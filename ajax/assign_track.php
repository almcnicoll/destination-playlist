<?php
    // Assigns track to letter
    ob_start();
    // Include Letter class
    if (!@include_once('class/letter.php')) {
        if (!@include_once('../class/letter.php')) {
            require_once('../../class/letter.php');
        }
    }
    // Include Participation class
    if (!@include_once('class/participation.php')) {
        if (!@include_once('../class/participation.php')) {
            require_once('../../class/participation.php');
        }
    }

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

    // Do the assigning
    $letter->spotify_track_id = $_REQUEST['spotify_id'];
    $letter->cached_title = $_REQUEST['cached_title'];
    $letter->cached_artist = $_REQUEST['cached_artist'];
    $letter->save();

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
    ob_end_clean();
    die(json_encode($output));