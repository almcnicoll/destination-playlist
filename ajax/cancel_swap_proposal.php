<?php
    require_once('../autoload.php');
    // Withdraws the session user's own outgoing swap proposal, without taking their letter off
    // the market entirely - they can propose to someone else instead
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!isset($_REQUEST['letter_id'])) {
        $error_messages[] = "No letter specified";
        $fatal_error = true;
    }

    $letter = null;
    if (!$fatal_error) {
        $letter = Letter::getById((int)$_REQUEST['letter_id']);
        if ($letter == null) {
            $error_messages[] = "Letter not found";
            $fatal_error = true;
        } elseif ($letter->user_id != $_SESSION['USER_ID']) {
            $error_messages[] = "That's not your letter.";
            $fatal_error = true;
        }
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    $letter->swap_target_id = null;
    $letter->save();

    $output = ['success' => true];
    ob_end_clean();
    die(json_encode($output));
