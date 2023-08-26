<?php
session_start();
if (!include_once('class/authmethod.php')) {
    require_once('../class/authmethod.php');
}

// $get_back will be set to ../ or empty string if we are using handler.php to manage pages deeper than root level

if(!(
 isset($_SESSION['USER']) &&
 isset($_SESSION['USER_ID']) &&
 isset($_SESSION['USER_AUTHMETHOD_ID']) &&
 isset($_SESSION['USER_ACCESSTOKEN']) &&
 isset($_SESSION['USER_REFRESHTOKEN']) &&
 isset($_SESSION['USER_REFRESHNEEDED'])
 )) {
    // Need to log in
    echo "<pre>Session:\n".print_r($_SESSION,true)."</pre>";
    if(!isset($get_back)) { $get_back = ''; }
    header("Location: {$get_back}./login.php");
    die();
} else {
    // Check if our token is still valid
    $refresh_needed = (int)($_SESSION['USER_REFRESHNEEDED']);
    //die("<pre>Comparing {$refresh_needed} to ".time()."</pre>\n");
    if ($refresh_needed < time()) {
        // Call refresh mechanism
        $method = AuthMethod::getById((int)$_SESSION['USER_AUTHMETHOD_ID']);
        if(!isset($get_back)) { $get_back = ''; }
        //header("Location: {$get_back}{$method->handler}?refresh_needed=true&redirect_url=".urlencode($_SERVER['REQUEST_URI']));
        header("Location: {$config['root_path']}/{$method->handler}?refresh_needed=true&redirect_url=".urlencode($_SERVER['REQUEST_URI']));
        die();
    }
    // Otherwise, everything is OK! Just ensure that USER property is correctly populated as a User object
    if (!include_once('class/user.php')) {
        require_once('../class/user.php');
    }
    $_SESSION['USER'] = unserialize(serialize($_SESSION['USER']));
}