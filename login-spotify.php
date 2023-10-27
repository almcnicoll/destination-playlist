<?php
require_once('autoload.php');

// TODO - switch to the PKCE auth method https://developer.spotify.com/documentation/web-api/tutorials/code-pkce-flow
// TODO - build redirect_uri from a server variable or constant so all instances update when hosting changes
$discard = new User(); // ensure User class loaded

if (isset($_REQUEST['refresh_needed'])) {
    // This branch is for refreshing the access token
    $endpoint = "https://accounts.spotify.com/api/token";

    $refreshData = [
        'grant_type'        =>  'refresh_token',
        'refresh_token'     =>  $_SESSION['USER_REFRESHTOKEN'],
    ];

    /*$options = http_build_query($refreshData);
    $ch = curl_init($endpoint);
    curl_setopt_array ( $ch, array (
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $options, 
    ) );
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $config['SPOTIFY_CLIENTID'].':'.$config['SPOTIFY_CLIENTSECRET']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);*/

    $sr = new SpotifyRequest(SpotifyRequest::TYPE_OAUTH_REFRESHTOKEN, SpotifyRequest::ACTION_POST, $endpoint);
    $sr->send(http_build_query($refreshData));

    //$result = curl_exec($ch);
    if ($sr->hasErrors()) { // Can't refresh token for some reason
        //file_put_contents('redirects.log', 'Refresh errors: '.print_r($sr->getErrors(),true)."\n", FILE_APPEND);
        if (isset($_REQUEST['redirect_url'])) {
            header("Location: ".$config['root_path'].'/login.php?'.http_build_query(['redirect_url'=>$_REQUEST['redirect_url']]));
        } else {
            header("Location: ".$config['root_path'].'/login.php');
        }        
    }
    $authresponse = json_decode($sr->result, true);
    if($sr->hasErrors()) {
        // Give up on refresh - try re-auth
        //error_log("Refresh error: ".$sr->getErrors());
        $requestVars = $_REQUEST;
        unset($requestVars['refresh_needed']);
        header('Location: '.$_SERVER['REQUEST_URI'].'?'.http_build_query($requestVars));
        //echo 'Redirect to: '.$_SERVER['REQUEST_URI'].'?'.http_build_query($requestVars);
        die();
    }
    if ($authresponse == null) {
        error_log(__FILE__.':'.__LINE__." Can't JSON-decode response: ".print_r($sr->result, true));
        error_log(__FILE__.':'.__LINE__." Data sent: ".print_r($options, true));
    }
    $_SESSION['USER_ACCESSTOKEN'] = $authresponse['access_token'];
    if (isset($authresponse['refresh_token'])) { $_SESSION['USER_REFRESHTOKEN'] = $authresponse['refresh_token']; }
    $_SESSION['USER_REFRESHNEEDED'] = time() + (int)$authresponse['expires_in'] - (5*60); // Set expiry five mins early
    session_write_close();
    
    if (isset($_REQUEST['redirect_url'])) {
        header("Location: {$_REQUEST['redirect_url']}");
    } else {
        header("Location: ./");
    }
    die();

} elseif (isset($_REQUEST['code'])) {
    // This branch is callback with auth code
    //$_SESSION['SPOTIFY_AUTHCODE'] = $_REQUEST['code'];
    
    // Now request token
    $endpoint = "https://accounts.spotify.com/api/token";
    $options = http_build_query([
        'grant_type'        =>  'authorization_code',
        'code'              =>  $_REQUEST['code'],
        'redirect_uri'      => $config['root_path'].'/login-spotify.php',
    ]);
    $ch = curl_init($endpoint);
    curl_setopt_array ( $ch, array (
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $options, 
    ) );
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $config['SPOTIFY_CLIENTID'].':'.$config['SPOTIFY_CLIENTSECRET']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    $authresponse = json_decode($result, true);
    curl_close($ch);
    if (isset($authresponse['error'])) {
        header("Location: ./login.php?error={$authresponse['error_description']}");
        die();
    }
    //echo "<pre>".print_r($authresponse,true)."</pre>\n";
    
    // Now request user data
    $endpoint = "https://api.spotify.com/v1/me";
    $ch = curl_init($endpoint);
    curl_setopt_array ( $ch, array (
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$authresponse['access_token']],
    ) );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    $userresponse = json_decode($result, true);
    if ($userresponse == null) { error_log(print_r($result,true)); }
    $displayname = $userresponse['display_name'];
    $email = $userresponse['email'];
    $userid = $userresponse['id'];
    $market = $userresponse['country'];
    $images = $userresponse['images'];
    curl_close($ch);
    //echo "<pre>".print_r($userresponse,true)."</pre>\n";
    
    // Get correct auth method
    $methods = AuthMethod::find([['methodName','=','spotify'],]);
    if (count($methods)!=1) { throw new Exception("Couldn't find spotify auth method"); }

    // Look up user
    $users = User::find([['authmethod_id','=',$methods[0]->id],['identifier','=',$userid]]);
    if (count($users)==0) {
        // Need to create user
        $user = new User();
        $user->setAuthmethod_id($methods[0]->id);
        $user->identifier = $userid;
        $user->email = $email;
        $user->display_name = $displayname;
        $user->market = $market;
        if (!empty($images)) {
            $user->image_url = $images[0]['url'];
        }
        $user->save();
    } else {
        // Refresh market and profile image
        $user = $users[0];
        $user->market = $market;
        if (empty($images)) {
            $user->image_url = null;
        } else {
            $user->image_url = $images[0]['url'];
        }
        $user->save();
    }
    $_SESSION['USER_ID'] = $user->id;
    $_SESSION['USER'] = $user;
    $_SESSION['USER_AUTHMETHOD_ID'] = $methods[0]->id;
    $_SESSION['USER_ACCESSTOKEN'] = $authresponse['access_token'];
    $_SESSION['USER_REFRESHTOKEN'] = $authresponse['refresh_token'];
    $_SESSION['USER_REFRESHNEEDED'] = time() + (int)$authresponse['expires_in'] - (5*60); // Set expiry five mins early
    //echo "<pre>Session:\n".print_r($_SESSION,true)."</pre>";
    session_write_close();
    if (isset($_SESSION['redirect_url_once'])) {
        header('Location: '.$_SESSION['redirect_url_once']);
        unset($_SESSION['redirect_url_once']);
    } else {
        header('Location: ./');
    }
    die();
} elseif (isset($_REQUEST['error'])) {
    // This branch is for if the authorization process throws an error
    die('Error: '.$_REQUEST['error']);
} else {
    if (isset($_REQUEST['redirect_url'])) {
        $_SESSION['redirect_url_once'] = $_REQUEST['redirect_url'];
    }
    // This branch is for starting the authorization process, sometimes with user interaction needed
    $endpoint = "https://accounts.spotify.com/authorize";
    $options = [
        'client_id'         => $config['SPOTIFY_CLIENTID'],
        'response_type'      => 'code',
        'redirect_uri'      => $config['root_path'].'/login-spotify.php',
        /* 'state'             => '', */ // TODO - look this up and implement it sometime: https://datatracker.ietf.org/doc/html/rfc6749#section-4.1
        'scope'             =>  'user-read-playback-state user-modify-playback-state playlist-read-private playlist-read-collaborative playlist-modify-private playlist-modify-public user-library-read user-library-modify user-read-private user-read-email ugc-image-upload',
        'show_dialog'       => false,
    ];
    //var_dump($options); die();
    $url = $endpoint . '?' . http_build_query($options);
    session_write_close();
    header("Location: {$url}");
    die();
}