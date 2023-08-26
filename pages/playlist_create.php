<?php
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

    // If form submitted, handle creation
    if (isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] == 'formsubmitted') {
            if (!@include_once('class/user.php')) {
                if (!@include_once('../class/user.php')) {
                    require_once('../../class/user.php');
                }
            }
            if (!@include_once('class/letter.php')) {
                if (!@include_once('../class/letter.php')) {
                    require_once('../../class/letter.php');
                }
            }
            // Create playlist on spotify
            $user = $_SESSION['USER'];
            $endpoint = "https://api.spotify.com/v1/users/{$user->identifier}/playlists";
            $ch = curl_init($endpoint);
            $options = [
                'name'              => $_REQUEST['display_name'],
                'public'            => false,
                'collaborative'     => true,
                'description'       => "Created by Destination Playlist: ".date('jS M Y, H:i'),
            ];
            $url = $endpoint;
            curl_setopt_array ( $ch, array (
                CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$_SESSION['USER_ACCESSTOKEN'],'Content-type: application/json'],
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => json_encode($options),
            ) );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($ch);
            $listresponse = json_decode($result, true);
            curl_close($ch);

            if (isset($listresponse['error'])) {
                // Show the error
                $error_messages[] = $listresponse['error'];
            } else {
                // Create playlist in db
                $playlist = new Playlist();
                $playlist->destination = $_REQUEST['destination'];
                $playlist->spotify_playlist_id = $listresponse['id'];
                $playlist->display_name = $_REQUEST['display_name'];
                $playlist->flags = $_REQUEST['flags'];
                $playlist->user_id = $_SESSION['USER_ID'];
                $playlist->save();

                // Populate list of letters
                $letters = [];
                if ($playlist->hasFlags(Playlist::FLAGS_INCLUDEDIGITS)) {
                    $letters = str_split( strtoupper(preg_replace('/[^\w\d]+/i','',$playlist->destination)) , 1);
                } else {
                    $letters = str_split( strtoupper(preg_replace('/[^\w]+/i','',$playlist->destination)) , 1);
                }

                // Create letters in db
                foreach ($letters as $letter) {
                    $l = new Letter ();
                    $l->playlist_id = $playlist->id;
                    $l->user_id = $_SESSION['USER_ID'];
                    $l->letter = $letter;
                    $l->save();
                }

                header("Location: {$get_back}playlist/share/{$playlist->id}");
            }
        }
    }

    $destination_placeholders = [
        "Alexander Road London",
        "Kings Road Sheffield",
        "Highfield Road Kilmarnock",
        "Canterbury Cathedral",
        "Uluru National Park",
        "The Great Pyramid of Giza",
        "The Mausoleum at Halicarnassus",
        "The Channel Tunnel",
        "Øresund Bridge",
        "Soda Springs Idaho",
        "Truth or Consequences New Mexico",
        "Dinosaur Colorado",
        "Uncertain Volunteer Fire Department Karnack Texas",
        "Llanfairpwllgwyngyllgogerychwyrndrobwllllantysiliogogogoch",
        "Taumatawhakatangihangakoauauotamateaturipukakapiki-maungahoronukupokaiwhenuakitnatahu Porangahau New Zealand",
        "Just around the corner",
        "The Magellanic Clouds",
    ];
    $i = array_rand($destination_placeholders,1);
    $destination_placeholder = $destination_placeholders[$i];
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
<h2>Create a playlist</h2>
<?php
if (count($error_messages)>0) {
    foreach($error_messages as $error_message) {
?>
<div class="row">
    <div class="span12 alert alert-danger"><?= $error_message ?></div>
</div>
<?php
    }
}
?>
<div class="row">
    <form method="POST">
        <div class="mb-3">
            <label for="destination" class="form-label">What's your destination?</label>
            <input type="text" class="form-control" name="destination" id="destination" placeholder="<?= $destination_placeholder ?>" aria-describedby="destination-help">
            <div class="form-text" id="destination-help">This is the word or phrase on which the playlist is based.</div>
        </div>
        <div class="mb-3">
            <label for="display_name" class="form-label">What do you want to call the playlist?</label>
            <input type="text" class="form-control" name="display_name" id="display_name" aria-describedby="display_name-help">
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
                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_STRICT ?>" class="form-control" name="flags" id="flags-strict" aria-describedby="flags-strict-help" checked>
                        <label class="form-check-label" for="flags-strict">Strict mode</label>
                        <div class="form-text" id="flags-strict-help">Should Destination Playlist enforce the rules? If not, users can choose to ignore them.</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_ALLOWTITLE ?>" class="form-control" name="flags" id="flags-allow-title" aria-describedby="flags-allow-title-help" checked>
                        <label class="form-check-label" for="flags-allow-title">Track match</label>
                        <div class="form-text" id="flags-allow-title-help">Can the track title be used for the letter match?</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_ALLOWARTIST ?>" class="form-control" name="flags" id="flags-allow-artist" aria-describedby="flags-allow-artist-help" checked>
                        <label class="form-check-label" for="flags-allow-artist">Artist match</label>
                        <div class="form-text" id="flags-allow-artist-help">Can the artist name be used for the letter match?</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_THEAGNOSTIC ?>" class="form-control" name="flags" id="flags-the-agnostic" aria-describedby="flags-the-agnostic-help" checked>
                        <label class="form-check-label" for="flags-the-agnostic">"The"-agnostic</label>
                        <div class="form-text" id="flags-the-agnostic-help">Can users ignore the word "The" at the start of a track or artist?</div>

                        <input class="form-check-input" type="checkbox" value="<?= Playlist::FLAGS_INCLUDEDIGITS ?>" class="form-control" name="flags" id="flags-include-digits" aria-describedby="flags-include-digits-help">
                        <label class="form-check-label" for="flags-include-digits">Include digits</label>
                        <div class="form-text" id="flags-include-digits-help">Include digits as well as letters?</div>
                    </fieldset>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <input type="hidden" value="formsubmitted" name="action" id="action">
            <button type="submit" class="btn btn-primary">Create!</button>
        </div>
    </form>
</div>