<?php
// Leaves a playlist (if not owner)
require_once('../autoload.php');
ob_start();

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
$playlist_id = $_REQUEST['playlist_id'];

$playlist = Playlist::getById($playlist_id);
if ($playlist == null) {
    $error_messages[] = "Playlist not found";
    $fatal_error = true;
}
if ($playlist->user_id == $_SESSION['USER_ID']) {
    $error_messages[] = "You cannot leave a playlist that you created!";
    $fatal_error = true;
}

$participation = Participation::findFirst(
    ['user_id', '=', $_SESSION['USER_ID']],
    ['playlist_id', '=', $playlist_id],
);
if ($participation == null) {
    $error_messages[] = "You cannot leave a playlist that you haven't joined!";
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
        $participation->delete();
        echo "Removed user from playlist in local db: OK\n";
    } catch (Exception $e) {
        echo "Could not remove user from playlist in local db: ".$e->getMessage()."\n";
    }
}