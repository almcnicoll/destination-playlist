<?php
    require_once('../autoload.php');
    // Returns who a letter in this playlist could be assigned to: the owner, plus any
    // participant who hasn't been kicked - for the "Assign" picker modal
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
    }
    $playlist_id = $_REQUEST['playlist_id'] ?? null;

    $playlist = null;
    if (!$fatal_error) {
        $playlist = Playlist::getById($playlist_id);
        if ($playlist == null) {
            $error_messages[] = "Playlist not found";
            $fatal_error = true;
        } elseif ($playlist->user_id != $_SESSION['USER_ID']) {
            $error_messages[] = "You do not own this playlist!";
            $fatal_error = true;
        }
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    $owner = $playlist->getOwner();
    $users = [];
    $users[] = ['id' => $owner->id, 'display_name' => $owner->display_name];

    $participations = Participation::find([['playlist_id', '=', $playlist_id], ['removed', '=', 0]]);
    foreach ($participations as $participation) {
        $participant = $participation->getUser();
        if ($participant !== null) {
            $users[] = ['id' => $participant->id, 'display_name' => $participant->display_name];
        }
    }

    usort($users, function($a, $b) { return strcasecmp($a['display_name'], $b['display_name']); });

    $output = ['users' => $users];
    ob_end_clean();
    die(json_encode($output));
