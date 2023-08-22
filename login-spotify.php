<?php
// TODO - switch to the PKCE auth method https://developer.spotify.com/documentation/web-api/tutorials/code-pkce-flow
// TODO - build redirect_uri from a server variable or constant so all instances update when hosting changes
require_once('inc/db.php');

if (isset($_REQUEST['code'])) {
    // This branch is callback with auth code
    //$_SESSION['SPOTIFY_AUTHCODE'] = $_REQUEST['code'];
    
    // Now request token
    $endpoint = "https://accounts.spotify.com/api/token";
    $options = http_build_query([
        'grant_type'        =>  'authorization_code',
        'code'              =>  $_REQUEST['code'],
        'redirect_uri'      => 'http://localhost:8888/destination-playlist/login-spotify.php',
    ]);
    $ch = curl_init($endpoint);
    curl_setopt_array ( $ch, array (
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $options, 
    ) );
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, SPOTIFY_CLIENTID.':'.SPOTIFY_CLIENTSECRET);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    $authresponse = json_decode($result, true);
    curl_close($ch);
    echo "<pre>".print_r($authresponse,true)."</pre>\n";
    
    // Now request user data
    $endpoint = "https://api.spotify.com/v1/me";
    $ch = curl_init($endpoint);
    curl_setopt_array ( $ch, array (
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$authresponse['access_token']],
    ) );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    $userresponse = json_decode($result, true);
    $displayname = $userresponse['display_name'];
    $email = $userresponse['email'];
    $userid = $userresponse['id'];
    curl_close($ch);
    
    
    
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
        'scope'             =>  'user-read-playback-state user-modify-playback-state playlist-read-private playlist-read-collaborative playlist-modify-private playlist-modify-public user-library-read user-library-modify user-read-private user-read-email ugc-image-upload',
        'show_dialog'       => false,
    ];
    $url = $endpoint . '?' . http_build_query($options);
    header("Location: {$url}");
    die();
}