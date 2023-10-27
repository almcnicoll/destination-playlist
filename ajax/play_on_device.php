<?php
    require_once('../autoload.php');
    ob_start();

    User::loginCheck(false);

    // Check participation etc.
    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    // Get spotify ID for playlist
    $playlist = Playlist::getById($_REQUEST['playlist_id']);
    if ($playlist == null) {
        $error_messages[] = "Cannot retrieve playlist from Spotify";
    } else {
        // Make call
        $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT,"https://api.spotify.com/v1/me/player/play?device_id=".$_REQUEST['device_id']);
        $sr->contentType = SpotifyRequest::CONTENT_TYPE_JSON;
        
        $data = [
            'context_uri' => "spotify:playlist:{$playlist->spotify_playlist_id}",
        ];

        $sr->send($data);

        if ($sr->hasErrors()) {
            $error_messages[] = $sr->getErrors();
        } else {
            header("Expires: 0");
            ob_end_clean();
            echo json_encode(['result' => 'OK']);
            die();
        }
    }
    
    header("Expires: 0");
    http_response_code($sr->http_code);
    ob_end_clean();

    echo $sr->result;
    die();