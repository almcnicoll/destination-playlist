<?php
    require_once('../autoload.php');
    // Creates or updates a user from the admin Users page
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

    $identifier = trim($_REQUEST['identifier'] ?? '');
    $authmethod_id = (int)($_REQUEST['authmethod_id'] ?? 0);
    $authmethod = null;

    if (!$fatal_error) {
        if ($identifier === '') {
            $error_messages[] = "Account identifier cannot be empty.";
            $fatal_error = true;
        }
        $authmethod = AuthMethod::getById($authmethod_id);
        if ($authmethod === null) {
            $error_messages[] = "Please choose a valid login method.";
            $fatal_error = true;
        }
    }

    if (!$fatal_error) {
        if (!empty($_REQUEST['id'])) {
            $user = User::getById((int)$_REQUEST['id']);
            if ($user === null) {
                $error_messages[] = "User not found.";
                $fatal_error = true;
            }
        } else {
            $user = new User();
        }
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(400);
        ob_end_clean();
        die($output);
    }

    $user->authmethod_id = $authmethod_id;
    $user->identifier = $identifier;
    $user->display_name = trim($_REQUEST['display_name'] ?? '') ?: null;
    $user->email = trim($_REQUEST['email'] ?? '') ?: null;
    $user->market = trim($_REQUEST['market'] ?? '') ?: null;
    $user->image_url = trim($_REQUEST['image_url'] ?? '') ?: null;

    try {
        $user->save();
    } catch (PDOException $e) {
        // LoginLookup is a UNIQUE KEY on (authmethod_id, identifier) - MySQL error 1062
        if (($e->errorInfo[1] ?? null) == 1062) {
            $error_messages[] = "That login method and identifier are already linked to another user.";
        } else {
            $error_messages[] = "Could not save user: ".$e->getMessage();
        }
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(409);
        ob_end_clean();
        die($output);
    }

    // Same shape as admin_get_users.php's rows, so the caller can patch the table with either
    $user->authmethod_name = $authmethod->methodName;

    $output = ['success' => true, 'user' => $user];
    ob_end_clean();
    die(json_encode($output));
