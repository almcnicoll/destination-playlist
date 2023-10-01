<?php

class PageInfo {
    
    public const AUTH_NEVER = 0;
    public const AUTH_EARLY = 1;
    public const AUTH_LATE = 2;

    public int $authSetting = self::AUTH_EARLY;
    public $redirectOnFail = true; // Values: false (don't redirect - "soft fail"), true (redirect to login page), URL (string)

    public function __construct($authSetting = self::AUTH_EARLY, $redirectOnFail = true) {
        $this->authSetting = $authSetting;
        $this->redirectOnFail = $redirectOnFail;
    }

    public static function get($stub) : PageInfo {
        $config = Config::get();
        //var_dump($config['pageinfo']);
        //die();
        if (array_key_exists($stub, $config['pageinfo'])) {
            // We have page config for this page
            $pageinfo = $config['pageinfo'][$stub];
            return $pageinfo;
        } else {
            return new PageInfo();
        }
    }
}