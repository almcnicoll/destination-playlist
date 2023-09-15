<?php
// Absolute path to the root directory of destination playlist. No trailing slash.
$config['root_path'] = 'http://localhost:8888/destination-playlist';
// Local filesystem path to the root directory of destination playlist. No trailing slash.
$config['local_root'] = 'C:/BitNami/wampstack8/apache2/htdocs/destination-playlist';

// DATABASE CONFIG
$config['DB_HOST']      = 'localhost';
$config['DB_SCHEMA']    ='destination-playlist';
$config['DB_USER']      = 'dp';
$config['DB_PORT']      = 3306;
//$config['DB_PORT']      = 4040; // Local SQL profiler
// PASSWORD MUST BE SET IN secret.php, in the format $config['DB_PASSWORD'] = '';
$config['DB_CHARSET']   = 'utf8mb4';