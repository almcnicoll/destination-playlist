<?php
    require_once('../autoload.php');
    /*
     * Note to future me:
     * At the moment, Spotify doesn't do collaborative playlists very well via API
     * Collaborative playlists don't allow API calls to alter them unless you're the owner;
     * The "new" way is to add collaborators, but the API won't let you do that.
     * So at the moment, the only way we can do this is for non-owners to write to the
     * database, and have the owner update to Spotify when it next retrieves letters.
     */

    // Assigns track to letter
    ob_start();

    // Pre-flight checks
    $fatal_error = false;

    $error_messages = [];
    $warning_messages = [];
    $info_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!isset($_REQUEST['id'])) {
        $error_messages[] = "No letter specified";
        $fatal_error = true;
    }

    $letter = Letter::getById($_REQUEST['id']);
    if ($letter == null) {
        $error_messages[] = "Playlist not found";
        $fatal_error = true;
    } else {    
        $playlist_id = $letter->playlist_id;

        $playlist = Playlist::getById($playlist_id);
        if ($playlist == null) {
            $error_messages[] = "Playlist not found";
            $fatal_error = true;
        }

        if ($letter->user_id != $_SESSION['USER_ID']) {
            $error_messages[] = "This is not your letter!";
            $fatal_error = true;
        } else {
            // Check if we own playlist, and if not, check that we're a participant
            if ($playlist->user_id != $letter->user_id) {
                $participations = Participation::find([['user_id','=',$letter->user_id],['playlist_id','=',$playlist_id]]);
                if ((count($participations) == 0) || ($participations[0]->removed != 0)) {
                    $error_messages[] = "You are not part of this playlist!";
                    $fatal_error = true;
                }
            }
        }
    }

    // Return if fatal
    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        header("HTTP/1.1 400 Bad Request");
        ob_end_clean();
        die($output);
    }

    // Backup old values in case we need to revert
    $old = $letter->clone(true,true,true); // Keep ID, created, modified

    // Do the assigning
    $letter->spotify_track_id = $_REQUEST['spotify_id'];
    $letter->cached_title = $_REQUEST['cached_title'];
    $letter->cached_artist = $_REQUEST['cached_artist'];
    $letter->save();

    // If we own the playlist, update it straight away - otherwise leave it for the owner
    if ($playlist->user_id == $_SESSION['USER_ID']) {
        // Update the playlist
        $sqlGetTracks = <<<END_SQL
        SELECT CONCAT('spotify:track:',GROUP_CONCAT(spotify_track_id ORDER BY id SEPARATOR ',spotify:track:')) AS tracks FROM letters
        WHERE spotify_track_id IS NOT NULL
        AND playlist_id = :playlist_id
        GROUP BY playlist_id
        ;
END_SQL;
        $params = [
            'playlist_id' => $playlist_id,
        ];
        $db = db::getPDO();
        $stmt = $db->prepare($sqlGetTracks);
        $stmt->execute($params);
        $resGetTracks = $stmt->fetch(PDO::FETCH_ASSOC);
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
                //error_log("OK:  CURL returned http code ".$srUpdatePlaylist->http_code);
            } else {
                //error_log("ERR: CURL returned http code ".$srUpdatePlaylist->http_code." (".$srUpdatePlaylist->result.")");

                if ($srUpdatePlaylist->http_code >= 400) {
                    $error_messages[] = "Request URL: {$endpoint}";
                    $error_messages[] = "Request returned ".$srUpdatePlaylist->http_code.': '.$srUpdatePlaylist->result;
                } else {
                    $error_messages[] = $srUpdatePlaylist->error_message;
                }
                
                // Reverse the assigning - SIMPLES
                $old->save();
            }
        }
    }

    // Return values
    $output = [];
    if (count($error_messages)>0) {
        $output['errors'] = $error_messages;
    } else {
        $output['success'] = true;
    }
    if (count($warning_messages)>0) {
        $output['warnings'] = $warning_messages;
    }
    if (count($info_messages)>0) {
        $output['info'] = $info_messages;
    }
    http_response_code($srUpdatePlaylist->http_code); // Pass on any errors
    ob_end_clean();
    die(json_encode($output));