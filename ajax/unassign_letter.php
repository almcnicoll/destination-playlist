<?php
    require_once('../autoload.php');
    // Assigns letters to participants
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;

    $error_messages = [];
    $warning_messages = [];
    $info_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!isset($_REQUEST['letter_id'])) {
        $error_messages[] = "No letter specified";
        $fatal_error = true;
    } else {
        //var_dump($_REQUEST); die();
        $letter_id = (int)$_REQUEST['letter_id'];
        $letter = Letter::getById($letter_id);
        if ($letter == null) {
            $error_messages[] = "Letter not found";
            $fatal_error = true;
        } else {
            $playlist_id = $letter->playlist_id;
            $playlist = Playlist::getById($playlist_id);
            if ($playlist == null) {
                $error_messages[] = "Playlist not found";
                $fatal_error = true;
            } else {
                // We can proceed if (a) the user owns the letter or (b) the user owns the playlist
                if (($letter->user_id == $_SESSION['USER_ID'])
                  ||($playlist->user_id == $_SESSION['USER_ID'])) {
                    $user = $playlist->getOwner();
                } else {
                    $error_messages[] = "You do not own this playlist!";
                    $fatal_error = true;
                }
            }
        }
    }

    // Return if fatal
    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    // Do the unassigning
    $letter->user_id = null;
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