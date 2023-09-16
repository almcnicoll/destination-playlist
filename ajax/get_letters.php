<?php
    // Returns the current participant list for the playlist
    $time_start = microtime(true);
    ob_start();
    // Include Participation class
    if (!@include_once('class/participation.php')) {
        if (!@include_once('../class/participation.php')) {
            require_once('../../class/participation.php');
        }
    }
    // Include Letter class
    if (!@include_once('class/letter.php')) {
        if (!@include_once('../class/letter.php')) {
            require_once('../../class/letter.php');
        }
    }

    $fatal_error = false;

    $error_messages = [];
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

    // If we're the playlist owner, see if there's any changes to push to Spotify
    if ($playlist->user_id == $_SESSION['USER_ID']) {
        if (empty($_SESSION['last_updates_check'])) { $_SESSION['last_updates_check'] = $playlist->created; }

        $sqlCheckIfUpdatesNeeded = <<<END_SQL
        SELECT COUNT(id) AS c
        FROM letters
        WHERE playlist_id = :playlistid
        AND modified > :checkdate
        AND user_id <> :userid
        ;
END_SQL;
        $paramsCheckIfUpdatesNeeded = [
            'playlistid' => $playlist_id,
            'checkdate'  => $_SESSION['last_updates_check'],
            'userid'     => $_SESSION['USER_ID'],
        ];

        $dbCheckIfUpdatesNeeded = db::getPDO();
        $stmtCheckIfUpdatesNeeded = $dbCheckIfUpdatesNeeded->prepare($sqlCheckIfUpdatesNeeded);
        $stmtCheckIfUpdatesNeeded->execute($paramsCheckIfUpdatesNeeded);
        $result = $stmtCheckIfUpdatesNeeded->fetch(PDO::FETCH_ASSOC);
        if ($result !== false) {
            // We might have updates to do
            if ($result['c'] > 0) {
                // Update the playlist
                $sqlGetTracks = <<<END_SQL
                SELECT CONCAT('spotify:track:',GROUP_CONCAT(spotify_track_id ORDER BY id SEPARATOR ',spotify:track:')) AS tracks FROM letters
                WHERE spotify_track_id IS NOT NULL
                AND playlist_id = :playlist_id
                GROUP BY playlist_id
                ;
END_SQL;
                $paramsGetTracks = [
                    'playlist_id' => $playlist_id,
                ];
                $stmtGetTracks = $dbCheckIfUpdatesNeeded->prepare($sqlGetTracks);
                $stmtGetTracks->execute($paramsGetTracks);
                $resGetTracks = $stmtGetTracks->fetch(PDO::FETCH_ASSOC);
                if ($resGetTracks !== false) { 
                    $trackList = $resGetTracks['tracks'];
                    $trackData = [
                        'uris'          => $trackList,
                    ];
                    $endpoint = "https://api.spotify.com/v1/playlists/".$playlist->spotify_playlist_id."/tracks?".http_build_query($trackData);
                    $srUpdatePlaylist = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT, $endpoint);
                    $srUpdatePlaylist->send();
                    if (($srUpdatePlaylist->result !== false) && ($srUpdatePlaylist->error_number==0) && ($srUpdatePlaylist->http_code < 400)) {
                        // All good
                        //error_log("OK:  get_letters CURL returned http code ".$srUpdatePlaylist->http_code);
                    } else {
                        //error_log("ERR: get_letters CURL returned http code ".$srUpdatePlaylist->http_code." (".$srUpdatePlaylist->result.")");

                        if ($srUpdatePlaylist->http_code >= 400) {
                            $error_messages[] = "Request URL: {$endpoint}";
                            $error_messages[] = "Request returned ".$srUpdatePlaylist->http_code.': '.$srUpdatePlaylist->result;
                        } else {
                            $error_messages[] = $srUpdatePlaylist->error_message;
                        }
                    }
                }
            }
        }
    }

    // If we're the playlist owner, we don't need a participation entry
    $found_user = ($playlist->user_id == $_SESSION['USER_ID']);

    if (count($error_messages)==0) {
        $participants = Participation::find([['playlist_id','=',$playlist_id],]);
        foreach ($participants as $participant) {
            //$participant->user = $participant->getUser();
            if ($participant->user_id == $_SESSION['USER_ID']) { $found_user = true; }
        }
        // Add in playlist owner too
        $participants[] = $_SESSION['USER'];
    }

    if (!$found_user) {
        $error_messages[] = "You have not joined this playlist!";
        $fatal_error = true;
    }

    $letters = Letter::find([['playlist_id','=',$playlist_id],]);

    if (empty($letters)) {
        $letters = [];
    } else {
        foreach ($letters as $letter) {
            if (!empty($letter->user_id)) {
                $letter->user = $letter->getUser();
            }
        }
    }

    $time_end = microtime(true);

    if (count($error_messages)>0) {
        $hash = sha1(serialize($error_messages));
        $container = [
            'errors'    => $errors,
            'hash'      => $hash,
        ];
        $output = json_encode($container);
        ob_end_clean();
        die($output);
    } else {
        $hash = sha1(serialize($letters));
        $container = [
            'result'    => $letters,
            'hash'      => $hash,
        ];
        $output = json_encode($container);
        ob_end_clean();
        //echo "Runtime: ".(($time_end - $time_start) / 1000000)." ms\n\n";
        die($output);
    }