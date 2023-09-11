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
    var root_path = "<?= $config['root_path'] ?>";
    var playlist_id = "<?= $playlist->id ?>";
    var currentUser = <?= $_SESSION['USER_ID'] ?>;
</script>
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/letter_refresh.js'></script>
<script type="text/javascript">
    $(document).ready(
        function() {
            var currTitle = $('#title').text();
            $('#title').text(currTitle.replace(/Joining/,'Joined'));
        }
    );
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