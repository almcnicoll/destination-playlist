<?php
    require_once('../autoload.php');
    // Assigns letters to participants
    require_once('../autoload.php');
    ob_start();

    $fatal_error = false;

    $error_messages = [];
    $warning_messages = [];
    $info_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!isset($_REQUEST['playlist_id'])) {
        $error_messages[] = "No playlist specified";
        $fatal_error = true;
    }
    $playlist_id = $_REQUEST['playlist_id'];

    $playlist = Playlist::getById($playlist_id);
    if ($playlist == null) {
        $error_messages[] = "Playlist not found";
        $fatal_error = true;
    }

    if ($playlist->user_id != $_SESSION['USER_ID']) {
        $error_messages[] = "You do not own this playlist!";
        $fatal_error = true;
    }

    $user = $playlist->getOwner();

    // Return if fatal
    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        ob_end_clean();
        die($output);
    }

    // Do the assigning
    
    // If requested, start with no letter assignments
    if (isset($_REQUEST['from_scratch']) && $_REQUEST['from_scratch']) {
        $playlist->clearLetterOwners();
    }
    // Count total letters, unassigned letters, participants
    $letters = $playlist->getLetters();
    $letters_count = count($letters);
    $unassigned_letters = $playlist->getUnassignedLetters();
    $unassigned_count = count($unassigned_letters);
    $participants = $playlist->getParticipants();
    $participant_count = count($participants)+1; // Include owner
    $target_per_participant = floor($letters_count / $participant_count);
    //$target_remainder = $letters_count % $participant_count;
    $overassignment_count = 0;
    $participant_letter_counts = [];
    $overassigned_participants = [];
    // Loop through all letters, working out which participant they belong to
    // Count any "overspend" (people with more than their fair share) as we need it for subsequent calcs
    foreach ($participants as $p) {
        $participant_letter_counts[$p->user_id] = 0;
    }
    $participant_letter_counts[$user->id] = 0; // Owner

    foreach ($letters as $l) {
        if ($l->user_id !== null) {
            $participant_letter_counts[$l->user_id]++;
            if ($participant_letter_counts[$l->user_id] > $target_per_participant) {
                $overassignment_count++;
                $overassigned_participants[$l->user_id] = $participant_letter_counts[$l->user_id] - $target_per_participant;
            }
        }
    }
    // Remove any overassigned users and ALL their tracks from calculations (not just "overspent" ones)
    $new_letters_count = $unassigned_count - $overassignment_count - (count($overassigned_participants) * $target_per_participant);
    $new_participant_count = $participant_count - count($overassigned_participants);
    $new_target_per_participant = floor($new_letters_count / $new_participant_count);
    $new_target_remainder = $new_letters_count % $new_participant_count;
    // Make an array of un-overassigned users
    $new_participants = [];
    foreach ($participants as $p) {
        if($participant_letter_counts[$p->id] <= $target_per_participant) { $new_participants[] = $p; }
    }
    // Add a "fake" participation for the owner if appropriate
    if ($participant_letter_counts[$user->id] <= $target_per_participant) {
        $up = new Participation(); // DO NOT CALL save() ON THIS OBJECT!
        $up->user_id = $user->id;
        $up->id = 0;
        $up->playlist_id = $playlist->id;
        $new_participants[] = $up;
    }
    // TODO - what if no users / no tracks at this point? Add tests.


    // We now have how many we want to give to each user
    // So, loop through letters, assigning them to sequential users who aren't overassigned
    // Only allow overassignment once we're in remainder territory
    $i=0;
    $u = 0;
    $remainder_territory_boundary = $new_target_per_participant * $new_participant_count;
    foreach ($unassigned_letters as $l) {
        if ($i>=$remainder_territory_boundary) {
            // Just give it to this user - no checks
        } else {
            // Check that user has capacity - otherwise pick next until capacity found
            while( $participant_letter_counts[ $new_participants[$u]->user_id ] >= $new_target_per_participant ) {
                $u++; if ($u >= count($new_participants)) { $u = 0; }
            }
        }
        // Assign letter to user and increase their count
        if ($new_participants[$u] instanceof Participation) {
            $l->user_id = $new_participants[$u]->user_id; // Get user_id from participation
        } else {
            $l->user_id = $new_participants[$u]->id; // Get id from user - shouldn't happen now though as we're creating "fake" participation for owner
        }
        $l->save();
        $participant_letter_counts[ $new_participants[$u]->id ]++;
        // Increment counter and user
        $i++;
        $u++;
        if ($u >= count($new_participants)) { $u = 0; }
    }

    // Return values
    $output = [];
    if (count($error_messages)>0) {
        $output['errors'] = $error_messages;
    } else {
        $output['success'] = true;
    }
    if (count($warning_messages)>0) {
        $output['warnings'] = $warning_messages;
    }
    if (count($info_messages)>0) {
        $output['info'] = $info_messages;
    }
    ob_end_clean();
    die(json_encode($output));