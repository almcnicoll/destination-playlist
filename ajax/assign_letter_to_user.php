<?php
    require_once('../autoload.php');
    // Owner assigns a specific unassigned letter to a specific participant
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;

    $error_messages = [];
    $warning_messages = [];
    $info_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!isset($_REQUEST['letter_id']) || !isset($_REQUEST['user_id'])) {
        $error_messages[] = "Letter and user must both be specified";
        $fatal_error = true;
    }

    $letter = null;
    $playlist = null;
    $target_user_id = null;

    if (!$fatal_error) {
        $letter_id = (int)$_REQUEST['letter_id'];
        $target_user_id = (int)$_REQUEST['user_id'];
        $letter = Letter::getById($letter_id);
        if ($letter == null) {
            $error_messages[] = "Letter not found";
            $fatal_error = true;
        } else {
            $playlist = Playlist::getById($letter->playlist_id);
            if ($playlist == null) {
                $error_messages[] = "Playlist not found";
                $fatal_error = true;
            } elseif ($playlist->user_id != $_SESSION['USER_ID']) {
                $error_messages[] = "You do not own this playlist!";
                $fatal_error = true;
            } elseif (!empty($letter->user_id)) {
                // Assigning never changes an existing assignment - unassign it first if you want to reassign it
                $error_messages[] = "That letter is already assigned to someone.";
                $fatal_error = true;
            }
        }
    }

    $targetUser = null;
    if (!$fatal_error) {
        // Never trust the client's dropdown selection - the target must actually be the owner
        // themselves, or a non-kicked participant of this specific playlist
        $isOwner = ($target_user_id == $playlist->user_id);
        $isActiveParticipant = false;
        if (!$isOwner) {
            $participation = Participation::findFirst([
                ['playlist_id', '=', $playlist->id],
                ['user_id', '=', $target_user_id],
                ['removed', '=', 0],
            ]);
            $isActiveParticipant = ($participation !== null);
        }
        if (!$isOwner && !$isActiveParticipant) {
            $error_messages[] = "That user isn't an active participant of this playlist.";
            $fatal_error = true;
        } else {
            $targetUser = User::getById($target_user_id);
            if ($targetUser == null) {
                $error_messages[] = "User not found";
                $fatal_error = true;
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

    // Do the assigning
    $letter->user_id = $target_user_id;
    $letter->save();

    // Return values
    $output = [];
    if (count($error_messages)>0) {
        $output['errors'] = $error_messages;
    } else {
        $output['success'] = true;
        // Same shape as get_letters.php, so the caller can patch its row in place
        $letter->user = $targetUser;
        $output['letter'] = $letter;
    }
    if (count($warning_messages)>0) {
        $output['warnings'] = $warning_messages;
    }
    if (count($info_messages)>0) {
        $output['info'] = $info_messages;
    }
    ob_end_clean();
    die(json_encode($output));
