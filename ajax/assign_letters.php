<?php
    require_once('../autoload.php');
    // Assigns letters to participants
    ob_start();

    User::loginCheck(false);

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

    // NEW, SIMPLER METHOD
    $pdo = db::getPDO();
    $sqlAssignmentList = <<<END_SQL
SELECT `number`, users.id AS user_id
FROM integers i
INNER JOIN 
(
SELECT u.id, COUNT(l.id) AS letter_count
FROM users u
LEFT JOIN participations part ON (part.user_id=u.id AND part.playlist_id={$playlist_id})
LEFT JOIN playlists p ON part.playlist_id = p.id OR (u.id=p.user_id AND p.id={$playlist_id})
LEFT JOIN letters l ON (l.user_id=u.id AND l.playlist_id={$playlist_id})
WHERE p.id={$playlist_id} AND (part.removed IS NULL OR part.removed=0)
GROUP BY u.id
) users ON users.letter_count<=`number`
ORDER BY `number`,RAND()
LIMIT {$unassigned_count}
;
END_SQL;
    
    //LoggedError::log(LoggedError::TYPE_PHP, 1, __FILE__, __LINE__, 'Unassigned letters: '.count($unassigned_letters));
    $stmt = $pdo->prepare($sqlAssignmentList);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $results = $stmt->fetchAll();
    //LoggedError::log(LoggedError::TYPE_PHP, 1, __FILE__, __LINE__, 'Assignments to make: '.count($results));
    foreach ($results as $row) {
        $letter = array_shift($unassigned_letters);
        $letter->user_id = $row['user_id'];
        $letter->save();
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