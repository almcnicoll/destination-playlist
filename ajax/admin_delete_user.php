<?php
    require_once('../autoload.php');
    // Deletes a user account, from the admin Users page
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!$_SESSION['USER']->isAdmin()) {
        $error_messages[] = "You do not have access to this area.";
        $fatal_error = true;
    }

    $id = (int)($_REQUEST['id'] ?? 0);
    $user = null;

    if (!$fatal_error) {
        if ($id == $_SESSION['USER_ID']) {
            $error_messages[] = "You cannot delete your own account from here.";
            $fatal_error = true;
        } else {
            $user = User::getById($id);
            if ($user === null) {
                $error_messages[] = "User not found.";
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

    // Letters aren't foreign-keyed to users (see sql/create-tables.sql), so ownership doesn't cascade -
    // unassign anything this user was holding in *anyone's* playlist before removing them. Playlists
    // this user owns (and, in turn, those playlists' letters/participations) and this user's own
    // participations in other people's playlists DO cascade at the DB level once the user row goes.
    $pdo = db::getPDO();
    $sqlUnassign = "UPDATE letters SET user_id = NULL WHERE user_id = :id";
    $stmtUnassign = $pdo->prepare($sqlUnassign);
    $stmtUnassign->execute(['id' => $id]);

    $user->delete();

    $output = ['success' => true];
    ob_end_clean();
    die(json_encode($output));
