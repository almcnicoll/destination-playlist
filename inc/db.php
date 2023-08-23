<?php
session_start();
if (!is_writable(session_save_path())) {
    die('Cannot write to session path "'.session_save_path().'"'); 
} /*else {
    echo "<pre>Session #".session_id()."\n".print_r(session_get_cookie_params(),true)."</pre>"; // TODO - remove this
}*/

if (!@include_once('inc/secret.php')) { require_once('../inc/secret.php'); }
define('DB_HOST','localhost');
define('DB_SCHEMA','destination-playlist');
define('DB_USER','dp');
// PASSWORD IN secret.php
define('DB_CHARSET','utf8mb4');

class db {
    private static $pdo; 

    static function getDSN() {
        return "mysql:host=".DB_HOST.";dbname=".DB_SCHEMA.";charset=".DB_CHARSET;
    }

    public static function getPDO() {
        if (isset(static::$pdo) && !empty(static::$pdo)) { return static::$pdo; }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $dsn = db::getDSN();
        static::$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        return static::$pdo;
    }
}