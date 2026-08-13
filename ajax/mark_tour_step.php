<?php
    require_once('../autoload.php');

    // Marks a single guided-tour step, marks every known step (all=1), or clears every known
    // step (reset=1) as seen for the current user. all=1 is what closing a tour via its X button
    // uses; reset=1 is what "Replay tour" uses, so the replay is a genuine global reset rather
    // than just bypassing the seen-check for the page it was clicked from - see js/tour_guide.js.
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;
    $error_messages = [];

    if (!isset($_SESSION['USER_ID'])) {
        $error_messages[] = "You are not logged in.";
        $fatal_error = true;
    }

    $mark_all = isset($_REQUEST['all']) && $_REQUEST['all'] == '1';
    $reset = isset($_REQUEST['reset']) && $_REQUEST['reset'] == '1';
    $step_key = $_REQUEST['step_key'] ?? '';

    if (!$mark_all && !$reset) {
        if (empty($step_key)) {
            $error_messages[] = "No step specified";
            $fatal_error = true;
        } elseif (!in_array($step_key, TourStep::$knownKeys, true)) {
            $error_messages[] = "Unrecognised step";
            $fatal_error = true;
        }
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        header("HTTP/1.1 400 Bad Request");
        ob_end_clean();
        die($output);
    }

    if ($reset) {
        TourStep::resetAll((int)$_SESSION['USER_ID']);
    } elseif ($mark_all) {
        TourStep::markAllSeen((int)$_SESSION['USER_ID']);
    } else {
        TourStep::markSeen((int)$_SESSION['USER_ID'], $step_key);
    }

    ob_end_clean();
    die(json_encode(['success' => true]));
