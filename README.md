# Zeitschaltuhr (Timer Switch)

[![Home](https://img.shields.io/badge/Home-wilkware.de-0b1830.svg?style=flat-square)](https://wilkware.de/module/zeitschaltuhr/)
[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-4.0.20261102-orange.svg?style=flat-square)](https://github.com/Wilkware/TimerSwitch)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/TimerSwitch/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/TimerSwitch/actions)

Dieses Modul ermöglicht das Schalten eines Gerätes (Variable und/oder Skripts) in Abhängigkeit von Uhrzeit und/oder des täglichen Sonnenganges.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichtung](#user-content-4-einrichtung)
5. [Statusvariablen](#user-content-5-statusvariablen)
6. [Darstellungen](#user-content-6-darstellungen)
7. [Visualisierung](#user-content-7-visualisierung)
8. [Befehlsreferenz](#user-content-8-befehlsreferenz)
9. [Versionshistorie](#user-content-9-versionshistorie)

### 1. Funktionsumfang

Für eine einfache Zeitschaltung wäre dieses Modul normalerweise nicht notwendig. Die Erstellung einen Wochenplanes oder eines zyklischen Ereignisses ist mit IPS Bordmitteln recht einfach möglich. Interessant wird die Sache erst wenn man bedingtes und zyklisches Schalten verbinden möchte.
Für eine solche Kombination gibt es eine Reihe von Anwendungsfälle, wie z.B. ...

* Rollläden/Jalousien am Morgen zu einer definierten Zeit hochfahren (Arbeitstag unabhängig von Jahreszeit), aber abends zum Sonnenuntergang runterfahren
* Außenbeleuchtung bei einsetzender Dunkelheit einschalten, aber pünktlich um Mitternacht wieder ausschalten
* Haustür Notlicht einsetzenden der Dämmerung Ein- bzw.- Ausschalten
* oder zur Weihnachtszeit die Beleuchtung situativ schalten.

Das nur um einige Anregungen zu geben. Wahrscheinlich gibt es da noch einiges mehr an Ideen, welche sich so umsetzen lassen.

* Zeitschaltung anhand verschiedener Einstellmöglichkeiten:
  1. Aus => Ein- bzw. Ausschalten wird nicht vollzogen (externer Auslöser)
  2. Sonnengang => 8 mögliche Zeitpunkte wählbar (Sonnenaufgang und -untergang; zivile, nautische oder astronomische Dämmerung)
  3. Wochenplan => Steuerung über Zeitplan
  4. Offset => frei definierbare Zeitpunkte über das Location Control
* Zusätzlich bzw. ausschließlich kann ein Skript ausgeführt werden.
* Schaltvariablen werden über ihre Aktion geschaltet; boolesche Variablen ohne Aktion (die zu keiner Instanz gehören) können optional direkt gesetzt werden.
* Integer- bzw. Float-Variablen werden mit 1 (An) bzw. 0 (Aus) geschaltet.
* Option das Einschalten nur zu erlauben, wenn sich die Zeiten nicht überschneiden (zeitlich korrekte Abfolge, AN-vor-AUS).
* Statusvariable als Proxy-Schalter, z.B. für Verwendung im WebFront.
* Schalten kann über mehrere Tage hinweg organisiert werden (gezielter Einsatz des täglichen Zeitplanes).

### 2. Voraussetzungen

* Symcon ab Version 8.1

### 3. Installation

* Über den Modul Store das Modul _Zeitschaltuhr_ installieren.
* Alternativ über das Modul Control folgende URL hinzufügen.  
`https://github.com/Wilkware/TimerSwitch` oder `git://github.com/Wilkware/TimerSwitch.git`

### 4. Einrichtung

* Unter 'Instanz hinzufügen' ist das _Zeitschaltuhr_-Modul unter dem Hersteller '(Geräte)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> 🎚️ Schaltung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
An /Aus               | Schalter zum Aktivieren bzw. Deaktivieren der gesamten Schaltung, z.B. Weihnachtsbeleuchtung nur im Winter ;-)

> ⏰ Zeitsteuerung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Auslöser Einschalten  | Auswahlmöglichkeiten: Aus; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (An); Offsets
Auslöser Ausschalten  | Auswahlmöglichkeiten: ; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (Aus); Offsets
(Zeitplan)            | Hinterlegung einer täglichen Uhrzeit für AN & AUS (Montag - Sonntag)

> 💡 Geräte ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Geräteanzahl          | Auswahl bzw. Umschalter zwischen einzelnen und mehreren Geräten
Schaltvariable        | Schalt(Aktions-)variable (ein Gerät)
Schaltvariablen       | Liste von Geräten (mehrere Geräte)

> ⚙️ Erweiterte Einstellungen ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariable nur ein- bzw. ausschalten wenn zeitliche Abfolge korrekt ist (nur in Verbindung mit einem Wochenplan)! | true/false
Zusätzlich noch eine normale Schaltervariable anlegen (z.B. für Webfront)? | true/false
Variablen für die nächsten Schaltzeitpunkte anlegen? | true/false; legt die Statusvariablen 'Nächstes Einschalten' und 'Nächstes Ausschalten' an
Schaltvariablen ohne Aktion direkt setzen (nur Variablen, die zu keiner Instanz gehören)? | true/false (Standard: false); Variablen mit Aktion werden immer über RequestAction geschaltet
Skript | Gleichzeitig auszuführendes Skript (Status true/false wird als Array 'State' übergeben)

Aktionsbereich:

> 🧪 Test ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Jetzt einschalten     | Schaltet die konfigurierten Geräte (und das Skript) sofort ein
Jetzt ausschalten     | Schaltet die konfigurierten Geräte (und das Skript) sofort aus

### 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Ident                | Name                 | Typ     | Beschreibung
-------------------- | -------------------- | ------- | ------------------------------
switch_proxy         | Schalter             | Boolean | Proxy-Schalter zum direkten Schalten der Geräte (z.B. für die Visualisierung)
NextOn               | Nächstes Einschalten | Integer | Zeitpunkt des nächsten Einschaltens (0 = kein Zeitpunkt, z.B. Auslöser Aus oder Instanz inaktiv)
NextOff              | Nächstes Ausschalten | Integer | Zeitpunkt des nächsten Ausschaltens (0 = kein Zeitpunkt, z.B. Auslöser Aus oder Instanz inaktiv)

_Hinweis:_ Die Variablen werden nur angelegt, wenn sie in den erweiterten Einstellungen aktiviert sind.

### 6. Darstellungen

Die Darstellungen werden direkt an den Statusvariablen hinterlegt, es werden keine Profile angelegt.

Variable             | Darstellung   | Werte
-------------------- | ------------- | ------------------------------
Schalter             | Schalter      | An / Aus
Nächstes Einschalten | Datum/Uhrzeit | Wochentag, Tag und Monat, Stunden und Minuten
Nächstes Ausschalten | Datum/Uhrzeit | Wochentag, Tag und Monat, Stunden und Minuten

### 7. Visualisierung

Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

### 8. Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

```php
void LTM_Schedule(int $InstanzID, int $Value);
```

Führt die Schaltung wie der interne Zeitplan aus (1 = Einschalten, 2 = Ausschalten). Die Funktion wird von den Timern des Moduls aufgerufen.

### 9. Versionshistorie

v4.0.20261102

* _NEU_: Option zum direkten Setzen von Schaltvariablen ohne Aktion (Erweiterte Einstellungen)
* _NEU_: Kompatibilität auf Symcon 8.1 hoch gesetzt
* _NEU_: Optionale Statusvariablen für den nächsten Ein- und Ausschaltzeitpunkt
* _NEU_: Testbereich zum direkten Ein- und Ausschalten im Konfigurationsformular
* _NEU_: Konfigurationsformular auf Standardstruktur umgestellt (Skript unter Erweiterte Einstellungen)
* _NEU_: Eigene Statusmeldungen für fehlendes Location Control bzw. ungültige Schaltvariablen
* _FIX_: Wochenplan mit nur einem aktiven Tag schaltete nur einmal
* _FIX_: Wochenplan bei Sommer-/Winterzeitumstellung um eine Stunde verschoben
* _FIX_: Fehler bei Integer-/Float-Schaltvariablen behoben
* _FIX_: Fehlerhafte Rückmeldung beim Schalten mehrerer Geräte behoben
* _FIX_: Initialisierung erst nach vollständigem Systemstart
* _FIX_: Variablen mit Darstellung statt Profil werden akzeptiert
* _FIX_: Uhrzeitfelder des Wochenplans werden beim Wechsel des Auslösers korrekt (de)aktiviert
* _FIX_: Bestehende Instanzen mit einer Schaltvariable ohne Aktion schalten weiterhin direkt (Migration)

v3.1.20250212
* _NEU_: Einstellungsmöglichkeiten um Offset-Variablen aus dem Location Control erweitert
* _FIX_: Type Hinting für Funktions- und Methodenparameter (sowie Rückgabewerte) nachgezogen
* _FIX_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _FIX_: Dokumentation vereinheitlicht

v3.0.20240908

* _NEU_: Modulumbenennung in nur noch "Zeitschaltuhr" (ohne Licht-...)
* _NEU_: Kompatibilität auf IPS 6.4 hoch gesetzt
* _FIX_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _FIX_: Namensnennung und Repo vereinheitlicht
* _FIX_: Update Style-Checks
* _FIX_: Übersetzungen überarbeitet und verbessert
* _FIX_: Dokumentation vereinheitlicht

v2.0.20220216

* _NEU_: Umschalten zwischen einem oder mehreren Geräten
* _NEU_: Eine reine boolesche Schaltvariable (ein Gerät) wird automatisch erkannt
* _NEU_: Referenzieren der Gerätevariablen hinzugefügt
* _FIX_: Globale Aktivierung bzw. Deaktivierung der Schaltung umgebaut
* _FIX_: Schaltung der Proxy Schaltvariable für Webfront korrigiert
* _FIX_: Übersetzungen erweitert bzw. korrigiert

v1.6.20220119

* _NEU_: Schalter zum manuellen aktivieren bzw. deaktivieren der Instanz (Zeitschaltuhr)
* _NEU_: Kompatibilität auf IPS 6.0 hoch gesetzt
* _NEU_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _NEU_: Konfigurationsdialog überarbeitet (v6 Möglichkeiten genutzt)

v1.5.20210625

* _FIX_: Start Bedingung korrigiert
* _FIX_: Timer Update Berechnung vereinheitlicht

v1.4.20210505

* _FIX_: Komplett neue Steuerung für die Einhaltung der zeitlichen Reihenfolge
* _NEU_: Die eingestellte Zeit kann jetzt vom Sontag auf den Montag kopiert werden

v1.3.20210426

* _FIX_: Fix für die Einhaltung der zeitlichen Reihenfolge

v1.2.20210330

* _FIX_: Umstellung auf direkte Eingabe der Uhrzeiten (kein externer Wochenplan mehr notwendig)
* _NEU_: Beachtung der zeitlichen Reihenfolge (EIN-vor-AUS) hinzugefügt

v1.1.20210326

* _NEU_: Umstellung auf frei wählbaren Ein- und Ausschaltzeitpunkt
* _NEU_: Schaltung über Tagesgrenze hinweg möglich

v1.0.20210322

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
