<?php
    require_once('../autoload.php');
    // Takes the session user's currently-offered letter (if any) off the swap market - called when
    // their swap modal closes, and best-effort via sendBeacon on tab close (see js/swap_market.js)
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

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    $playlist_id = (int)$_REQUEST['playlist_id'];

    $letters = Letter::find([
        ['playlist_id', '=', $playlist_id],
        ['user_id', '=', $_SESSION['USER_ID']],
        ['swap_offered', '=', 1],
    ]);
    foreach ($letters as $letter) {
        $letter->swap_offered = 0;
        $letter->swap_offered_at = null;
        $letter->swap_target_id = null;
        $letter->save();
    }

    $output = ['success' => true];
    ob_end_clean();
    die(json_encode($output));
