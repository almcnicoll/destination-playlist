<?php

class Config {
    private static $__config = [];

    private static function retrieveSecrets() {
        $config = [];
        if (!@include_once('inc/secret.php')) {
            if (!@include_once('../inc/secret.php')) {
                if (!@include_once('../../inc/secret.php')) {
                    require_once('../../../inc/secret.php');
                }
            }
        }
        self::$__config += $config;
    }

    private static function retrieveLocalConfig() {
        $config = [];
        if (!@include_once('inc/config.local.php')) {
            if (!@include_once('../inc/config.local.php')) {
                if (!@include_once('../../inc/config.local.php')) {
                    @include_once('../../../inc/config.local.php');
                }
            }
        }
        self::$__config += $config;
    }

    public static function init() {
        self::retrieveSecrets();
        self::retrieveLocalConfig();
        // Add any non-local, non-secret config here in the form:
        // self::$__config['variable_key'] = 'variable value';
    }

    public static function get() {
        return self::$__config;
    }
}
Config::init();