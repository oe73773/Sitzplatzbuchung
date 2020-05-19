Sitzplatzbuchung
================

*Sitzplatzbuchung* ist eine einfache Webanwendung, mir der sich Teilnehmergruppen zu einer Veranstaltung vorab anmelden können.
Die angemeldeten Teilnehmergruppen werden auf die im Raum zur Verfügung stehenden Sitzreihen gerechnet um eine Überbuchung zu verhindern.

Voraussetzungen
---------------

Webserver mit PHP und MariaDB/MySQL-Datenbank.<br>
Getestete PHP-Version: 7.3.


Verwendete Frameworks und Bibliotheken
--------------------------------------

keine


Installation
------------

**Schritt 1**<br>
Erstelle eine Datenbank mit Hilfe der Datei ``create-database.sql``.<br>
Ersetze ``seatbooking`` durch den gewünschten Datenbanknamen.

**Schritt 2**<br>
Kopiere die Datei ``config.template.php`` nach ``config.php`` und passe den Inhalt an.

**Schritt 3**<br>
Rufe die Webanwendung im Webbrowser auf.<br>
Der erste Benutzer erhält automatisch Administrator-Berechtigung.


Lizenz
------

*Sitzplatzbuchung* ist freie Software unter der [GNU General Public License version 3](https://opensource.org/licenses/GPL-3.0) (GPLv3).<br>
Copyright 2020 Simon Krauter
