<?php
class Autoloader
{
    public static function register()
    {
        global $config;
        spl_autoload_register(function ($class) {
            //global $config;
            $file = 'class'.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
            if (file_exists($file)) {
                require $file;
                return true;
            }
            return false;
        });
    }
}
Autoloader::register();

$config = Config::get(); // Retrieve config while we're here