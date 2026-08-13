<?php
    require_once('../autoload.php');

    // Marks a single guided-tour step as seen for the current user
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;
    $error_messages = [];

    if (!isset($_SESSION['USER_ID'])) {
        $error_messages[] = "You are not logged in.";
        $fatal_error = true;
    }

    $step_key = $_REQUEST['step_key'] ?? '';
    if (empty($step_key)) {
        $error_messages[] = "No step specified";
        $fatal_error = true;
    } elseif (!in_array($step_key, TourStep::$knownKeys, true)) {
        $error_messages[] = "Unrecognised step";
        $fatal_error = true;
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        header("HTTP/1.1 400 Bad Request");
        ob_end_clean();
        die($output);
    }

    TourStep::markSeen((int)$_SESSION['USER_ID'], $step_key);

    ob_end_clean();
    die(json_encode(['success' => true]));
