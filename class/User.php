<?php

class User extends Model {
    public int $authmethod_id;
    public string $identifier;
    public ?string $email;
    public ?string $display_name;
    public ?string $market;
    public ?string $image_url;

    static string $tableName = "users";
    static $fields = ['id','authmethod_id','identifier','email','display_name','market','image_url','created','modified'];

    public function setAuthmethod_id($id) {
        $this->authmethod_id = $id;
    }

    public function getAuthmethod() : ?AuthMethod {
        return AuthMethod::getById($this->authmethod_id);
    }

    public function getThumbnail() : string {
        if (empty($this->image_url)) {
            // return initial
            $html = "<div class='initial-display'>".substr($this->display_name,0,1)."</div>";
        } else {
            // return pic
            $html = "<div class='initial-display'><img src='{$this->image_url}' /></div>";
        }
        return $html;
    }

    public static function loginCheck($redirectOnFail = true) : bool {
        $config = Config::get();

        // $login_check_redirect_on_fail allows pages to redirect unauthenticated users to custom URLs (e.g. / -> /dp/intro)
        // $login_check_soft_fail allows pages to refresh tokens if needed, but not redirect to login on fail (e.g. /dp/intro page, which is valid for unauthenticated users)
        if(session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

        $currentUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        // Check if user is on developer dashboard list - we can lose this if we move to Production Mode
        $userCheckedOnList = (isset($_SESSION['USER_CHECKEDONLIST']) && ($_SESSION['USER_CHECKEDONLIST'] === true));

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
            if (($redirectOnFail !== false) && ($redirectOnFail !== 0) && ($redirectOnFail !== '0')) {
                if (($redirectOnFail === true) || ($redirectOnFail === 1) || ($redirectOnFail === '1')) {
                    header("Location: {$config['root_path']}/login.php?redirect_url=".urlencode($currentUrl));
                    //file_put_contents('redirects.log',__LINE__." Location: {$config['root_path']}/login.php?redirect_url=".urlencode($currentUrl)."\n",FILE_APPEND);
                } else {
                    header("Location: {$config['root_path']}{$redirectOnFail}");
                    //file_put_contents('redirects.log','Type: '.gettype($redirectOnFail)."\n",FILE_APPEND);
                    //file_put_contents('redirects.log',__LINE__." Location: {$config['root_path']}{$redirectOnFail}\n",FILE_APPEND);
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
                //file_put_contents('redirects.log',__LINE__." Location: {$config['root_path']}/{$method->handler}?refresh_needed=true&redirect_url=".urlencode($currentUrl)."\n",FILE_APPEND);
                die();
            }
            // Lastly - once per session - make sure the user can access the API - in dev mode, this is only possible if they've been added to the developer dashboard
            if (!$userCheckedOnList) {
                $checkUrl = "https://api.spotify.com/v1/me/tracks";
                $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_GET, $checkUrl);
                $sr->send();
                if ($sr->hasErrors()) {
                    if ($sr->http_code == 403) {
                        // Not in dev dashboard
                        $_SESSION['USER_CHECKEDONLIST'] = false;
                        //header('Location: '.$config['root_path'].'/logout.php?'.http_build_query(['error_message'=>"You need to be registered as a trial user before you can access Destination Playlist. Please contact the developer."]));
                        header('Location: '.$config['root_path'].'/account/request/403');
                        //file_put_contents('redirects.log',__LINE__.' Location: '.$config['root_path'].'/account/request/403'."\n",FILE_APPEND);
                        die();
                    } else {
                        // Some other error - ignore, but check again on next page load
                        $_SESSION['USER_CHECKEDONLIST'] = false;
                    }
                } else {
                    // All good - they're on the dashboard list
                    $_SESSION['USER_CHECKEDONLIST'] = true;
                }
            }
            // Otherwise, everything is OK! Just ensure that USER property is correctly populated as a User object
            $discard = new User(); // Ensure that User class is autoloaded
            $_SESSION['USER'] = unserialize(serialize($_SESSION['USER']));
            return true;
        }
    }
}