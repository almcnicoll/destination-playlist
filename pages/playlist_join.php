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
    
    list($playlist_id,$auth_suffix) = explode('-',$params[0]);
    $playlist_id = (int)$playlist_id;

    $playlist = Playlist::getById($playlist_id);
    if ($playlist == null) {
        $error_messages[] = "Playlist not found";
        $fatal_error = true;
    }

    /*var_dump($params,$playlist_id,$auth_suffix,$playlist->getShareCode());
    die();*/

    if (empty($auth_suffix) || ($params[0] != $playlist->getShareCode())) {
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

<h2 class="text-center" id="title">Joining playlist <?= $playlist->display_name ?>...</h2>
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
    var currentUser = <?= $_SESSION['USER_ID'] ?>;
    $(document).ready(
        function() {
            var currTitle = $('#title').text();
            $('#title').text(currTitle.replace(/Joining/,'Joined'));
        }
    );

    
    var timer2;
    var script2Url = "<?= $config['root_path'] ?>/ajax/get_letters.php?playlist_id=<?= $playlist->id ?>";
    function updateTrackList(data, textStatus, jqXHR) {
        $('#tracks-table tbody tr').remove();
        for(var i in data) {
            var l = data[i];
            var user_display = "";
            var edit_own = "";
            if ((data[i].user_id != null) && (data[i].user_id != 'null')) {
                var u = data[i].user;
                user_display = "<div class='initial-display'>"+u.display_name.substr(0,1)+"</div>";
                if (u.id == currentUser) {
                    edit_own = "<a href='#' id=''><span class='bi bi-pencil-square'></span></a>";
                }
            }
            $('#tracks-table tbody').append("<tr><td class='letter-display'><div class='letter-display'>"+l.letter.toUpperCase()+"</div></td><td>"+l.cached_title+"</td><td>"+l.cached_artist+"</td><td class='initial-display'>"+user_display+"</td><td>"+edit_own+"</td></tr>");
        }
    }
    var ajax2Options = {
        async: true,
        cache: false,
        success: updateTrackList,
        dataType: 'json',
        method: 'GET',
        timeout: 4000
    };
    function getLetters() {
        $.ajax(script2Url, ajax2Options);
        timer2 = setTimeout('getLetters()',5000);
    }
    $(document).ready(
        function () {
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