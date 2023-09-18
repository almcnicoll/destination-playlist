<?php
    $fatal_error = false;

    // Include Playlist class
    if (!@include_once('class/playlist.php')) {
        if (!@include_once('../class/playlist.php')) {
            require_once('../../class/playlist.php');
        }
    }

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $playlist = null;

    if (!isset($params[0])) {
        $error_messages[] = "You need to choose which playlist to edit.";
        $fatal_error = true;
    } elseif (!is_numeric($params[0])) {
        $error_messages[] = "{$params[0]} isn't a valid playlist id.";
        $fatal_error = true;
    } else {
        $playlist = Playlist::getById($params[0]);
        if ($playlist->user_id != $_SESSION['USER_ID']) {
            $error_messages[] = "You can only edit playlists that you created.";
            $fatal_error = true;
        }
    }

    if ($fatal_error) {
        // If we can't edit, redirect to index and give error messages
        header("Location: {$config['root_path']}/?error_message=".urlencode(implode(',',$error_messages)));
        die();
    }

    // If form submitted, handle edit
    if (isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] == 'formsubmitted') {
            // Include User class
            if (!@include_once('class/user.php')) {
                if (!@include_once('../class/user.php')) {
                    require_once('../../class/user.php');
                }
            }
            // Include Letter class
            if (!@include_once('class/letter.php')) {
                if (!@include_once('../class/letter.php')) {
                    require_once('../../class/letter.php');
                }
            }
            // Include SpotifyRequest class
            if (!@include_once('class/spotifyrequest.php')) {
                if (!@include_once('../class/spotifyrequest.php')) {
                    require_once('../../class/spotifyrequest.php');
                }
            }
            
            // Make changes, but don't save
            $playlist->destination = $_REQUEST['destination'];
            $playlist->display_name = $_REQUEST['display_name'];
            $playlist->flags = 0; // And build up from here
            foreach ($_REQUEST['flags'] as $thisFlag) {
                $playlist->flags += ((int)$thisFlag);
            }
            // Create/update playlist on spotify
            $listresponse = $playlist->pushToSpotify();
            // TODO - handle errors/failures - remember, they may have deleted the Spotify list

            if ($listresponse->hasErrors()) {
                // Show the error
                $error_messages[] = $listresponse->getErrors();
            } else {
                // Make change in db
                
                $playlist->save();
                
                // Don't overwrite playlist image

                // Check if letters have changed
                $usable_letters_string = '';
                if ($playlist->hasFlags(Playlist::FLAGS_INCLUDEDIGITS)) {
                    $usable_letters_string = strtoupper(preg_replace('/[^\w\d]+/i','',$playlist->destination));
                } else {
                    $usable_letters_string = strtoupper(preg_replace('/[^\w]+/i','',$playlist->destination));
                }
                $playlist->tidyLetterRanks(); // Must be before we retrieve letters
                $current_letters = $playlist->getLetters();
                $current_letters_string = '';
                foreach ($current_letters as $cl) {
                    $current_letters_string .= $cl->letter;
                }
                $current_letters_string = strtoupper($current_letters_string); // Shouldn't be needed, but do it anyway

                // If letters have changed, we need to make some changes...
                if ($current_letters != $usable_letters_string) {
                    // TODO - make changes to Letters objects
                    // Populate list of letters
                    $letters = str_split($usable_letters_string);

                    // Include diff code
                    if (!@include_once('inc/diff_match_patch.php')) {
                        if (!@include_once('../inc/diff_match_patch.php')) {
                            require_once('../../inc/diff_match_patch.php');
                        }
                    }

                    $dmp = new diff_match_patch();
                    $diffs = $dmp->diff_main($current_letters_string,$usable_letters_string);
                    $i=0;
                    foreach($diffs as $diff) {
                        // diff_main("Good dog", "Bad dog") => [(-1, "Goo"), (1, "Ba"), (0, "d dog")]
                        switch($diff[0]) {
                            case 0:
                                // Skip this many letters
                                $i += strlen($diff[1]);
                                break;
                            case -1:
                                // Delete this many letters
                                for($ii=0;$ii<strlen($diff[1]);$ii++) {
                                    $current_letters[$i+$ii]->delete();
                                }
                                $i += strlen($diff[1]);
                                break;
                            case 1:
                                // Add this many letters
                                $playlist->makeLetterSpaceAt($i,strlen($diff[1])); // Make space for interstitial letters
                                for($ii=0;$ii<strlen($diff[1]);$ii++) {
                                    // Clone existing letter to keep connection to playlist etc.
                                    $new_letter = $current_letters[0]->clone();
                                    $new_letter->rank = $i+$ii;
                                    $new_letter->letter = strtoupper(substr($diff[1],$ii,1));
                                    $new_letter->save();
                                }
                                break;
                        }
                    }
                }

                header("Location: {$config['root_path']}/playlist/share/{$playlist->id}");
            }
        }
    }
?>
<script type="text/javascript">
    // Default to display name being destination
    $(document).ready(
        function() {
            $('#destination').on('change',function() {
                if ($('#display_name').val() == '') {
                    $('#display_name').val('DP: '+$('#destination').val());
                }
            });
        }
    );
</script>
<h2>Edit <?= $playlist->display_name ?></h2>
<?php
if (count($error_messages)>0) {
    foreach($error_messages as $error_message) {
?>
<div class="row">
    <div class="col-12 alert alert-danger"><?= $error_message ?></div>
</div>
<?php
    }
}
?>
<div class="row">
    <form method="POST">
        <div class="mb-3">
            <label for="destination" class="form-label">What's your destination?</label>
            <input type="text" class="form-control" name="destination" id="destination" aria-describedby="destination-help" value="<?= $playlist->destination ?>">
            <div class="form-text" id="destination-help">This is the word or phrase on which the playlist is based.</div>
        </div>
        <div class="mb-3">
            <label for="display_name" class="form-label">What do you want to call the playlist?</label>
            <input type="text" class="form-control" name="display_name" id="display_name" aria-describedby="display_name-help" value="<?= $playlist->display_name ?>">
            <div class="form-text" id="display_name-help">You can call the playlist something else if you like.</div>
        </div>
        <div class="mb-3 accordion" id="optionsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOptions" aria-expanded="false" aria-controls="collapseOptions">
                        Playlist Options
                    </button>
                </h2>
                <div class="accordion-collapse collapse" data-bs-parent="#optionsAccordion" id="collapseOptions">
                    <fieldset>
                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_STRICT ?>" class="form-control" name="flags[]" id="flags-strict" aria-describedby="flags-strict-help" <?= ($playlist->hasFlags(Playlist::FLAGS_STRICT)?' checked ':'') ?>>
                        <label class="form-check-label" for="flags-strict">Strict mode</label>
                        <div class="form-text" id="flags-strict-help">Should Destination Playlist enforce the rules? If not, users can choose to ignore them.</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_ALLOWTITLE ?>" class="form-control" name="flags[]" id="flags-allow-title" aria-describedby="flags-allow-title-help" <?= ($playlist->hasFlags(Playlist::FLAGS_ALLOWTITLE)?' checked ':'') ?>>
                        <label class="form-check-label" for="flags-allow-title">Track match</label>
                        <div class="form-text" id="flags-allow-title-help">Can the track title be used for the letter match?</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_ALLOWARTIST ?>" class="form-control" name="flags[]" id="flags-allow-artist" aria-describedby="flags-allow-artist-help" <?= ($playlist->hasFlags(Playlist::FLAGS_ALLOWARTIST)?' checked ':'') ?>>
                        <label class="form-check-label" for="flags-allow-artist">Artist match</label>
                        <div class="form-text" id="flags-allow-artist-help">Can the artist name be used for the letter match?</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_THEAGNOSTIC ?>" class="form-control" name="flags[]" id="flags-the-agnostic" aria-describedby="flags-the-agnostic-help" <?= ($playlist->hasFlags(Playlist::FLAGS_THEAGNOSTIC)?' checked ':'') ?>>
                        <label class="form-check-label" for="flags-the-agnostic">"The"-agnostic</label>
                        <div class="form-text" id="flags-the-agnostic-help">Can users ignore the word "The" at the start of a track or artist?</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_INCLUDEDIGITS ?>" class="form-control" name="flags[]" id="flags-include-digits" aria-describedby="flags-include-digits-help" <?= ($playlist->hasFlags(Playlist::FLAGS_INCLUDEDIGITS)?' checked ':'') ?>>
                        <label class="form-check-label" for="flags-include-digits">Include digits</label>
                        <div class="form-text" id="flags-include-digits-help">Include digits as well as letters?</div>
                    </fieldset>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <input type="hidden" value="formsubmitted" name="action" id="action">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>