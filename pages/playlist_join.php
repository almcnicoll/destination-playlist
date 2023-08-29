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
    $playlist_id = (int)$params[0];

    $playlist = Playlist::getById($playlist_id);
    if ($playlist == null) {
        $error_messages[] = "Playlist not found";
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
</script>
<?php