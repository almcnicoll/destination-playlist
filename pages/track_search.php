<?php
    // Include Participation class
    if (!@include_once('class/participation.php')) {
        if (!@include_once('../class/participation.php')) {
            require_once('../../class/participation.php');
        }
    }

    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $playlist_id = null;
    if (count($params)==0) {
        $error_messages[] = "No playlist specified";
        $fatal_error = true;
    } else {
        $playlist_id = $params[0];
    }

    if (!empty($playlist_id)) {
        $playlist = Playlist::getById($playlist_id);
        if ($playlist == null) {
            $error_messages[] = "Playlist not found";
            $fatal_error = true;
        }
    }

    $participation = Participation::findFirst([['playlist_id','=',$playlist->id],['user_id','=',$_SESSION['USER_ID']]]);
    if ($participation == null) {
        // They're not part of this playlist
        $error_messages[] = "Sorry, you have not joined this playlist. Please talk to the playlist owner to join.";
        $fatal_error = true;
    } else {
        if ($participation->removed == 1) {
            // They've been removed
            $error_messages[] = "Sorry, you have been removed from this playlist. Please talk to the playlist owner if you would like to be reinstated.";
            $fatal_error = true;
        }
    }
    
    ?>
<script type="text/javascript">
    if (typeof trackSearch === 'undefined') { trackSearch = {}; }
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
    echo "trackSearch.token = \"{$_SESSION['USER_ACCESSTOKEN']}\";\n\n";
    $user = $_SESSION['USER'];
    echo "/*\n".print_r($user,true)."\n*/\n\n";
    echo "trackSearch.market = \"{$user->market}\";\n\n";
    echo "trackSearch.playlist_id = \"{$playlist->id}\";\n\n";
    echo "root_path = \"{$config['root_path']}\";\n\n";
    ?>
</script>
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/search_mgmt.js'></script>
<script type='text/javascript'>
    // TODO - move this to search_mgmt.js - need to work out how to get PHP inserts into it
    trackSearch.updateSearchBoxCustom = function(data, textStatus, jqXHR) {
        output = '';
        for(var i in data.tracks.items) {
            var t = data.tracks.items[i];
            output += "<li class='list-group-item'>"+t.name+"</li>";
        }
        $('#search-results-container').html("<ul class='list-group'>"+output+"</ul>");
    }

    trackSearch.init('#track-search-box');
</script>

<div class="card" style="max-width: 16em;">
    <div class="row">
        <div class="col-12">
            <input type="text" placeholder="Type here to search..." id="track-search-box">
        </div>
        <div class="col-12" style="min-height: 10em;" id='search-results-container'>
        </div>
    </div>
</div>

<!--
    <div class="dropdown">
    <a class="btn btn-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        Dropdown link
    </a>

    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Action</a></li>
        <li><a class="dropdown-item" href="#">Another action</a></li>
        <li><a class="dropdown-item" href="#">Something else here</a></li>
    </ul>
    </div>
-->

    <form>

    </form>