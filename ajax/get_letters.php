<?php
    // TODO - also return a results hash so pages can refresh only if hash is different

    // Returns the current participant list for the playlist
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

    if (count($error_messages)>0) {
        $output = json_encode(['errors' => $error_messages]);
        ob_end_clean();
        die($output);
    } else {
        $output = json_encode($letters);
        ob_end_clean();
        die($output);
    }