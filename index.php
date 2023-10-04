<?php
require_once('autoload.php');

/* Do these steps manually, as login-check may be called much later */
/*
$discard = new User(); // To force autoloading of User class
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_SESSION['USER'])) { $_SESSION['USER'] = unserialize(serialize($_SESSION['USER'])); }
}
*/
/* END BLOCK */

if(isset($_GET['params'])) {
    $page_parts = explode('/', $_GET['params']);
} else {
    $page_parts = [];
}
$params = [];

// If no params, treat as index
if (count($page_parts)==0 || (empty($page_parts[0]))) {
    $stub = 'index';
} elseif (count($page_parts)==1) {
    $page_parts[] = 'index';
    $stub = "{$page_parts[0]}_{$page_parts[1]}";
} elseif (count($page_parts)>=2) {
    // Move extraneous page parts to params 
    while (count($page_parts)>2) {
        array_unshift($params, (array_pop($page_parts)) );
    }
    $stub = "{$page_parts[0]}_{$page_parts[1]}";
}

// Get information about the page we're serving
$pageinfo = PageInfo::get($stub);
//var_dump($config);
//var_dump($stub);
//var_dump($pageinfo);
//die();
// Check if we need to authenticate now
if ($pageinfo->authSetting === PageInfo::AUTH_EARLY) {
    if (empty($_REQUEST)) {
        User::loginCheck($pageinfo->redirectOnFail);
    } else {
        User::loginCheck($pageinfo->redirectOnFail.'?'.http_build_query($_REQUEST));
    }
}


$page = "pages/{$stub}.php";

ob_start(); // Required, as we're including login-check further down
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (strpos(strtolower($_SERVER['SERVER_NAME']), 'localhost') === false):
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Q5ZQ6ZE62S"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-Q5ZQ6ZE62S');
    </script>
    <!-- END GOOGLE -->
    <?php
    else:
    echo "<!-- NO ANALYTICS ON LOCALHOST -->\n";
    endif;
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (substr($config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Destination Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="<?= $config['root_path'] ?>/css/app.css" rel="stylesheet">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>

<?php

if (!@include_once('inc/header.php')) {
    if (!@include_once('../inc/header.php')) {
        if (!@include_once('../../inc/header.php')) {
            require_once('../../../inc/header.php');
        }
    }
}

if (!@include_once($page)) {
    http_response_code(404);
    echo "<h1>You’ve Lost That Lovin’ Feelin’...</h1>\n";
    echo "<h2>Or, more accurately, you've clicked a wrong link.</h2>\n";
    echo "<p>Look, it's most likely our fault - sorry. Or you've mistyped something. Who knows?</p>\n";
    echo "<p class='text-body-secondary'><small>{$page}</small></p>\n";
    ob_end_flush();
    die();
} else {
    if ($pageinfo->authSetting == PageInfo::AUTH_LATE) {
        require_once('inc/login_check.php');
    }
    ob_end_flush();
}

// Check if we need to authenticate now
if ($pageinfo->authSetting === PageInfo::AUTH_LATE) {
    if (empty($_REQUEST)) {
        User::loginCheck($pageinfo->redirectOnFail);
    } else {
        User::loginCheck($pageinfo->redirectOnFail.'?'.http_build_query($_REQUEST));
    }
}
?>
</body>
</html>