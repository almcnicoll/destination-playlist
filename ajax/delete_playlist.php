<?php
// Deletes a playlist
require_once('../autoload.php');
ob_start();

function safeBool($var, $context) : bool {    
    if(is_bool($var)) { return $var; }

    if(is_string($var)) {
        switch(strtolower($var)) {
            case 'true':
                return true;
            case 'false':
                return false;
            default:
                break; // Do nothing
        }
    }
    if(is_numeric($var)) {
        switch((int)$var) {
            case 0:
                return false;
            case 1:
                return true;
            default:
                break; // Do nothing
        }
    }
    throw new Exception("Invalid boolean value {$var} provided to {$context}");
}

$fatal_error = false;

$error_messages = [];
$warning_messages = [];
$info_messages = [];
if (isset($_REQUEST['error_message'])) {
    $error_messages[] = $_REQUEST['error_message'];
}

if (!isset($_REQUEST['playlist_id'])) {
    $error_messages[] = "No playlist specified";
    $fatal_error = true;
}
if (isset($_REQUEST['deleteLocal'])) {
    $deleteLocal = safeBool($_REQUEST['deleteLocal'], "deleteLocal");
} else {
    $error_messages[] = "You must specify whether to delete the local playlist entry";
    $fatal_error = true;
}
if (isset($_REQUEST['deleteFromSpotify'])) {
    $deleteFromSpotify = safeBool($_REQUEST['deleteFromSpotify'], "deleteFromSpotify");
} else {
    $error_messages[] = "You must specify whether to delete the Spotify playlist entry";
    $fatal_error = true;
}
$playlist_id = $_REQUEST['playlist_id'];

$playlist = Playlist::getById($playlist_id);
if ($playlist == null) {
    $error_messages[] = "Playlist not found";
    $fatal_error = true;
}

if ($playlist->user_id != $_SESSION['USER_ID']) {
    $error_messages[] = "You do not own this playlist!";
    $fatal_error = true;
}

$user = $playlist->getOwner();

// Return if fatal
if ($fatal_error) {
    $output = json_encode(['errors' => $error_messages]);
    ob_end_clean();
    die($output);
}

if ($deleteFromSpotify) {
    // It actually just unfollows it
    $endpoint = "https://api.spotify.com/v1/playlists/{$playlist->spotify_playlist_id}/followers";
    $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_DELETE, $endpoint);
    $sr->send();
    // Ignore any errors for now methinks // TODO - review that decision
    if ($sr->hasErrors()) {
        echo "Could not unfollow Spotify playlist: ".$sr->getErrors()."\n";
    } else {
        echo "Unfollowed Spotify playlist: OK\n";
    }
}
if ($deleteLocal) {
    try {
        $playlist->delete();
        echo "Deleted local playlist entry from db: OK\n";
    } catch (Exception $e) {
        echo "Could not delete local playlist entry from db: ".$e->getMessage()."\n";
    }
}