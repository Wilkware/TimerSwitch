<?php

declare(strict_types=1);

/** General functions */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\TimerSwitch\DebugHelper;
use Wilkware\TimerSwitch\FormHelper;
use Wilkware\TimerSwitch\VariableHelper;

/**
 * CLASS Timer Switch
 */
class TimerSwitch extends IPSModuleStrict
{
    // -------------------------------------------------------------------------
    // Traits
    // -------------------------------------------------------------------------

    use DebugHelper;
    use FormHelper;
    use VariableHelper;

    // -------------------------------------------------------------------------
    // Timing Constants
    // -------------------------------------------------------------------------

    /** @var string Timing OFF */
    private const TIMING_OFF = 'Off';

    /** @var string Timing Start */
    private const TIMING_START = 'TimingStart';

    /** @var string Timing End */
    private const TIMING_END = 'TimingEnd';

    /** @var string Timing Offset */
    private const TIMING_OFFSET = 'Offset';

    /** @var string Weekly Scheduler ON */
    private const TIMING_WEEKLYON = 'WeeklySchedulOn';

    /** @var string Weekly Scheduler OFF */
    private const TIMING_WEEKLYOFF = 'WeeklySchedulOff';

    /** @var string Separator Indicator */
    private const TIMING_SEPARATOR = 'None';

    // -------------------------------------------------------------------------
    // Schedule Constants
    // -------------------------------------------------------------------------

    /** @var int Scheduler ON */
    private const SCHEDULE_ON = 1;

    /** @var int Scheduler OFF */
    private const SCHEDULE_OFF = 2;

    /** @var array<int,string> Scheduler Weekly */
    private const SCHEDULE_WEEKLY = ['Check', 'Time', 'Delete', 'Copy'];

    /** @var array<int,string> Scheduler Days */
    private const SCHEDULE_DAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'So'];

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var int Device One */
    private const DEVICE_ONE = 0;

    /** @var int Device Multiple  */
    private const DEVICE_MULTIPLE = 1;

    /** @var string Location Control GUID */
    private const LOCATION_GUID = '{45E97A63-F870-408A-B259-2933F7EABF74}';

    // -------------------------------------------------------------------------
    // Presentations
    // -------------------------------------------------------------------------

    /**
     * @var array<string,mixed> Presentation (Switch)
     */
    private const LTM_PRESENTATION_SWITCH = [
        'ICON_TRUE'    => 'power-off',
        'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
    ];

    /**
     * @var array<string,mixed> Presentation (Date/Time); presentation has no icon
     */
    private const LTM_PRESENTATION_NEXT = [
        'DAY_OF_THE_WEEK' => true,
        'PRESENTATION'    => VARIABLE_PRESENTATION_DATE_TIME,
    ];

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        // Instance
        $this->RegisterPropertyBoolean('InstanceActive', true);

        // Timing
        $this->RegisterPropertyString('TimingStart', 'Sunrise');
        $this->RegisterPropertyString('TimingEnd', 'Sunset');

        // Device
        $this->RegisterPropertyInteger('DeviceNumber', 0);
        $this->RegisterPropertyInteger('DeviceVariable', 0);
        $this->RegisterPropertyString('DeviceVariables', '[]');
        $this->RegisterPropertyInteger('DeviceScript', 0);

        // Settings
        $this->RegisterPropertyBoolean('SettingsTime', false);
        $this->RegisterPropertyBoolean('SettingsSwitch', false);
        $this->RegisterPropertyBoolean('SettingsDirect', false);
        $this->RegisterPropertyBoolean('SettingsNext', false);

        // Schedule
        foreach (self::SCHEDULE_DAYS as $day) {
            $this->RegisterPropertyBoolean(self::TIMING_START . 'Check' . $day, true);
            $this->RegisterPropertyBoolean(self::TIMING_END . 'Check' . $day, true);
            $this->RegisterPropertyString(self::TIMING_START . 'Time' . $day, '{"hour":6,"minute":0,"second":0}');
            $this->RegisterPropertyString(self::TIMING_END . 'Time' . $day, '{"hour":18,"minute":0,"second":0}');
        }

        // Attribute
        $this->RegisterAttributeInteger('ConditionalStart', 0);
        $this->RegisterAttributeInteger('ConditionalEnd', 0);
        $this->RegisterAttributeInteger('ConditionalTime', 0);

        // Timer
        $this->RegisterTimer('ScheduleTimerOn', 0, 'LTM_Schedule(' . $this->InstanceID . ',' . self::SCHEDULE_ON . ');');
        $this->RegisterTimer('ScheduleTimerOff', 0, 'LTM_Schedule(' . $this->InstanceID . ',' . self::SCHEDULE_OFF . ');');

        // Kernel
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
    }

    /**
     * Migrates the configuration of older versions.
     *
     * Up to v3.1 a single switch variable without an action was set directly.
     * Keep this behaviour for existing instances which rely on it.
     *
     * @param string $json Configuration data as JSON
     *
     * @return string Migrated configuration data as JSON
     */
    public function Migrate(string $json): string
    {
        //Never delete this line!
        parent::Migrate($json);

        $data = json_decode($json);
        if (!is_object($data) || !isset($data->configuration) || !is_object($data->configuration)) {
            return $json;
        }
        $config = $data->configuration;
        if (isset($config->SettingsDirect)) {
            return $json;
        }
        if ((int) ($config->DeviceNumber ?? self::DEVICE_ONE) !== self::DEVICE_ONE) {
            return $json;
        }
        $vid = (int) ($config->DeviceVariable ?? 0);
        if (($vid > 0) && IPS_VariableExists($vid) && !HasAction($vid)) {
            $config->SettingsDirect = true;
            return json_encode($data);
        }
        return $json;
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return string Content of the configuration page.
     */
    public function GetConfigurationForm(): string
    {
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        // Extract Version
        $ins = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($ins['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);
        $version = sprintf('v%s.%d', $lib['Version'], $lib['Build']);
        $this->ModifyFormElement($form['actions'], 'Version', function (array &$element) use ($version): void
        {
            $element['caption'] = $version;
        });

        // add self defined offsets
        $options = [['caption' => '------------------------------', 'value' => 'None']];
        $lcs = IPS_GetInstanceListByModuleID(self::LOCATION_GUID);
        $this->LogDebug(__FUNCTION__, $lcs);
        if (isset($lcs[0])) {
            $childs = IPS_GetChildrenIDs($lcs[0]);
            $this->LogDebug(__FUNCTION__, $childs);
            foreach ($childs as $cid) {
                $obj = IPS_GetObject($cid);
                $this->LogDebug(__FUNCTION__, $obj['ObjectIdent']);
                if ($this->StartsWith($obj['ObjectIdent'], self::TIMING_OFFSET)) {
                    $options[] = ['caption' => $obj['ObjectName'], 'value' => $obj['ObjectIdent']];
                }
            }
        }
        if (count($options) > 1) {
            foreach ([self::TIMING_START, self::TIMING_END] as $name) {
                $this->ModifyFormElement($form['elements'], $name, function (array &$element) use ($options): void
                {
                    $element['options'] = array_merge($element['options'], $options);
                });
            }
        }

        // activate/deactivate times
        $weekly = [
            self::TIMING_START => ($this->ReadPropertyString('TimingStart') == self::TIMING_WEEKLYON),
            self::TIMING_END   => ($this->ReadPropertyString('TimingEnd') == self::TIMING_WEEKLYOFF),
        ];
        foreach ($weekly as $prefix => $enabled) {
            foreach (self::SCHEDULE_DAYS as $day) {
                foreach (self::SCHEDULE_WEEKLY as $part) {
                    $this->ModifyFormElement($form['elements'], $prefix . $part . $day, function (array &$element) use ($enabled): void
                    {
                        $element['enabled'] = $enabled;
                    });
                }
            }
        }

        // number of devices
        $number = $this->ReadPropertyInteger('DeviceNumber');
        $this->ModifyFormElement($form['elements'], 'DeviceVariable', function (array &$element) use ($number): void
        {
            $element['visible'] = ($number === self::DEVICE_ONE);
        });

        // device list (set status column)
        $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
        $this->ModifyFormElement($form['elements'], 'DeviceVariables', function (array &$element) use ($number, $variables): void
        {
            $element['visible'] = ($number === self::DEVICE_MULTIPLE);
            foreach ($variables as $variable) {
                $element['values'][] = [
                    'Status' => $this->GetVariableStatus((int) $variable['VariableID']),
                ];
            }
        });

        // return form
        return json_encode($form);
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        // Disable Timer
        $this->SetTimerInterval('ScheduleTimerOn', 0);
        $this->SetTimerInterval('ScheduleTimerOff', 0);

        // Register Message
        if ($this->ReadPropertyInteger('DeviceVariable') > 0) {
            $this->UnregisterMessage($this->ReadPropertyInteger('DeviceVariable'), VM_UPDATE);
        }
        if ($this->ReadAttributeInteger('ConditionalStart') > 0) {
            $this->UnregisterMessage($this->ReadAttributeInteger('ConditionalStart'), VM_UPDATE);
        }
        if ($this->ReadAttributeInteger('ConditionalEnd') > 0) {
            $this->UnregisterMessage($this->ReadAttributeInteger('ConditionalEnd'), VM_UPDATE);
        }

        //Never delete this line!
        parent::ApplyChanges();

        // Wait for kernel start (Location Control and devices must be loaded)
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        // Next switching times (reset, CalculateTimer sets the valid values)
        $next = $this->ReadPropertyBoolean('SettingsNext');
        $this->MaintainVariable('NextOn', $this->Translate('Next switch on'), VARIABLETYPE_INTEGER, self::LTM_PRESENTATION_NEXT, 1, $next);
        $this->MaintainVariable('NextOff', $this->Translate('Next switch off'), VARIABLETYPE_INTEGER, self::LTM_PRESENTATION_NEXT, 2, $next);
        if ($next) {
            $this->SetValueInteger('NextOn', 0);
            $this->SetValueInteger('NextOff', 0);
        }

        //Delete all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all registrations in order to readd them
        foreach ($this->GetMessageList() as $sender => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($sender, $message);
            }
        }

        //Register references
        $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
        foreach ($variables as $variable) {
            if (IPS_VariableExists($variable['VariableID'])) {
                $this->RegisterReference($variable['VariableID']);
            }
        }
        $variable = $this->ReadPropertyInteger('DeviceVariable');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
        }

        //Register update messages
        $number = $this->ReadPropertyInteger('DeviceNumber');
        if ($number == self::DEVICE_ONE) {
            //Create one trigger
            if ($this->GetVariableStatus($variable) === 'OK') {
                $this->RegisterMessage($variable, VM_UPDATE);
            } else {
                $this->SetStatus(205);
                return;
            }
        } else {
            //Create multiple trigger
            $ok = 0;
            foreach ($variables as $variable) {
                if ($this->GetVariableStatus((int) $variable['VariableID']) === 'OK') {
                    $ok++;
                }
            }
            //If we are missing triggers or devices will not work
            if ((empty($variables)) || ($ok != count($variables))) {
                $this->SetStatus(205);
                return;
            }
            //Register update messages
            foreach ($variables as $variable) {
                $this->RegisterMessage($variable['VariableID'], VM_UPDATE);
            }
        }

        // On/Off Check
        $active = $this->ReadPropertyBoolean('InstanceActive');
        if (!$active) {
            $this->SetStatus(104);
            return;
        }

        // Safety Check Separators
        $start = $this->ReadPropertyString('TimingStart');
        if ($start == self::TIMING_SEPARATOR) {
            $this->SetStatus(201);
            return;
        }
        $end = $this->ReadPropertyString('TimingEnd');
        if ($end == self::TIMING_SEPARATOR) {
            $this->SetStatus(202);
            return;
        }

        // Check Start <> End
        if (($start != self::TIMING_OFF) && ($end != self::TIMING_OFF)) {
            if ($start == $end) {
                $this->SetStatus(203);
                return;
            }
        }

        // Get Start ID
        if ($start == self::TIMING_OFF) {
            $cs = -1;
        } elseif ($start == self::TIMING_WEEKLYON) {
            $cs = 0;
        } else {
            $cs = $this->GetLocationID($start);
            if ($cs === 0) {
                $this->SetStatus(204);
                return;
            }
        }

        // Get End ID
        if ($end == self::TIMING_OFF) {
            $ce = -1;
        } elseif ($end == self::TIMING_WEEKLYOFF) {
            $ce = 0;
        } else {
            $ce = $this->GetLocationID($end);
            if ($ce === 0) {
                $this->SetStatus(204);
                return;
            }
        }

        // Write
        $this->WriteAttributeInteger('ConditionalStart', $cs);
        $this->LogDebug(__FUNCTION__, $start . ' = ' . $cs);
        $this->WriteAttributeInteger('ConditionalEnd', $ce);
        $this->LogDebug(__FUNCTION__, $end . ' = ' . $ce);

        // Register Start
        if ($cs > 0) {
            $this->RegisterMessage($cs, VM_UPDATE);
        }

        // Register End
        if ($ce > 0) {
            $this->RegisterMessage($ce, VM_UPDATE);
        }

        // Off before On check
        $ct = 0;
        if ($this->ReadPropertyBoolean('SettingsTime')) {
            $ct = 1;
        }
        $this->WriteAttributeInteger('ConditionalTime', $ct);
        $this->LogDebug(__FUNCTION__, 'ConditionalTime = ' . $ct);

        // Additional Switch
        $switch = $this->ReadPropertyBoolean('SettingsSwitch');
        $this->MaintainVariable('switch_proxy', $this->Translate('Switch'), VARIABLETYPE_BOOLEAN, self::LTM_PRESENTATION_SWITCH, 0, $switch);
        if ($switch) {
            $this->EnableAction('switch_proxy');
        }

        // Set next Timer
        $this->CalculateTimer();

        // All okay
        $this->SetStatus(102);
    }

    /**
     * The content of the function can be overwritten in order to carry out own reactions to certain messages.
     * The function is only called for registered MessageIDs/SenderIDs combinations.
     *
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     *
     * @param int   $timestamp Continuous counter timestamp
     * @param int   $sender    Sender ID
     * @param int   $message   ID of the message
     * @param array{0:mixed,1:bool,2:mixed,3:int} $data Data of the message
     *
     * @return void
     */
    public function MessageSink(int $timestamp, int $sender, int $message, array $data): void
    {
        switch ($message) {
            case IPS_KERNELSTARTED:
                $this->ApplyChanges();
                break;
            case VM_UPDATE:
                if (!$data[1]) {
                    break;
                }
                $sid = $this->ReadAttributeInteger('ConditionalStart');
                $eid = $this->ReadAttributeInteger('ConditionalEnd');
                if (in_array($sender, $this->GetDeviceVariables(), true)) {
                    $this->LogDebug(__FUNCTION__, $sender . ': device variable changed');
                    $this->SwitchState((bool) $data[0]);
                } elseif (($sender === $sid) || ($sender === $eid)) {
                    $this->LogDebug(__FUNCTION__, $sender . ': conditional variable changed');
                    // Schedule identifies conditional triggers by comparing the value with the start/end variable ID
                    $this->Schedule($sender);
                } else {
                    $this->LogDebug(__FUNCTION__, $sender . ' unknown!');
                }
                break;
        }
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     *
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . var_export($value, true));
        switch ($ident) {
            case 'switch_proxy':
                if ($this->SwitchDevice((bool) $value)) {
                    $this->SetValueBoolean($ident, (bool) $value);
                }
                break;
            case 'TestSwitch':
                if ($this->SwitchDevice((bool) $value)) {
                    $this->SwitchState((bool) $value);
                } else {
                    echo $this->Translate('One or more devices could not be switched!');
                }
                break;
            case 'OnDeviceNumber':
            case 'OnTimingStart':
            case 'OnTimingEnd':
            case 'OnStartDelete':
            case 'OnEndDelete':
            case 'OnStartCopy':
            case 'OnEndCopy':
                $this->$ident((string) $value);
                break;
            default:
                $this->LogDebug(__FUNCTION__, 'Unknown ident: ' . $ident);
        }
    }

    /**
     * Schedule
     *
     * @param int $value Action value (ON=1, OFF=2)
     *
     * @return void
     */
    public function Schedule(int $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        // Mode?
        $cs = $this->ReadAttributeInteger('ConditionalStart');
        $ce = $this->ReadAttributeInteger('ConditionalEnd');
        $ct = $this->ReadAttributeInteger('ConditionalTime');
        $this->LogDebug(__FUNCTION__, 'Conditional is :' . $cs . ', ' . $ce . ', ' . $ct);

        // Start is OFF
        if (($cs == -1) && ($value == self::SCHEDULE_ON)) {
            $this->LogDebug(__FUNCTION__, 'Start Trigger is off');
            return;
        }
        // End is OFF
        if (($ce == -1) && ($value == self::SCHEDULE_OFF)) {
            $this->LogDebug(__FUNCTION__, 'End Trigger is off');
            return;
        }

        // Start is Time(Clock)
        if (($cs == 0) && ($value == self::SCHEDULE_ON)) {
            $this->LogDebug(__FUNCTION__, 'Start timer switch');
            $switch = true;
            // Check was OFF before ON (only for conditional timing setup, means ce is set)
            if ($ct > 0 && $ce > 0) {
                $mid = mktime(24, 0, 0);
                $int = (int) GetValue($ce);
                $this->LogDebug(__FUNCTION__, 'Check was OFF before ON: ' . $mid . ' < ' . $int);
                if ($mid < $int) {
                    $this->LogDebug(__FUNCTION__, 'OFF was before ON: ' . var_export($mid < $int, true));
                    $switch = false;
                }
            }
            if ($switch) {
                // Everything okay - switch ON
                if ($this->SwitchDevice(true)) {
                    $this->SwitchState(true);
                }
            }
        }
        // End is Time(Clock)
        if (($ce == 0) && ($value == self::SCHEDULE_OFF)) {
            $this->LogDebug(__FUNCTION__, 'End timer switch');
            if ($this->SwitchDevice(false)) {
                $this->SwitchState(false);
            }
        }

        // Start conditional switching
        if ($cs == $value) {
            $this->LogDebug(__FUNCTION__, 'Start conditional-Switch: ' . $value);
            $switch = true;
            // Check was OFF before ON (only for conditional timing setup; means ce is clock(0))
            if ($ct > 0 && $ce == 0) {
                $buf = $this->GetBuffer('schedule');
                $lts = explode(':', $buf);
                $mid = mktime(24, 0, 0);
                $int = (int) ($lts[1] ?? 0);
                $this->LogDebug(__FUNCTION__, 'Check was OFF before ON: ' . $mid . ' < ' . $int);
                if ($mid < $int) {
                    $this->LogDebug(__FUNCTION__, 'OFF was before ON: ' . var_export($mid < $int, true));
                    $switch = false;
                }
            }
            if ($switch) {
                // Everything okay - switch ON
                if ($this->SwitchDevice(true)) {
                    $this->SwitchState(true);
                }
            }
        }
        // End conditional switching
        if ($ce == $value) {
            $this->LogDebug(__FUNCTION__, 'End conditional-Switch: ' . $value);
            if ($this->SwitchDevice(false)) {
                $this->SwitchState(false);
            }
        }

        // Calculate the timer new
        $this->CalculateTimer();
    }

    /**
     * User has selected a new number of devices.
     *
     * @param string $value The selected value.
     *
     * @return void
     */
    protected function OnDeviceNumber(string $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        $this->UpdateFormField('DeviceVariable', 'visible', (intval($value) == self::DEVICE_ONE));
        $this->UpdateFormField('DeviceVariables', 'visible', (intval($value) == self::DEVICE_MULTIPLE));
    }

    /**
     * User has selected a new start trigger.
     *
     * @param string $value The selected value.
     *
     * @return void
     */
    protected function OnTimingStart(string $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        if ($value == self::TIMING_SEPARATOR) {
            $this->UpdateFormField(self::TIMING_START, 'value', self::TIMING_OFF);
        }
        $this->WeeklySchedule(self::TIMING_START, ($value == self::TIMING_WEEKLYON));
    }

    /**
     * User has selected a new end trigger.
     *
     * @param string $value The selected value.
     *
     * @return void
     */
    protected function OnTimingEnd(string $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        if ($value == self::TIMING_SEPARATOR) {
            $this->UpdateFormField(self::TIMING_END, 'value', self::TIMING_OFF);
        }
        $this->WeeklySchedule(self::TIMING_END, ($value == self::TIMING_WEEKLYOFF));
    }

    /**
     * User has clicked on start delete button.
     *
     * @param string $name The button field name.
     *
     * @return void
     */
    protected function OnStartDelete(string $name): void
    {
        $this->LogDebug(__FUNCTION__, 'Name: ' . $name);
        $this->UpdateFormField($name, 'value', '{"hour":6,"minute":0,"second":0}');
    }

    /**
     * User has clicked on end delete button.
     *
     * @param string $name The button field name.
     *
     * @return void
     */
    protected function OnEndDelete(string $name): void
    {
        $this->LogDebug(__FUNCTION__, 'Name: ' . $name);
        $this->UpdateFormField($name, 'value', '{"hour":18,"minute":0,"second":0}');
    }

    /**
     * User has clicked on start copy button.
     *
     * @param string $value The copy value.
     *
     * @return void
     */
    protected function OnStartCopy(string $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        $day = substr($value, 0, 2);
        $time = substr($value, 2);
        $this->LogDebug(__FUNCTION__, 'Day: ' . $day . ' Time: ' . $time);
        $this->UpdateFormField(self::TIMING_START . 'Time' . $day, 'value', $time);
    }

    /**
     * User has clicked on end copy button.
     *
     * @param string $value The copy value.
     *
     * @return void
     */
    protected function OnEndCopy(string $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        $day = substr($value, 0, 2);
        $time = substr($value, 2);
        $this->LogDebug(__FUNCTION__, 'Day: ' . $day . ' Time: ' . $time);
        $this->UpdateFormField(self::TIMING_END . 'Time' . $day, 'value', $time);
    }

    /**
     * Switch device state.
     *
     * @param bool $state True for ON, otherwise OFF.
     *
     * @return void
     */
    private function SwitchState(bool $state): void
    {
        $this->LogDebug(__FUNCTION__, 'New Value: ' . var_export($state, true));
        // Check shadow Variable
        if ($this->ReadPropertyBoolean('SettingsSwitch')) {
            $this->SetValueBoolean('switch_proxy', boolval($state));
        }
    }

    /**
     * Switch Variable/Script
     *
     * @param bool $state ON/OFF.
     *
     * @return bool True if switch successful, otherwise false.
     */
    private function SwitchDevice(bool $state): bool
    {
        $this->LogDebug(__FUNCTION__, 'New State: ' . var_export($state, true));

        // Check Script
        $ds = $this->ReadPropertyInteger('DeviceScript');
        if ($ds != 0) {
            if (IPS_ScriptExists($ds)) {
                $rs = IPS_RunScriptEx($ds, ['State' => $state]);
                $this->LogDebug(__FUNCTION__, 'RunScript: ' . $rs);
            } else {
                $this->LogDebug(__FUNCTION__, 'Script #' . $ds . ' does not exist!');
            }
        }

        // Check Variables
        $ret = true;
        foreach ($this->GetDeviceVariables() as $vid) {
            if (IPS_VariableExists($vid) && HasAction($vid)) {
                $ok = (@RequestAction($vid, $state) !== false);
            } elseif (IPS_VariableExists($vid) && $this->IsDirectSwitchable($vid)) {
                $ok = @SetValueBoolean($vid, $state);
            } else {
                $ok = false;
            }
            if (!$ok) {
                $this->LogDebug(__FUNCTION__, 'Device #' . $vid . ' could not be switched!');
                $ret = false;
            }
        }
        if ($ret === false) {
            $this->LogMessage('One or more devices could not be switched!', KL_WARNING);
        }
        return $ret;
    }

    /**
     * Returns the configured device variables (one or multiple).
     *
     * @return array<int,int> Variable IDs
     */
    private function GetDeviceVariables(): array
    {
        if ($this->ReadPropertyInteger('DeviceNumber') == self::DEVICE_ONE) {
            $ids = [$this->ReadPropertyInteger('DeviceVariable')];
        } else {
            $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
            $ids = array_map(fn (array $variable): int => (int) ($variable['VariableID'] ?? 0), is_array($variables) ? $variables : []);
        }
        return array_values(array_filter($ids, fn (int $id): bool => $id > 0));
    }

    /**
     * Checks whether a variable without action may be set directly.
     * Only allowed by the user and only for boolean variables which do not belong to an instance.
     *
     * @param int $vid Variable ID
     *
     * @return bool True if the value may be set directly
     */
    private function IsDirectSwitchable(int $vid): bool
    {
        return $this->ReadPropertyBoolean('SettingsDirect')
            && (IPS_GetVariable($vid)['VariableType'] === VARIABLETYPE_BOOLEAN)
            && !IPS_InstanceExists(IPS_GetParent($vid));
    }

    /**
     * Get variable status.
     *
     * @param int $vid The variable ID.
     *
     * @return string Status message
     */
    private function GetVariableStatus(int $vid): string
    {
        if (!IPS_VariableExists($vid)) {
            return $this->Translate('Missing');
        }

        $var = IPS_GetVariable($vid);

        if (!in_array($var['VariableType'], [VARIABLETYPE_BOOLEAN, VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT], true)) {
            return $this->Translate('Bool/Int/Float required');
        }

        if (!HasAction($vid) && !$this->IsDirectSwitchable($vid)) {
            return $this->Translate('Action required');
        }

        return 'OK';
    }

    /**
     * Returns the status variable ID of the Location Control by given ident.
     *
     * @param string $ident Ident of the Location Control Variable
     *
     * @return int Variable ID
     */
    private function GetLocationID(string $ident): int
    {
        $LCs = IPS_GetInstanceListByModuleID(self::LOCATION_GUID);
        if (isset($LCs[0])) {
            $id = @IPS_GetObjectIDByIdent($ident, $LCs[0]);
            if ($id != false) {
                return $id;
            }
        }
        $this->LogDebug(__FUNCTION__, 'No Location Control found!');
        return 0;
    }

    /**
     * Activate or deactivate weekly schedule elements.
     *
     * @param string $name Name of the trigger
     * @param bool $active True for activate, otherwise false.
     */
    private function WeeklySchedule(string $name, bool $active): void
    {
        $this->LogDebug(__FUNCTION__, $name . ': ' . var_export($active, true));
        foreach (self::SCHEDULE_DAYS as $day) {
            foreach (self::SCHEDULE_WEEKLY as $weekly) {
                $this->UpdateFormField($name . $weekly . $day, 'enabled', $active);
            }
        }
    }

    /**
     * Calculate and setup the next Timer.
     *
     * @return void
     */
    private function CalculateTimer(): void
    {
        // read setup
        $start = $this->ReadPropertyString('TimingStart');
        $end = $this->ReadPropertyString('TimingEnd');
        $this->LogDebug(__FUNCTION__, 'Start: ' . $start . ' End: ' . $end);
        // Disable Timer
        $this->SetTimerInterval('ScheduleTimerOn', 0);
        $this->SetTimerInterval('ScheduleTimerOff', 0);
        // New Timer
        $now = time();
        $ts = 0;
        $te = 0;
        if ($start == self::TIMING_WEEKLYON) {
            $ts = $this->NextWeeklyTime(self::TIMING_START, $now);
            if ($ts > 0) {
                $this->SetTimerInterval('ScheduleTimerOn', ($ts - $now) * 1000);
            }
        }
        if ($end == self::TIMING_WEEKLYOFF) {
            $te = $this->NextWeeklyTime(self::TIMING_END, $now);
            if ($te > 0) {
                $this->SetTimerInterval('ScheduleTimerOff', ($te - $now) * 1000);
            }
        }
        $this->SetBuffer('schedule', $ts . ':' . $te);
        $this->LogDebug(__FUNCTION__, 'Buffer: ' . $this->GetBuffer('schedule'));
        // Next switching times
        if ($this->ReadPropertyBoolean('SettingsNext')) {
            $this->SetValueInteger('NextOn', $this->NextSwitchTime($this->ReadAttributeInteger('ConditionalStart'), $ts));
            $this->SetValueInteger('NextOff', $this->NextSwitchTime($this->ReadAttributeInteger('ConditionalEnd'), $te));
        }
    }

    /**
     * Returns the next switching time of a trigger.
     *
     * @param int $id Conditional ID (-1 = off, 0 = weekly schedule, otherwise Location Control variable)
     * @param int $weekly Next time of the weekly schedule
     *
     * @return int Timestamp of the next switching time, 0 if unknown
     */
    private function NextSwitchTime(int $id, int $weekly): int
    {
        if ($id === 0) {
            return $weekly;
        }
        // Location Control variables jump to the next occurrence at the time of the event
        if (($id > 0) && IPS_VariableExists($id)) {
            return (int) GetValue($id);
        }
        return 0;
    }

    /**
     * Returns the next active switching time of the weekly schedule (today up to the same day next week).
     *
     * @param string $prefix Schedule prefix (TimingStart or TimingEnd)
     * @param int $now Current timestamp
     *
     * @return int Timestamp of the next switching time, 0 if no day is active
     */
    private function NextWeeklyTime(string $prefix, int $now): int
    {
        $today = (int) date('N', $now) - 1;
        for ($add = 0; $add <= 7; $add++) {
            $day = self::SCHEDULE_DAYS[($today + $add) % 7];
            if (!$this->ReadPropertyBoolean($prefix . 'Check' . $day)) {
                continue;
            }
            $time = json_decode($this->ReadPropertyString($prefix . 'Time' . $day), true);
            // Day offset via mktime keeps the wall clock time across DST changes
            $next = mktime($time['hour'], $time['minute'], $time['second'], (int) date('n', $now), (int) date('j', $now) + $add, (int) date('Y', $now));
            if ($next > $now) {
                return $next;
            }
        }
        return 0;
    }

    /**
     * Checks if a string starts with a given substring.
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for in the haystack.
     *
     * @return bool Returns true if haystack begins with needle, false otherwise.
     */
    private function StartsWith(string $haystack, string $needle): bool
    {
        return (string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
