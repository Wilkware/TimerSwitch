# Zeitschaltuhr (Timer Switch)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-3.1.20250212-orange.svg?style=flat-square)](https://github.com/Wilkware/TimerSwitch)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/TimerSwitch/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/TimerSwitch/actions)

Dieses Modul ermöglicht das Schalten eines Gerätes (Variable und/oder Skripts) in Abhängigkeit von Uhrzeit und/oder des täglichen Sonnenganges.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in Symcon](#user-content-4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Darstellungen](#user-content-5-statusvariablen-und-darstellungen)
6. [Visualisierung](#user-content-6-visualisierung)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

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
* Schaltvariable muss nicht eine Aktionsvariable sein, sondern kann auch einfach eine boolesche Variable sein.
* Option das Einschalten nur zu erlauben, wenn sich die Zeiten nicht überschneiden (zeitlich korrekte Abfolge, AN-vor-AUS).
* Statusvariable als Proxy-Schalter, z.B. für Verwendung im WebFront.
* Schalten kann über mehrere Tage hinweg organisiert werden (gezielter Einsatz des täglichen Zeitplanes).

### 2. Voraussetzungen

* Symcon ab Version 6.4

### 3. Installation

* Über den Modul Store das Modul _Zeitschaltuhr_ installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/TimerSwitch` oder `git://github.com/Wilkware/TimerSwitch.git`

### 4. Einrichten der Instanzen in Symcon

* Unter "Instanz hinzufügen" ist das _Zeitschaltuhr_-Modul unter dem Hersteller '(Geräte)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Schaltung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
An /Aus               | Schalter zum Aktivieren bzw. Deaktivieren der gesamten Schaltung, z.B. Weihnachtsbeleuchtung nur im Winter ;-)

> Zeitssteuerung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Auslöser Einschalten  | Auswahlmöglichkeiten: Aus; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (An); Offsets
Auslöser Ausschalten  | Auswahlmöglichkeiten: ; Sonnenaufgang oder -untergang; zivile, nautische oder astronomische Dämmerung; Wochenplan (Aus); Offsets
(Zeitplan)            | Hinterlegung einer täglichen Uhrzeit für AN & AUS (Montag - Sonntag)

> Geräte ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Geräteanzahl          | Auswahl bzw. Umschalter zwischen einzelnen und mehreren Geräten
Schaltvariable        | Schalt(Aktions-)variable (ein Gerät)
Schaltvariablen       | Liste von Geräten (mehrere Geräte)
Skript                | Auszuführendes Skript (Status true/false wird als Array 'State' übergeben)

> Einstellungen ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariable nur ein- bzw. ausschalten wenn zeitliche Abfolge korrekt ist (nur in Verbindung mit einem Wochenplan)! | true/false
Zusätzlich noch eine normale Schaltervariable anlegen (z.B. für Webfront)? | true/false

### 5. Statusvariablen und Profile

Es werden keine zusätzlichen Statusvariablen/Profile benötigt.

### 6. Visualisierung

Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

### 7. PHP-Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 8. Versionshistorie

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
