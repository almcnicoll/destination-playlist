<?php
// $login_check_redirect_on_fail allows pages to redirect unauthenticated users to custom URLs (e.g. / -> /dp/intro)
// $login_check_soft_fail allows pages to refresh tokens if needed, but not redirect to login on fail (e.g. /dp/intro page, which is valid for unauthenticated users)
@session_start();
if (!include_once('class/authmethod.php')) {
    require_once('../class/authmethod.php');
}

$redirect_url_if_needed = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

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
    if (empty($login_check_soft_fail) || $login_check_soft_fail===false) {
        if (empty($login_check_redirect_on_fail)) {
            header("Location: {$config['root_path']}/login.php?redirect_url=".urlencode($redirect_url_if_needed));
        } else {
            header("Location: {$config['root_path']}{$login_check_redirect_on_fail}");
        }
        die();
    }  
} else {
    // Check if our token is still valid
    $refresh_needed = (int)($_SESSION['USER_REFRESHNEEDED']);
    //die("<pre>Comparing {$refresh_needed} to ".time()."</pre>\n");
    if ($refresh_needed < time()) {
        // Call refresh mechanism
        $method = AuthMethod::getById((int)$_SESSION['USER_AUTHMETHOD_ID']);
        header("Location: {$config['root_path']}/{$method->handler}?refresh_needed=true&redirect_url=".urlencode($redirect_url_if_needed));
        die();
    }
    // Otherwise, everything is OK! Just ensure that USER property is correctly populated as a User object
    if (!include_once('class/user.php')) {
        require_once('../class/user.php');
    }
    $_SESSION['USER'] = unserialize(serialize($_SESSION['USER']));
}

$login_checked = true;