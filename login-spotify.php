<?php
// TODO - switch to the PKCE auth method https://developer.spotify.com/documentation/web-api/tutorials/code-pkce-flow
// TODO - build redirect_uri from a server variable or constant so all instances update when hosting changes
require_once('inc/db.php');

if (isset($_REQUEST['code'])) {
    // Callback with auth code
    //$_SESSION['SPOTIFY_AUTHCODE'] = $_REQUEST['code'];
    // Now request token
    $endpoint = "https://accounts.spotify.com/api/token";
    $options = [
        'grant_type'        =>  'authorization_code',
        'code'              =>  $_REQUEST['code'],
        'redirect_uri'      => 'http://localhost:8888/destination-playlist/login-spotify.php',
    ];
    $ch = curl_init($endpoint);
    curl_setopt_array ( $ch, array (
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $options, 
    ) );
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, SPOTIFY_CLIENTID.':'.SPOTIFY_CLIENTSECRET);
    $result = curl_exec($ch);
    
    $curlresponse = json_decode($result, true);
    var_dump($curlresponse);
    die();
} elseif (isset($_REQUEST['error'])) {
    die('Error: '.$_REQUEST['error']);
} else {
    $endpoint = "https://accounts.spotify.com/authorize";
    $options = [
        'client_id'         => SPOTIFY_CLIENTID,
        'response_type'      => 'code',
        'redirect_uri'      => 'http://localhost:8888/destination-playlist/login-spotify.php',
        /* 'state'             => '', */ // TODO - look this up and implement it sometime: https://datatracker.ietf.org/doc/html/rfc6749#section-4.1
        'scope'             =>  'user-read-playback-state user-modify-playback-state playlist-read-private playlist-read-collaborative playlist-modify-private playlist-modify-public user-library-read user-library-modify user-read-email ugc-image-upload',
        'show_dialog'       => false,
    ];
    $url = $endpoint . '?' . http_build_query($options);
    header("Location: {$url}");
    die();
}