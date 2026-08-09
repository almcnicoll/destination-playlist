<?php
    require_once('../autoload.php');
    // The target of an incoming swap proposal accepts or declines it
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $action = $_REQUEST['action'] ?? '';
    if (!isset($_REQUEST['proposer_letter_id']) || !in_array($action, ['accept', 'decline'], true)) {
        $error_messages[] = "Invalid request";
        $fatal_error = true;
    }

    $proposerLetter = null;
    $myLetter = null;
    if (!$fatal_error) {
        $proposerLetter = Letter::getById((int)$_REQUEST['proposer_letter_id']);
        if ($proposerLetter == null || empty($proposerLetter->swap_target_id)) {
            $error_messages[] = "That swap request is no longer available.";
            $fatal_error = true;
        } else {
            $myLetter = Letter::getById($proposerLetter->swap_target_id);
            if ($myLetter == null || $myLetter->user_id != $_SESSION['USER_ID']) {
                $error_messages[] = "That swap request isn't for one of your letters.";
                $fatal_error = true;
            }
        }
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    if ($action == 'decline') {
        $proposerLetter->swap_target_id = null;
        $proposerLetter->save();
        $output = ['success' => true, 'declined' => true];
        ob_end_clean();
        die(json_encode($output));
    }

    // Accept - re-validate both sides are still genuinely on the market before trading anything.
    // Either could have gone stale or been withdrawn in the moment between the proposal and this click
    $staleCutoff = (new DateTime('-8 seconds'))->format('Y-m-d H:i:s');
    $proposerFresh = $proposerLetter->swap_offered && !empty($proposerLetter->swap_offered_at) && $proposerLetter->swap_offered_at >= $staleCutoff;
    $myFresh = $myLetter->swap_offered && !empty($myLetter->swap_offered_at) && $myLetter->swap_offered_at >= $staleCutoff;
    if (!$proposerFresh || !$myFresh) {
        $output = json_encode(['errors' => ["That swap is no longer available - one of you may have closed the swap window."]]);
        http_response_code(409);
        ob_end_clean();
        die($output);
    }

    // Trade ownership of the two letter slots only - nothing else about either letter (track, rank,
    // letter character) changes. Wrapped in a transaction since a partial failure here would leave
    // a letter double-booked or orphaned, which plain sequential saves elsewhere in this app don't risk
    $pdo = db::getPDO();
    $pdo->beginTransaction();
    try {
        $tmpUserId = $proposerLetter->user_id;
        $proposerLetter->user_id = $myLetter->user_id;
        $myLetter->user_id = $tmpUserId;

        // Both come off the market - the trade is done
        foreach ([$proposerLetter, $myLetter] as $letter) {
            $letter->swap_offered = 0;
            $letter->swap_offered_at = null;
            $letter->swap_target_id = null;
            $letter->save();
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Same shape as get_letters.php, so the caller can patch both rows immediately
    $proposerLetter->user = $proposerLetter->getUser();
    $myLetter->user = $myLetter->getUser();

    $output = ['success' => true, 'letters' => [$proposerLetter, $myLetter]];
    ob_end_clean();
    die(json_encode($output));
