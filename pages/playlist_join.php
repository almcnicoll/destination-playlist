<?php
    // Include Playlist class
    if (!@include_once('class/playlist.php')) {
        if (!@include_once('../class/playlist.php')) {
            require_once('../../class/playlist.php');
        }
    }
    // Include participation class
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

    if (count($params)==0) {
        $error_messages[] = "No playlist specified";
        $fatal_error = true;
    }
    
    list($playlist_id,$share_code) = explode('-',$params[0]);
    $playlist_id = (int)$playlist_id;

    $playlist = Playlist::getById($playlist_id);
    if ($playlist == null) {
        $error_messages[] = "Playlist not found";
        $fatal_error = true;
    }

    if (empty($share_code) || ($share_code != $playlist->getShareCode())) {
        $error_messages[] = "Invalid share link";
        $fatal_error = true;
    }

    if ($playlist->hasFlags(Playlist::FLAGS_PEOPLELOCKED)) {
        $error_messages[] = "This playlist is locked to new joiners!";
        $fatal_error = true;
    }

    $participation = Participation::findFirst([['playlist_id','=',$playlist->id],['user_id','=',$_SESSION['USER_ID']]]);
    if ($participation != null) {
        // They're already joined
        if ($participation->removed == 1) {
            $error_messages[] = "Sorry, you have been removed from this playlist. Please talk to the playlist owner if you would like to be reinstated.";
            $fatal_error = true;
        }
    }

?>

<h2 class="text-center" id="title">Joining <?= $playlist->display_name ?>...</h2>
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

// Dynamic content
?>
<script type="text/javascript">
    $(document).ready(
        function() {
            var currTitle = $('#title').text();
            $('#title').text(currTitle.replace(/Joining/,'Joined'));
        }
    );

    
    var timer;
    var script2Url = "<?= $config['root_path'] ?>/ajax/get_letters.php?playlist_id=<?= $playlist->id ?>";
    function updateTrackList(data, textStatus, jqXHR) {
        $('#tracks-table tbody tr').remove();
        for(var i in data) {
            var l = data[i];
            var u = data[i].user;
            $('#tracks-table tbody').append("<tr><td><div class='letter-display'>"+l.letter.toUpperCase()+"</div></td><td>"+l.cached_title+"</td><td>"+l.cached_artist+"</td></tr>");
        }
    }
    var ajax2Options = {
        async: false,
        cache: false,
        success: updateTrackList,
        dataType: 'json',
        method: 'GET',
        timeout: 4000
    };
    function getLetters() {
        $.ajax(script2Url, ajax2Options);
        timer = setTimeout('getLetters()',5000);
    }
    $(document).ready(
        function () {
            //timer = setTimeout('getParticipants()',5000);
            getLetters();
        }
    );
</script>

<table class="table table-light table-striped" id="tracks-table">
    <!--<thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </thead>-->
    <tbody>
    </tbody>
</table>
<?php