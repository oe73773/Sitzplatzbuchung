<?php

$config = [];

# Database connection:

$config['dbType'] = 'mysql';        # First part of the Data Source Name (DSN), see documentation of PDO::__construct
$config['dbHost'] = 'localhost';    # Hostname or IP address of the database server
$config['dbName'] = 'seatbooking';  # Name of the database
$config['dbUsername'] = 'root';     # Username to connect to the database
$config['dbPassword'] = '';         # Password to connect to the database

# Title:

$config['isProductionInstance'] = true;
$config['instanceTitle'] = 'Sitzplatzbuchung';
$config['instanceHeadline'] = 'Sitzplatzbuchung';
$config['textAfterTitle'] = '...Logo...';
# $config['faviconPath'] = 'public/favicon.png';

# Static texts:

$config['mainPageText'] = <<<EOD
...Hinweise...
EOD;

$config['helpPageText'] = <<<EOD
...Hilfe-Text...
EOD;

$config['footerText'] = <<<EOD
...Fußzeile...
EOD;

# Locale:

# date_default_timezone_set('Europe/Berlin');
# setlocale(LC_TIME, 'de_DE');
