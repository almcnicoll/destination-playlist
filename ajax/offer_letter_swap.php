<?php
    require_once('../autoload.php');
    // Puts one of the session user's own letters up for swap and opens the marketplace for it
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
            $error_messages[] = "You can only offer your own letter for swap.";
            $fatal_error = true;
        }
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    // Only one letter offered at a time per playlist - offering a new one withdraws any other
    $pdo = db::getPDO();
    $sqlClearOthers = "UPDATE letters SET swap_offered=0, swap_offered_at=NULL, swap_target_id=NULL
                        WHERE playlist_id=:playlist_id AND user_id=:user_id AND id != :letter_id AND swap_offered=1";
    $stmtClearOthers = $pdo->prepare($sqlClearOthers);
    $stmtClearOthers->execute([
        'playlist_id' => $letter->playlist_id,
        'user_id'     => $_SESSION['USER_ID'],
        'letter_id'   => $letter->id,
    ]);

    $letter->swap_offered = 1;
    $letter->swap_offered_at = (new DateTime())->format('Y-m-d H:i:s');
    $letter->swap_target_id = null;
    $letter->save();

    $output = ['success' => true];
    ob_end_clean();
    die(json_encode($output));
