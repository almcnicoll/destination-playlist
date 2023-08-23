<?php
if (!include_once('class/authmethod.php')) {
    require_once('../class/authmethod.php');
}

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
    header("Location: ./login.php");
    die();
} else {
    // Check if our token is still valid
    $refresh_needed = (int)($_SESSION['USER_REFRESHNEEDED']);
    if ($refresh_needed < time()) {
        // Call refresh mechanism
        $method = AuthMethod::getById((int)$_SESSION['USER_AUTHMETHOD_ID']);
        header("Location: {$method->handler}?refresh_needed=true&redirect_url=".urlencode($_SERVER['REQUEST_URI']));
        die();
    }
    // Otherwise, everything is OK! Just ensure that USER property is correctly populated as a User object
    if (!include_once('class/user.php')) {
        require_once('../class/user.php');
    }
    $_SESSION['USER'] = unserialize(serialize($_SESSION['USER']));
}