<?php
    require_once('../autoload.php');
    ob_start();

    // Check participation etc.
    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    // Make call
    $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_GET,"https://api.spotify.com/v1/me/player/devices");

    $sr->send();
    
    header("Expires: 0");
    ob_end_clean();

    echo $sr->result;
    die();