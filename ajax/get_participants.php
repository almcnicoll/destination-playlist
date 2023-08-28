<?php
    // Returns the current participant list for the playlist
    ob_start();
    // Include Playlist class
    if (!@include_once('class/participation.php')) {
        if (!@include_once('../class/participation.php')) {
            require_once('../../class/participation.php');
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

    if ($playlist->user_id != $_SESSION['USER_ID']) {
        $error_messages[] = "You do not own this playlist!";
        $fatal_error = true;
    }

    if (count($error_messages)==0) {
        $participants = Participation::find([['playlist_id','=',$playlist_id],]);
        foreach ($participants as $participant) {
            $participant->user = $participant->getUser();
        }
    }

    if (empty($participants)) {
        $participants = [];
    }

    if (count($error_messages)>0) {
        $output = json_encode(['errors' => $error_messages]);
        ob_end_clean();
        die($output);
    } else {
        $output = json_encode($participants);
        ob_end_clean();
        die($output);
    }