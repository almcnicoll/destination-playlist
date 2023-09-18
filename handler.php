<?php
session_start();
/* Do these steps manually, as login-check being called much later */
require_once('inc/config.php');
require_once('class/user.php');
if (isset($_SESSION['USER'])) { $_SESSION['USER'] = unserialize(serialize($_SESSION['USER'])); }
/* END BLOCK */

$page_parts = explode('/', $_GET['params']);
$params = [];

// If no params, redirect to index
if (count($page_parts)==0) {
    header('Location: ./');
    die();
} elseif (count($page_parts)==1) {
    $page_parts[] = 'index';
} elseif (count($page_parts)>2) {
    // Move extraneous page parts to params 
    while (count($page_parts)>2) {
        array_unshift($params, (array_pop($page_parts)) );
    }
}
$page = "pages/{$page_parts[0]}_{$page_parts[1]}.php";

ob_start(); // Required, as we're including login-check further down
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (substr($config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Destination Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="<?= $config['root_path'] ?>/css/app.css" rel="stylesheet">
</head>
<body>
    <!-- <script src="https://code.jquery.com/jquery-3.7.0.slim.js" integrity="sha256-7GO+jepT9gJe9LB4XFf8snVOjX3iYNb0FHYr5LI1N5c=" crossorigin="anonymous"></script> -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>

<?php

require_once('inc/header.php');

if (!@include_once($page)) {
    http_response_code(404);
    echo "<h1>You’ve Lost That Lovin’ Feelin’...</h1>\n";
    echo "<h2>Or, more accurately, you've clicked a wrong link.</h2>\n";
    echo "<p>Look, it's most likely our fault - sorry. Or you've mistyped something. Who knows?</p>\n";
    echo "<p class='text-body-secondary'><small>{$page}</small></p>\n";
    ob_end_flush();
    die();
} else {
    require_once('inc/login_check.php'); // Do it here because the included file might specify that login isn't required
    ob_end_flush();
}
?>
</body>
</html>