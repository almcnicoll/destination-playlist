<?php
session_start();
if (!@include_once('inc/secret.php')) {
    require_once('secret.php');
}
define('DB_HOST','localhost');
define('DB_SCHEMA','destination-playlist');
define('DB_USER','dp');
// PASSWORD IN secret.php
define('DB_CHARSET','utf8mb4');

class db {
    public function getDSN() {
        return "mysql:host=".DB_HOST.";dbname=".DB_SCHEMA.";charset=".DB_CHARSET;
    }

    public function getPDO() {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $dsn = self::getDSN();
        return new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    }
}