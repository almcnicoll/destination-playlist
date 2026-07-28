<?php
require_once('autoload.php');

// Dev-only backdoor: logs straight in as the almcnicoll user, no Spotify OAuth round-trip.
// Gated on loopback REMOTE_ADDR so it can never fire against a real client even if this file
// ends up deployed. USER_ACCESSTOKEN is a dummy value on purpose: any real Spotify API call
// (track search, playlist sync) will fail with 401 rather than silently hitting the real
// production playlist under almcnicoll's actual Spotify account.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
    http_response_code(404);
    die('Not found');
}

$method = AuthMethod::findFirst([['methodName', '=', 'spotify']]);
if ($method === null) {
    die('Spotify auth method not found');
}
$user = User::findFirst([['authmethod_id', '=', $method->id], ['identifier', '=', 'almcnicoll']]);
if ($user === null) {
    die('User almcnicoll not found locally - import it first');
}

$_SESSION['USER'] = $user;
$_SESSION['USER_ID'] = $user->id;
$_SESSION['USER_AUTHMETHOD_ID'] = $method->id;
$_SESSION['USER_ACCESSTOKEN'] = 'DEV-BACKDOOR-NO-REAL-TOKEN';
$_SESSION['USER_REFRESHNEEDED'] = time() + 86400;
$_SESSION['USER_CHECKEDONLIST'] = true;

header('Location: ' . $config['root_path'] . '/');
die();
