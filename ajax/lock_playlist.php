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

    if (!isset($_REQUEST['playlist_id'])) {
        $error_messages[] = "No playlist specified";
        $fatal_error = true;
    } else {
        if (!isset($_REQUEST['action'])) {
            $error_messages[] = "No action specified";
            $fatal_error = true;
        } else {
            $action = $_REQUEST['action'];
            $playlist_id = $_REQUEST['playlist_id'];
            $playlist = Playlist::getById($playlist_id);
            if ($playlist == null) {
                $error_messages[] = "Playlist not found";
                $fatal_error = true;
            } else {
                if ($playlist->user_id != $_SESSION['USER_ID']) {
                    $error_messages[] = "You do not own this playlist!";
                    $fatal_error = true;
                } else {
                    $user = $playlist->getOwner();
                }
            }
        }
    }

    // Return if fatal
    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        ob_end_clean();
        die($output);
    }

    // Lock or unlock
    switch($action) {
        case 'lock':
            $playlist->setFlag(Playlist::FLAGS_PEOPLELOCKED, true);
            $playlist->save();
            break;
        case 'unlock':
            $playlist->setFlag(Playlist::FLAGS_PEOPLELOCKED, false);
            $playlist->save();
            break;
        default:
            $error_messages[] = "Action {$action} not recognised";
            $output = json_encode(['errors' => $error_messages]);
            ob_end_clean();
            die($output);
            break;
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
    ob_end_clean();
    die(json_encode($output));