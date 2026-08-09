<?php
    require_once('../autoload.php');
    // Polled roughly every second while a user's swap modal is open. Doubles as the heartbeat for
    // the session user's own offer (see js/swap_market.js) - refreshing swap_offered_at here is
    // what keeps it visible to everyone else's marketplace; if polling stops, it goes stale and
    // drops out of the market on its own, without needing an explicit "I closed my modal" signal
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

    $playlist = null;
    $playlist_id = null;
    if (!$fatal_error) {
        $playlist_id = (int)$_REQUEST['playlist_id'];
        $playlist = Playlist::getById($playlist_id);
        if ($playlist == null) {
            $error_messages[] = "Playlist not found";
            $fatal_error = true;
        } else {
            $isOwner = ($playlist->user_id == $_SESSION['USER_ID']);
            $isActiveParticipant = false;
            if (!$isOwner) {
                $participation = Participation::findFirst([
                    ['playlist_id', '=', $playlist_id],
                    ['user_id', '=', $_SESSION['USER_ID']],
                    ['removed', '=', 0],
                ]);
                $isActiveParticipant = ($participation !== null);
            }
            if (!$isOwner && !$isActiveParticipant) {
                $error_messages[] = "You're not part of this playlist.";
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

    // Find my own offered letter - no staleness filter here, since it's mine and gets heartbeated
    // below; it can only stop being "found" by an explicit withdraw or a completed swap
    $myLetter = Letter::findFirst([
        ['playlist_id', '=', $playlist_id],
        ['user_id', '=', $_SESSION['USER_ID']],
        ['swap_offered', '=', 1],
    ]);
    if ($myLetter !== null) {
        $myLetter->swap_offered_at = (new DateTime())->format('Y-m-d H:i:s');
        $myLetter->save();
    }

    $staleCutoff = (new DateTime('-8 seconds'))->format('Y-m-d H:i:s');

    // Everyone else's still-fresh offers in this playlist
    $market = [];
    $others = Letter::find([
        ['playlist_id', '=', $playlist_id],
        ['swap_offered', '=', 1],
        ['swap_offered_at', '>', $staleCutoff],
    ]);
    foreach ($others as $other) {
        if ($other->user_id == $_SESSION['USER_ID']) { continue; }
        $owner = $other->getUser();
        if ($owner === null) { continue; }
        $market[] = [
            'letter_id'    => $other->id,
            'letter'       => $other->letter,
            'display_name' => $owner->display_name,
        ];
    }

    // Incoming proposals - other fresh offers currently targeting my offered letter
    $incoming = [];
    if ($myLetter !== null) {
        $proposers = Letter::find([
            ['playlist_id', '=', $playlist_id],
            ['swap_target_id', '=', $myLetter->id],
            ['swap_offered', '=', 1],
            ['swap_offered_at', '>', $staleCutoff],
        ]);
        foreach ($proposers as $proposer) {
            $proposerUser = $proposer->getUser();
            if ($proposerUser === null) { continue; }
            $incoming[] = [
                'letter_id'    => $proposer->id,
                'letter'       => $proposer->letter,
                'display_name' => $proposerUser->display_name,
            ];
        }
    }

    // My own outgoing proposal, if any - self-heals if its target has stopped being genuinely
    // on the market (they withdrew, went stale, or already completed a different swap)
    $myProposalTarget = null;
    if ($myLetter !== null && !empty($myLetter->swap_target_id)) {
        $target = Letter::getById($myLetter->swap_target_id);
        $targetFresh = ($target !== null) && $target->swap_offered && !empty($target->swap_offered_at) && $target->swap_offered_at > $staleCutoff;
        if ($targetFresh) {
            $targetOwner = $target->getUser();
            $myProposalTarget = [
                'letter_id'    => $target->id,
                'letter'       => $target->letter,
                'display_name' => $targetOwner->display_name,
            ];
        } else {
            $myLetter->swap_target_id = null;
            $myLetter->save();
        }
    }

    $output = [
        'myOfferedLetter'   => ($myLetter === null) ? null : ['letter_id' => $myLetter->id, 'letter' => $myLetter->letter],
        'market'            => $market,
        'incomingProposals' => $incoming,
        'myProposalTarget'  => $myProposalTarget,
    ];
    ob_end_clean();
    die(json_encode($output));
