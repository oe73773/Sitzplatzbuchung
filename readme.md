Sitzplatzbuchung
================

*Sitzplatzbuchung* ist eine einfache Webanwendung, mir der sich Teilnehmergruppen zu einer Veranstaltung vorab anmelden können. Das System dient dazu, das Überschreiten der Kapazität zu verhindern und das Erstellen der Anwesenheitsliste zu vereinfachen. Ziel ist die Unterstützung von z. B. Kirchengemeinden bei der Planung von Präsenzveranstaltungen während den Coronabeschränkungen.


Demo-Installation
-----------------

https://missionsgemeinde.de/demo_sitzplatzbuchung/


Projektstatus
-------------

Die grunsätzliche Funktionen sind fertiggestellt.<br>
Die Anwendung wird im produktiven Betrieb eingesetzt.


Features
--------

- Hosting auf eigenem Server
- Öffentliche Startseite zur Anmeldung zu Veranstaltungen
- Eine Buchung umfasst die Namen der Personen eines Haushalts und eine Telefonnummer
- Pro Gerät und Veranstaltung ist genau eine Buchung möglich
- Stornierung vom selben Gerät
- Definierbarer Buchungszeitraum pro Veranstaltung
- Meherere Veranstaltungen können gleichzeit zur Buchung bereitstehen
- Anzeige der Teilnehmer, der freien Sitzplätze und der eigenen Buchung
- Limitierung der Teilnehmer auf eine fixe Anzahl
- Limitierung der Teilnehmer durch Anzahl die im Raum zur Verfügung stehenden Sitzreihen (3 Stühle Abstand zwischen Teilnehmergruppen)
- Erstellung einer Anwesenheitsliste
- automatisches Neuladen der Webseite bei Änderungen
- deutschsprachige Benutzeroberfläche
- Titel, Logo, Fußzeile und Hinweistexte anpassbar
- für mobile Geräte geeignet
- minimalistisches System ("Do One Thing and Do It Well")


Technik
-------

### Voraussetzungen

Webserver mit PHP und MariaDB/MySQL-Datenbank.<br>
Getestete PHP-Version: 7.3 und 7.4.


### Verwendete Frameworks und Bibliotheken

keine


### Installation

**Schritt 1**<br>
Erstelle eine Datenbank mit Hilfe der Datei ``create-database.sql``.<br>
Ersetze ``seatbooking`` durch den gewünschten Datenbanknamen.

**Schritt 2**<br>
Kopiere die Datei ``config.template.php`` nach ``config.php`` und passe den Inhalt an.

**Schritt 3**<br>
Rufe die Webanwendung im Webbrowser auf.<br>
Das erste Gerät erhält automatisch Administrator-Berechtigung.


Support
-------

Bei Fragen zur Software:
- E-Mail an trustable@disroot.org
- [GitHub-Issue anlegen](https://github.com/MissionsgemeindeWeinstadt/Sitzplatzbuchung/issues/new)


Lizenz
------

*Sitzplatzbuchung* ist freie Software unter der [GNU General Public License version 3](https://opensource.org/licenses/GPL-3.0) (GPLv3).<br>
Copyright 2020 Simon Krauter
