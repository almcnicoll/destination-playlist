<?php
    // Handle creation
    if (isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] == 'formsubmitted') {
            if (!@include_once('class/user.php')) {
                if (!@include_once('../class/user.php')) {
                    require_once('../../class/user.php');
                }
            }
            if (!@include_once('class/playlist.php')) {
                if (!@include_once('../class/playlist.php')) {
                    require_once('../../class/playlist.php');
                }
            }
            // Now request user data
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

            //echo "<pre>".print_r($listresponse,true)."</pre>\n";

            $playlist = new Playlist();
            $playlist->destination = $_REQUEST['destination'];
            $playlist->spotify_playlist_id = $listresponse['id'];
            $playlist->display_name = $_REQUEST['display_name'];
            $playlist->flags = $_REQUEST['flags'];
            $playlist->user_id = $_SESSION['USER_ID'];
            $playlist->save();

            header("Location: {$get_back}playlist/manage/{$playlist->id}");
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
                        <input class="form-check-input" type="checkbox" value="1" class="form-control" name="flags" id="flags-strict" aria-describedby="flags-strict-help" checked>
                        <label class="form-check-label" for="flags-strict">Strict mode</label>
                        <div class="form-text" id="flags-strict-help">Should Destination Playlist enforce the rules? If not, users can choose to ignore them.</div>

                        <input class="form-check-input" type="checkbox" value="2" class="form-control" name="flags" id="flags-allow-title" aria-describedby="flags-allow-title-help" checked>
                        <label class="form-check-label" for="flags-allow-title">Track match</label>
                        <div class="form-text" id="flags-allow-title-help">Can the track title be used for the letter match?</div>

                        <input class="form-check-input" type="checkbox" value="4" class="form-control" name="flags" id="flags-allow-artist" aria-describedby="flags-allow-artist-help" checked>
                        <label class="form-check-label" for="flags-allow-artist">Artist match</label>
                        <div class="form-text" id="flags-allow-artist-help">Can the artist name be used for the letter match?</div>

                        <input class="form-check-input" type="checkbox" value="8" class="form-control" name="flags" id="flags-the-agnostic" aria-describedby="flags-the-agnostic-help" checked>
                        <label class="form-check-label" for="flags-the-agnostic">"The"-agnostic</label>
                        <div class="form-text" id="flags-the-agnostic-help">Can users ignore the word "The" at the start of a track or artist?</div>
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