<?php
    require_once('../autoload.php');
    ob_start();

    User::loginCheck(false);
    
    // Expected params
    /*  q: query,
        type: resultType,
        market: userMarket,
        limit: resultLimit,
        offset: resultOffset,
        playlist_id: dp internal playlist id
    */

    // Check participation etc.
    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $playlist_id = null;
    if (!isset($_REQUEST['playlist_id'])) {
        $error_messages[] = "No playlist specified";
        $fatal_error = false;
    } else {
        $playlist_id = $_REQUEST['playlist_id'];
        unset($_REQUEST['playlist_id']); // Don't want to pass it to API
    }

    if (!empty($playlist_id)) {
        $playlist = Playlist::getById($playlist_id);
        if ($playlist == null) {
            $error_messages[] = "Playlist not found";
            $fatal_error = true;
        } else {
            $participation = Participation::findFirst([['playlist_id','=',$playlist->id],['user_id','=',$_SESSION['USER_ID']]]);
            if ($participation == null) {
                // They're not part of this playlist
                $error_messages[] = "Sorry, you have not joined this playlist. Please talk to the playlist owner to join.";
                $fatal_error = true;
            } else {
                if ($participation->removed == 1) {
                    // They've been removed
                    $error_messages[] = "Sorry, you have been removed from this playlist. Please talk to the playlist owner if you would like to be reinstated.";
                    $fatal_error = true;
                }
            }
        }
    }

    // Load data from query
    $data = $_REQUEST;

    // Make call - standard search UNLESS we're passed a spotify share link
    $reShareLink = "/^\s*https:\/\/open\.spotify\.com\/track\/([^?]+)/i";
    $matches = [];
    if (preg_match($reShareLink, $data['q'], $matches) === 1) {
        // Search by track ID
        $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_GET,"https://api.spotify.com/v1/tracks/{$matches[1]}");
        $sr->contentType = SpotifyRequest::CONTENT_TYPE_FORM_ENCODED;
        $sr->send($data);
        // Now put that result inside an items array inside a tracks object, to mimic search results
        $sr->result = <<<END_JSON
{
    "tracks": {
        "limit": 1,
        "offset": 0,
        "items": [
            {$sr->result}
        ]
    }
}
END_JSON;
    } else {
        // Search by querystring as normal
        $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_GET,"https://api.spotify.com/v1/search");
        $sr->contentType = SpotifyRequest::CONTENT_TYPE_FORM_ENCODED;
        $sr->send($data);
    }

    
    header("Expires: 0");
    ob_end_clean();

    echo $sr->result;
    die();