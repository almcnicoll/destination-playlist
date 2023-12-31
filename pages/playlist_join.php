<?php
    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (count($params)==0) {
        $error_messages[] = "No playlist specified";
        $fatal_error = true;
    }

    if (!isset($_SESSION['USER_ID'])) {
        $error_messages[] = "You are not logged in. Please refresh the page.";
        $fatal_error = true;
        header('Location: '.$config['root_path'].'/login.php?redirect_url='.$_SERVER['REQUEST_URI']);
        die();
    }
    
    list($playlist_id,$auth_suffix) = explode('-',$params[0]);
    $playlist_id = (int)$playlist_id;

    $playlist = Playlist::getById($playlist_id);
    if ($playlist == null) {
        $error_messages[] = "Playlist not found";
        $fatal_error = true;
    } else {
        if (empty($auth_suffix) || ($params[0] != $playlist->getShareCode())) {
            $error_messages[] = "Invalid share link";
            $fatal_error = true;
        }

        if ($playlist->hasFlags(Playlist::FLAGS_PEOPLELOCKED)) {
            $error_messages[] = "This playlist is locked to new joiners!";
            $fatal_error = true;
        }

        if ($playlist->user_id == $_SESSION['USER_ID']) {
            // Can't join a playlist you own
            header('Location: '.$config['root_path']."/playlist/manage/{$playlist->id}?".http_build_query(['error_message' => "You don't need to join this playlist: you created it!"]));
            die();
        }

        $participation = Participation::findFirst([['playlist_id','=',$playlist->id],['user_id','=',$_SESSION['USER_ID']]]);
        if ($participation != null) {
            // They're already joined
            if ($participation->removed == 1) {
                $error_messages[] = "Sorry, you have been removed from this playlist. Please talk to the playlist owner if you would like to be reinstated.";
                $fatal_error = true;
            }
        }
    }

?>

<div class='top-left-menu'><a href="<?= $config['root_path'] ?>" class='btn btn-warning btn-md'><< Back</a></div>
<h2 class="text-center" id="title"><span id='titleText'>Joining playlist <?= $playlist->display_name ?>...</span> <a href='#' id='play-button' data-bs-toggle='modal' data-bs-target='#playDevicesModal' onclick="playHandler.getDevices();"><span class='bi bi-play-circle'></span></a></h2></h2>
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

<?php
if ($fatal_error) {
    die();
}

if ($participation == null) {
    // Create participation record
    $participation = new Participation();
    $participation->user_id = $_SESSION['USER_ID'];
    $participation->playlist_id = $playlist->id;
    $participation->save();
}

// Follow the playlist, whether or not participation record exists, in case it's been unfollowed
$srFollow = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT, "https://api.spotify.com/v1/playlists/".$playlist->spotify_playlist_id."/followers");
$dataFollow = [
    'public' => false,
];
$srFollow->send($dataPublic);

// Dynamic content
?>
<!-- Set vars -->
<script type="text/javascript">
    if (typeof trackSearch === 'undefined') { trackSearch = {}; }
    if (typeof playHandler === 'undefined') { var playHandler = {}; }
    <?php
    if(isset($playlist_id) && !$playlist->hasFlags(Playlist::FLAGS_ALLOWTITLE)) {
        echo "trackSearch.allow_title=false;\n";
    } else {
        echo "trackSearch.allow_title=true;\n";
    }
    if(isset($playlist_id) && !$playlist->hasFlags(Playlist::FLAGS_ALLOWARTIST)) {
        echo "trackSearch.allow_artist=false;\n";
    } else {
        echo "trackSearch.allow_artist=true;\n";
    }
    if(isset($playlist_id) && $playlist->hasFlags(Playlist::FLAGS_STRICT)) {
        echo "trackSearch.strict_mode=true;\n";
    } else {
        echo "trackSearch.strict_mode=false;\n";
    }
    if(isset($playlist_id) && $playlist->hasFlags(Playlist::FLAGS_THEAGNOSTIC)) {
        echo "trackSearch.the_agnostic=true;\n";
    } else {
        echo "trackSearch.the_agnostic=false;\n";
    }
    echo "trackSearch.token = \"{$_SESSION['USER_ACCESSTOKEN']}\";\n";
    $user = $_SESSION['USER'];
    //echo "/*\n".print_r($user,true)."\n*/\n\n";
    echo "trackSearch.market = \"{$user->market}\";\n";
    echo "trackSearch.playlist_id = \"{$playlist->id}\";\n\n";
    echo "var root_path = \"{$config['root_path']}\";\n";
    echo "var playlist_id = {$playlist->id};\n";
    echo "var currentUser = {$_SESSION['USER_ID']};\n";
    echo "playHandler.playlist_id = \"{$playlist->id}\";\n\n";
    echo "playHandler.spotify_playlist_id = \"{$playlist->spotify_playlist_id}\";\n\n";
    ?>
</script>
<!-- Include search script -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/search_mgmt.js'></script>
<!-- Include letter-refresh script -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/letter_refresh.js'></script>
<!-- Include play-devices script -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/play_handler.js'></script>
<!-- Include letter-unassign script (part of letter-assign script) -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/letter_assign.js'></script>
<!-- Custom callback functions -->
<script type='text/javascript'>
    playHandler.init('#playDevicesContainer');
    letterAssigner.init(); // No target as we're UN-assigning only
    letterGetter.updateLettersCustom = function(data, textStatus, jqXHR) {
        $('#tracks-table tbody tr').remove();
        // Manage errors or good data
        if ('errors' in data) {            
            $('#tracks-table tbody tr').remove();
            for(var i in data.errors) {
                $('#tracks-table tbody').append("<tr><td class='error'><div class='error'>"+data.errors[i]+"</td></tr>");
            }
        } else {
            var letterData = data.result;
            for(var i in letterData) {
                var l = letterData[i];
                var user_display = "";
                var edit_own = "";
                if ((letterData[i].user_id != null) && (letterData[i].user_id != 'null')) {
                    var u = letterData[i].user;
                    user_display = "<div class='initial-display'>"+u.display_name.substr(0,1)+"</div>";
                    if (u.id == currentUser) {
                        edit_own = "<a href='#' id='edit-track-"+i+"' class='btn' data-bs-toggle='modal' data-bs-target='#trackSearchModal' onclick=\"trackSearch.search_letter = '"
                                    +l.letter.toUpperCase()+"'; letter_id = "+l.id+"; $('#beginning-with-letter').html('&nbsp;"
                                    +l.letter.toUpperCase()+"');\"><span class='bi bi-pencil-square'></span></a>";
                        var unassignLink = "<a href='#' class='unassign-letter text-danger' data-letter-id='"+l.id+"' title='Give up this track'><span class='bi bi-x-circle'></span></a>&nbsp;";
                        user_display = "<div class='initial-display'>"+unassignLink+u.display_name.substr(0,1)+"</div>"
                                        +"<div class='name-display'>"+unassignLink+u.display_name+"</div>";
                        // Allow populated tracks to be cleared
                        if (l.cached_title!='' && l.cached_artist!='') {
                            edit_own += "<a href='#' id='clear-track-"+i+"' class='btn clear-track text-danger' data-letter-id='"+l.id+"'><span class='bi bi-x-circle'></span></a>";
                        }
                    }
                }
                $('#tracks-table tbody').append("<tr><td class='letter-display'><div class='letter-display'>"+l.letter.toUpperCase()+"</div></td><td>"+l.cached_title+"</td><td>"+l.cached_artist+"</td><td class='initial-display'>"+user_display+"</td><td>"+edit_own+"</td></tr>");
            }
        }
    }

    trackSearch.updateSearchBoxCustom = function(data, textStatus, jqXHR) {
        var output = '';
        var isAppend = false;
        if ('tracks' in data) {
            isAppend = (data.tracks.offset > 0);
        }
        if (!isAppend) {
            output = "<li class='list-group-item fs-6'>Results from <img src='"+root_path+"/img/Spotify_Logo_small_RGB_Black.png' style='height: 1em;'></li>";
            output += "<li class='list-group-item fs-6 fst-italic visually-hidden' id='hidden-results-count-container'><span id='hidden-results-count'></span> results hidden because they don't begin with <span id='must-begin-with'></span></li>";
        }
        if (('tracks' in data) && ('items' in data.tracks)) {
            // Loop through the tracks
            for(var i in data.tracks.items) {
                var t = data.tracks.items[i];
                var t_a = t.artists;
                var artistNames = new Array();
                for (var ii in t.artists) {
                    artistNames.push(t.artists[ii].name);
                }
                var explicit_class = ((t.explicit)?'bi bi-exclamation-diamond warning':'bi bi-file-earmark-music primary');
                var explicit_title = ((t.explicit)?" title='Explicit lyrics' ":'');
                t.artist_string = artistNames.join(' // ');
                output += "<li class='list-group-item track validating'><span class='"+explicit_class+"'"+explicit_title+"></span>&nbsp;<a href='#' class='search-result' data-track-id='"
                        +t.id+"' data-preview-url='"+t.preview_url+"' data-track-title=\""+encodeURIComponent(t.name)
                        +"\" data-track-artists=\""+encodeURIComponent(t.artist_string)+"\">"+t.name+" ("+t.artist_string+")</a></li>";
            }
        }
        // Now determine whether to append or overwrite
        if (isAppend) {
            $('#search-results-container ul').append(output);
        } else {
            $('#search-results-container').html("<ul class='list-group'>"+output+"</ul>");
        }
        // Now determine whether they are valid options to select
        trackSearch.validateTracks('#search-results-container li.track');
    }
    trackSearch.handleSearchClickCustom = function(clickedElement) {

    }
    trackSearch.handleTrackUpdateSuccessCustom = function() {
        $('#trackSearchModalCloseX').trigger('click');
        // Refresh immediately
        clearTimeout(letterGetter.timer);
        letterGetter.getLetters();
    }

    // Initialisations
    trackSearch.init('#track-search-box','#search-results-container');
    letterGetter.init(500,10000,8000);
    
</script>
<script type="text/javascript">
    $(document).ready(
        function() {
            var currTitle = $('#titleText').text();
            $('#titleText').text(currTitle.replace(/Joining/,'Joined'));
            
            // Modal focus
            document.getElementById('trackSearchModal').addEventListener('shown.bs.modal', () => {
                document.getElementById('track-search-box').focus()
            });
        }
    );
</script>

<table class="table table-light table-striped neat" id="tracks-table">
    <tbody>
        <tr>
            <td class='loading'>Loading</td>
        </tr>
    </tbody>
</table>

<div class="modal fade" id="trackSearchModal" tabindex="-1">
  <div class="modal-dialog .modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Track search</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="trackSearchModalCloseX"></button>
      </div>
      <div class="modal-body">
      <div class="row mb-1">
        <div class='col-12 fs-6 fst-italic' id='search-protip'>&nbsp;</div>
      </div>
      <div class="row">
        <div class="col-12">
            <div class='input-group'>
                <div class="input-group-prepend">
                    <span class="input-group-text">Beginning with<span id='beginning-with-letter' class='text-primary'></span></span>
                </div>
                <input type="text" class='form-control' autocomplete="off" placeholder="Type here to search..." id="track-search-box">
                <div class="input-group-append">
                    <button class="btn btn-outline-warning" type="button" onclick="$('#track-search-box').val(''); $('#track-search-box').focus();"><span class='bi bi-x-circle'></span></button>
                </div>
                            <div class='input-group-append'>
            <div class="spinner-border spinner-border-sm text-primary hidden" id="search_spinner" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
                        </div>
                    </div>
        <div class="col-12" style="min-height: 10em;" id='search-results-container'>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="playDevicesModal" tabindex="-1">
    <div class="modal-dialog .modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Play <?= $playlist->display_name ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="playDevicesModalCloseX"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12" id='playDevicesContainer'>Loading</div>
                </div>
            </div>
        </div>
    </div>
</div>
