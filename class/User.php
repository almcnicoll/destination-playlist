<?php

class User extends Model {
    public int $authmethod_id;
    public string $identifier;
    public ?string $email;
    public ?string $display_name;
    public ?string $market;

    static string $tableName = "users";
    static $fields = ['id','authmethod_id','identifier','email','display_name','market','created','modified'];

    public function setAuthmethod_id($id) {
        $this->authmethod_id = $id;
    }

    public function getAuthmethod() : ?AuthMethod {
        return AuthMethod::getById($this->authmethod_id);
    }

    public static function loginCheck($redirectOnFail = true) : bool {
        $config = Config::get();

        // $login_check_redirect_on_fail allows pages to redirect unauthenticated users to custom URLs (e.g. / -> /dp/intro)
        // $login_check_soft_fail allows pages to refresh tokens if needed, but not redirect to login on fail (e.g. /dp/intro page, which is valid for unauthenticated users)
        if(session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

        $currentUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        if(!(
        isset($_SESSION['USER']) &&
        isset($_SESSION['USER_ID']) &&
        isset($_SESSION['USER_AUTHMETHOD_ID']) &&
        isset($_SESSION['USER_ACCESSTOKEN']) &&
        isset($_SESSION['USER_REFRESHTOKEN']) &&
        isset($_SESSION['USER_REFRESHNEEDED'])
        )) {
            // Need to log in
            //echo "<pre>Session:\n".print_r($_SESSION,true)."</pre>";
            if ($redirectOnFail !== false) {
                if ($redirectOnFail === true) {
                    header("Location: {$config['root_path']}/login.php?redirect_url=".urlencode($currentUrl));
                } else {
                    header("Location: {$config['root_path']}{$redirectOnFail}");
                }
                die();
            }
            return false;
        } else {
            // Check if our token is still valid
            $refresh_needed = (int)($_SESSION['USER_REFRESHNEEDED']);
            //die("<pre>Comparing {$refresh_needed} to ".time()."</pre>\n");
            if ($refresh_needed < time()) {
                // Call refresh mechanism
                $method = AuthMethod::getById((int)$_SESSION['USER_AUTHMETHOD_ID']);
                header("Location: {$config['root_path']}/{$method->handler}?refresh_needed=true&redirect_url=".urlencode($currentUrl));
                die();
            }
            // Otherwise, everything is OK! Just ensure that USER property is correctly populated as a User object
            $discard = new User(); // Ensure that User class is autoloaded
            $_SESSION['USER'] = unserialize(serialize($_SESSION['USER']));
            return true;
        }
    }
}