<?php
// Absolute path to the root directory of destination playlist. No trailing slash.
$config['root_path'] = 'http://localhost:8888/destination-playlist';

// DATABASE CONFIG
// TO DO - switch these to $config at some point
/*
define('DB_HOST','localhost');
define('DB_SCHEMA','destination-playlist');
define('DB_USER','dp');
// PASSWORD IN secret.php
define('DB_CHARSET','utf8mb4');
*/

$config['DB_HOST']      = 'localhost';
$config['DB_SCHEMA']    ='destination-playlist';
$config['DB_USER']      = 'dp';
// PASSWORD IN secret.php
$config['DB_CHARSET']   = 'utf8mb4';