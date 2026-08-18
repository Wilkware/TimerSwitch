<?php

declare(strict_types=1);

/** Generell funktions  */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\TimerSwitch\DebugHelper;
use Wilkware\TimerSwitch\EventHelper;
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
    use EventHelper;
    use VariableHelper;

    // -------------------------------------------------------------------------
    // Timing Constants
    // -------------------------------------------------------------------------

    /** @var string Timming ON */
    // private const TIMING_ON = 'On';

    /** @var string Timming OFF */
    private const TIMING_OFF = 'Off';

    /** @var string Timming Start */
    private const TIMING_START = 'TimingStart';

    /** @var string Timming End */
    private const TIMING_END = 'TimingEnd';

    /** @var string Timming Offset */
    private const TIMING_OFFSET = 'Offset';

    /** @var string Weekly Sheduler ON */
    private const TIMING_WEEKLYON = 'WeeklySchedulOn';

    /** @var string Weekly Sheduler OFF */
    private const TIMING_WEEKLYOFF = 'WeeklySchedulOff';

    /** @var string Seperator Indicator */
    private const TIMING_SEPERATOR = 'None';

    // -------------------------------------------------------------------------
    // Schedule Constants
    // -------------------------------------------------------------------------

    /** @var int Scheduler ON */
    private const SCHEDULE_ON = 1;

    /** @var int Scheduler OFF */
    private const SCHEDULE_OFF = 2;

    /** @var array<int,string> Scheduler Weekly */
    private const SCHEDULE_WEEKLY = ['Check', 'Timer', 'Delete', 'Copy'];

    /** @var array<int,string> Scheduler Days */
    private const SCHEDULE_DAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'So'];

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var int Min IPS Object ID  */
    private const IPS_MIN_ID = 10000;

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
        'PRESENTATION'   => VARIABLE_PRESENTATION_SWITCH,
        'USE_ICON_FALSE' => false,
        'USAGE_TYPE'     => 0,
        'ICON_TRUE'      => 'power-off',
        'ICON_FALSE'     => 'power-off',
        'GLOW_INTENSITY' => 50,
        'GLOW_COLOR'     => 16771899,
    ];

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
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

        // Timming
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
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
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
            $form['elements'][3]['items'][0]['items'][0]['options'] = array_merge($form['elements'][3]['items'][0]['items'][0]['options'], $options);
            $form['elements'][3]['items'][0]['items'][2]['options'] = array_merge($form['elements'][3]['items'][0]['items'][2]['options'], $options);
        }

        // activate/deactivate times
        $start = $this->ReadPropertyString('TimingStart');
        $end = $this->ReadPropertyString('TimingEnd');
        for ($d = 1; $d <= 7; $d++) {
            for ($i = 0; $i <= 8; $i++) {
                if ($form['elements'][3]['items'][$d]['items'][$i]['type'] != 'Label') {
                    if ($this->StartsWith($form['elements'][3]['items'][$d]['items'][$i]['name'], self::TIMING_START)) {
                        $form['elements'][3]['items'][$d]['items'][$i]['enabled'] = ($start == self::TIMING_WEEKLYON);
                    }
                    if ($this->StartsWith($form['elements'][3]['items'][$d]['items'][$i]['name'], self::TIMING_END)) {
                        $form['elements'][3]['items'][$d]['items'][$i]['enabled'] = ($end == self::TIMING_WEEKLYOFF);
                    }
                }
            }
        }

        // number of devices
        $number = $this->ReadPropertyInteger('DeviceNumber');
        $form['elements'][4]['items'][1]['visible'] = ($number === self::DEVICE_ONE);
        $form['elements'][4]['items'][2]['visible'] = ($number === self::DEVICE_MULTIPLE);

        // device list (set status column)
        $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
        foreach ($variables as $variable) {
            $form['elements'][4]['items'][2]['values'][] = [
                'Status' => $this->GetVariableStatus($variable['VariableID']),
            ];
        }

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
            if (IPS_VariableExists($variable)) {
                $this->RegisterMessage($variable, VM_UPDATE);
            } else {
                $this->SetStatus(104);
                return;
            }
        } else {
            //Create multiple trigger
            $ok = 0;
            foreach ($variables as $variable) {
                if ($this->GetVariableStatus($variable['VariableID']) == 'OK') {
                    $ok++;
                }
            }
            //If we are missing triggers or devices will not work
            if ((empty($variables)) || ($ok != count($variables))) {
                $this->SetStatus(104);
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

        // Safty Check Seperators
        $start = $this->ReadPropertyString('TimingStart');
        if ($start == self::TIMING_SEPERATOR) {
            $this->SetStatus(201);
            return;
        }
        $end = $this->ReadPropertyString('TimingEnd');
        if ($end == self::TIMING_SEPERATOR) {
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
        }

        // Get End ID
        if ($end == self::TIMING_OFF) {
            $ce = -1;
        } elseif ($end == self::TIMING_WEEKLYOFF) {
            $ce = 0;
        } else {
            $ce = $this->GetLocationID($end);
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

        // Aditionally Switch
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
        // $this->LogDebug(__FUNCTION__, 'Sender: '. $sender . 'Data: ' . print_r($data, true), 0);
        switch ($message) {
            case VM_UPDATE:
                $vid = 0;
                // Extract vars
                $number = $this->ReadPropertyInteger('DeviceNumber');
                if ($number == self::DEVICE_ONE) {
                    $vid = $this->ReadPropertyInteger('DeviceVariable');
                } else {
                    $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
                    foreach ($variables as $variable) {
                        if ($variable['VariableID'] == $sender) {
                            $vid = $sender;
                        }
                    }
                }
                $sid = $this->ReadAttributeInteger('ConditionalStart');
                $eid = $this->ReadAttributeInteger('ConditionalEnd');
                // Safety Check
                if (($sender != $vid) || ($sender != $sid) || ($sender != $eid)) {
                    if (($sender == $vid) && ($data[1] == true)) {
                        $this->LogDebug(__FUNCTION__, $sender . ': device variable changed');
                        $this->SwitchState($data[0]);
                    } elseif ($data[1] == true) {
                        $this->LogDebug(__FUNCTION__, $sender . ': conditional start changed');
                        $this->Schedule($sender);
                    }
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
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'switch_proxy':
                if ($this->SwitchDevice($value)) {
                    $this->SetValueBoolean($ident, $value);
                }
                break;
            default:
                eval('$this->' . $ident . '(\'' . $value . '\');');
        }
    }

    /**
     * Schedule
     *
     * @param integer $value Action value (ON=1, OFF=2)
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
            // never happend!!!!
            $this->LogDebug(__FUNCTION__, 'Start Trigger is off');
            return;
        }
        // End is OFF
        if (($ce == -1) && ($value == self::SCHEDULE_OFF)) {
            // never happend!!!!
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
                $int = GetValue($ce);
                $this->LogDebug(__FUNCTION__, 'Check was OFF before ON: ' . $mid . ' < ' . $int);
                if ($mid < $int) {
                    $this->LogDebug(__FUNCTION__, 'OFF wass before ON: ' . boolval($mid < $int));
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
                $int = $lts[1];
                $this->LogDebug(__FUNCTION__, 'Check was OFF before ON: ' . $mid . ' < ' . $int);
                if ($mid < $int) {
                    $this->LogDebug(__FUNCTION__, 'OFF was before ON: ' . boolval($mid < $int));
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
     * User has select an new number of devices.
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
     * User has select an new start trigger.
     *
     * @param string $value The selected value.
     *
     * @return void
     */
    protected function OnTimingStart(string $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        if ($value == self::TIMING_SEPERATOR) {
            $this->UpdateFormField(self::TIMING_START, 'value', self::TIMING_OFF);
        }
        $this->WeeklySchedule(self::TIMING_START, ($value == self::TIMING_WEEKLYON));
    }

    /**
     * User has select an new end trigger.
     *
     * @param string $value The selected value.
     *
     * @return void
     */
    protected function OnTimingEnd(string $value)
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        if ($value == self::TIMING_SEPERATOR) {
            $this->UpdateFormField(self::TIMING_END, 'value', self::TIMING_OFF);
        }
        $this->WeeklySchedule(self::TIMING_END, ($value == self::TIMING_WEEKLYOFF));
    }

    /**
     * User has clickt on start delete button.
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
     * User has clickt on end delete button.
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
     * User has clickt on start copy button.
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
     * User has clickt on end copy button.
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
     * @param boolean $state True for ON, otherwise OFF.
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
     * @param boolean $state ON/OFF.
     *
     * @return bool True if switch successful, otherwise false.
     */
    private function SwitchDevice($state): bool
    {
        $ret = true;
        $this->LogDebug(__FUNCTION__, 'New State: ' . var_export($state, true));

        // Check Script
        $ds = $this->ReadPropertyInteger('DeviceScript');
        if ($ds != 0) {
            if (IPS_ScriptExists($ds)) {
                $rs = IPS_RunScriptEx($ds, ['State' => $state]);
                $this->LogDebug(__FUNCTION__, 'RundScript: ' . $rs);
            } else {
                $this->LogDebug(__FUNCTION__, 'Script #' . $ds . ' doesnt exist!');
            }
        }

        // Check Variable
        $number = $this->ReadPropertyInteger('DeviceNumber');
        if ($number == self::DEVICE_ONE) {
            $dv = $this->ReadPropertyInteger('DeviceVariable');
            if ($dv != 0) {
                $ret = @RequestAction($dv, boolval($state));
                if ($ret === false) {
                    $this->LogDebug(__FUNCTION__, 'Device #' . $dv . ' could not be switched by RequestAction!');
                    $ret = @SetValueBoolean($dv, boolval($state));
                    if ($ret === false) {
                        $this->LogDebug(__FUNCTION__, 'Device could not be switched by Boolean!');
                    }
                }
                if ($ret === false) {
                    $this->LogMessage('Device could not be switched (UNREACH)!');
                    return false;
                }
            }
            return $ret;
        } else {
            $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
            $ret = true;
            foreach ($variables as $variable) {
                $ret = @RequestAction($variable['VariableID'], boolval($state));
                if ($ret === false) {
                    $this->LogDebug(__FUNCTION__, 'Device #' . $variable['VariableID'] . ' could not be switched by RequestAction!');
                    $ret = false;
                }
            }
            if ($ret === false) {
                $this->LogMessage('One or more devices could not be switched!');
            }
            return $ret;
        }
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

        if ($var['VariableType'] === VARIABLETYPE_BOOLEAN) {
            $profile = $var['VariableCustomProfile'] !== ''
                ? $var['VariableCustomProfile']
                : $var['VariableProfile'];

            if (!IPS_VariableProfileExists($profile)) {
                return $this->Translate('Profile required');
            }
        }

        $action = $var['VariableCustomAction'] !== 0
            ? $var['VariableCustomAction']
            : $var['VariableAction'];

        if ($action <= self::IPS_MIN_ID) {
            return $this->Translate('Action required');
        }

        return 'OK';
    }

    /**
     * Returns the status variablen ID of the Location Control by given ident.
     *
     * @param string $ident Ident of the Location Control Variable
     *
     * @return integer Variablen ID
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
        // buffer setup
        $ts = 0;
        $te = 0;
        // Disable Timer
        $this->SetTimerInterval('ScheduleTimerOn', 0);
        $this->SetTimerInterval('ScheduleTimerOff', 0);
        // New Timer
        $day = date('N', time()) - 1;
        $now = time();
        $add = 0;
        if ($start == self::TIMING_WEEKLYON) {
            $active = false;
            for ($i = $day; $i <= 6; $i++) {
                $active = $this->ReadPropertyBoolean('TimingStartCheck' . self::SCHEDULE_DAYS[$i]);
                if ($active) {
                    $time = $this->ReadPropertyString('TimingStartTime' . self::SCHEDULE_DAYS[$i]);
                    $time = json_decode($time, true);
                    $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                    if ($next > $now) {
                        $ts = $next - $now;
                        $this->SetTimerInterval('ScheduleTimerOn', $ts * 1000);
                        break;
                    } else {
                        $active = false;
                    }
                }
                $add++;
            }
            // if no day behind active, then look before
            if (!$active) {
                for ($i = 0; $i < $day; $i++) {
                    $active = $this->ReadPropertyBoolean('TimingStartCheck' . self::SCHEDULE_DAYS[$i]);
                    if ($active) {
                        $time = $this->ReadPropertyString('TimingStartTime' . self::SCHEDULE_DAYS[$i]);
                        $time = json_decode($time, true);
                        $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                        if ($next > $now) {
                            $ts = $next - $now;
                            $this->SetTimerInterval('ScheduleTimerOn', $ts * 1000);
                            break;
                        } else {
                            $active = false;
                        }
                    }
                    $add++;
                }
            }
        }
        $add = 0;
        if ($end == self::TIMING_WEEKLYOFF) {
            $active = false;
            for ($i = $day; $i <= 6; $i++) {
                $active = $this->ReadPropertyBoolean('TimingEndCheck' . self::SCHEDULE_DAYS[$i]);
                if ($active) {
                    $time = $this->ReadPropertyString('TimingEndTime' . self::SCHEDULE_DAYS[$i]);
                    $time = json_decode($time, true);
                    $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                    if ($next > $now) {
                        $te = $next - $now;
                        $this->SetTimerInterval('ScheduleTimerOff', $te * 1000);
                        break;
                    } else {
                        $active = false;
                    }
                }
                $add++;
            }
            // if no day behind active, thern look before
            if (!$active) {
                for ($i = 0; $i < $day; $i++) {
                    $active = $this->ReadPropertyBoolean('TimingEndCheck' . self::SCHEDULE_DAYS[$i]);
                    if ($active) {
                        $time = $this->ReadPropertyString('TimingEndTime' . self::SCHEDULE_DAYS[$i]);
                        $time = json_decode($time, true);
                        $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                        if ($next > $now) {
                            $te = $next - $now;
                            $this->SetTimerInterval('ScheduleTimerOff', $te * 1000);
                            break;
                        } else {
                            $active = false;
                        }
                    }
                    $add++;
                }
            }
        }
        $this->SetBuffer('schedule', ($ts > 0 ? $ts + $now : 0) . ':' . ($te > 0 ? $te + $now : 0));
        $this->LogDebug(__FUNCTION__, 'Buffer: ' . $this->GetBuffer('schedule'));
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
