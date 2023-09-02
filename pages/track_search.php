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
        $error_messages[] = "Sorry, you have nto joined this playlist. Please talk to the playlist owner to join.";
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
    <?php
    if(isset($playlist_id) && !$playlist->hasFlags(Playlist::FLAGS_ALLOWTITLE)) {
        echo "var allow_title=false;\n";
    } else {
        echo "var allow_title=true;\n";
    }
    if(isset($playlist_id) && !$playlist->hasFlags(Playlist::FLAGS_ALLOWARTIST)) {
        echo "var allow_artist=false;\n";
    } else {
        echo "var allow_artist=true;\n";
    }
    if(isset($playlist_id) && $playlist->hasFlags(Playlist::FLAGS_STRICT)) {
        echo "var strict_mode=true;\n";
    } else {
        echo "var strict_mode=false;\n";
    }
    echo "var t = \"{$_SESSION['USER_ACCESSTOKEN']}\";\n\n";
    ?>
    
    var ajax3Options = {
        async: true,
        cache: false,
        success: updateSearchBox,
        dataType: 'json',
        method: 'GET',
        timeout: 10000,
        headers: {
            Authorization: 'Bearer '+t,
            'Content-type': 'application/json'
        }
    };
    function search_request(query,resultType,userMarket,resultLimit) {
        ajax3Options.data = {
            q: query,
            type: resultType,
            market: userMarket,
            limit: resultLimit,
            playlist_id: <?= $playlist->id ?>
        };
        $.ajax('<?= $config['root_path'] ?>/ajax/proxy_search.php', ajax3Options);
    }
    function updateSearchBox(data, textStatus, jqXHR) {
        output = '';
        for(var i in data.tracks.items) {
            var t = data.tracks.items[i];
            output += "<li>"+t.name+"</li>";
        }
        $('#search-results-container').html("<ul>"+output+"</ul>");
    }

    $(document).ready(function() {
        $('#track-search-box').on('keyup',function() {
            var txt=$(this).val();
            if (txt.length > 3) {
                var querystring = '';
                if (allow_title && allow_artist) {
                    querystring = encodeURIComponent(txt);
                } else if (allow_title) {
                    querystring = encodeURIComponent("track:"+txt);
                } else if (allow_artist) {
                    querystring = encodeURIComponent("artist:"+txt);
                } else {
                    // Not sure what to do here!
                    querystring = encodeURIComponent(txt);
                }
                search_request(querystring,'track','GB',20);
            }
        })
    });
</script>

<div class="card" style="max-width: 16em;">
    <div class="row">
        <div class="col-12">
            <input type="text" placeholder="Type here to search..." id="track-search-box">
        </div>
        <div class="col-12" style="min-height: 10em;" id='search-results-container'>
            <ul class="list-group">
                <li class="list-group-item">An item</li>
                <li class="list-group-item">A second item</li>
                <li class="list-group-item">A third item</li>
                <li class="list-group-item">A fourth item</li>
                <li class="list-group-item">And a fifth one</li>
            </ul>
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