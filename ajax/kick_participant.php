<?php
    require_once('../autoload.php');
    // TODO - also return a results hash so pages can refresh only if hash is different

    // Returns the current participant list for the playlist
    ob_start();

    User::loginCheck(false);
    
    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!isset($_REQUEST['playlist_id'])) {
        $error_messages[] = "No playlist specified";
        $fatal_error = true;
        $playlist_id = 0;
    } else {
        $playlist_id = $_REQUEST['playlist_id'];
    }

    if (!isset($_REQUEST['user_id'])) {
        $error_messages[] = "No user specified";
        $fatal_error = true;
        $user_id = 0;
    } else {
        $user_id = $_REQUEST['user_id'];
    }

    $playlist = Playlist::getById($playlist_id);
    if ($playlist == null) {
        $error_messages[] = "Playlist not found";
        $fatal_error = true;
    }

    if ($playlist->user_id != $_SESSION['USER_ID']) {
        $error_messages[] = "You do not own this playlist!";
        $fatal_error = true;
    }

    if ($_REQUEST['user_id'] == $_SESSION['USER_ID']) {
        $error_messages[] = "You cannot kick yourself out of the playlist!";
        $fatal_error = true;
    }

    if (count($error_messages)==0) {
        $participant = Participation::findFirst([
            ['playlist_id','=',$playlist_id],
            ['user_id','=',$user_id],
        ]);
        if ($participant == null) {
            $error_messages[] = "The user is not part of this playlist.";
            $fatal_error = true;
        } else {
            // All good - kick or unkick them
            $kick = filter_var($_REQUEST['kick'], FILTER_VALIDATE_BOOLEAN);
            $participant->removed = $kick;
            $participant->save();
        }
    }

    if (count($error_messages)>0) {
        $output = json_encode(['errors' => $error_messages]);
        ob_end_clean();
        die($output);
    } else {
        $output = json_encode(['success' => true]);
        ob_end_clean();
        die($output);
    }