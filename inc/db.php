<?php
session_start();
if (!@include_once('inc/secret.php')) { require_once('../inc/secret.php'); }
define('DB_HOST','localhost');
define('DB_SCHEMA','destination-playlist');
define('DB_USER','dp');
// PASSWORD IN secret.php
define('DB_CHARSET','utf8mb4');

class db {
    static function getDSN() {
        return "mysql:host=".DB_HOST.";dbname=".DB_SCHEMA.";charset=".DB_CHARSET;
    }

    public static function getPDO() {
        if (isset($_SESSION['DB_PDO'])) { return $_SESSION['DB_PDO']; }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $dsn = db::getDSN();
        $_SESSION['DB_PDO'] = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        return $_SESSION['DB_PDO'];
    }
}