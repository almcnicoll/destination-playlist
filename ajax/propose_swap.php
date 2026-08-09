<?php
    require_once('../autoload.php');
    // Proposes a swap: the session user's own currently-offered letter, for someone else's offered letter
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!isset($_REQUEST['target_letter_id'])) {
        $error_messages[] = "No target letter specified";
        $fatal_error = true;
    }

    $targetLetter = null;
    $staleCutoff = (new DateTime('-8 seconds'))->format('Y-m-d H:i:s');

    if (!$fatal_error) {
        $targetLetter = Letter::getById((int)$_REQUEST['target_letter_id']);
        if ($targetLetter == null) {
            $error_messages[] = "That letter is no longer available.";
            $fatal_error = true;
        } elseif ($targetLetter->user_id == $_SESSION['USER_ID']) {
            $error_messages[] = "You can't swap with your own letter.";
            $fatal_error = true;
        } elseif (!$targetLetter->swap_offered || empty($targetLetter->swap_offered_at) || $targetLetter->swap_offered_at < $staleCutoff) {
            $error_messages[] = "That letter is no longer up for swap.";
            $fatal_error = true;
        }
    }

    $myLetter = null;
    if (!$fatal_error) {
        $myLetter = Letter::findFirst([
            ['playlist_id', '=', $targetLetter->playlist_id],
            ['user_id', '=', $_SESSION['USER_ID']],
            ['swap_offered', '=', 1],
        ]);
        if ($myLetter == null) {
            $error_messages[] = "Offer one of your own letters for swap first.";
            $fatal_error = true;
        }
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    $myLetter->swap_target_id = $targetLetter->id;
    $myLetter->save();

    $output = ['success' => true];
    ob_end_clean();
    die(json_encode($output));
