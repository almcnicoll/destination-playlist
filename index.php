<?php
require_once('autoload.php');

$login_check_redirect_on_fail = "/dp/intro";
require_once('inc/login_check.php');

if (isset($_REQUEST['newname'])) {
    $user = $_SESSION['USER'];
    $user->display_name = $_REQUEST['newname'];
    $user->save();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (substr($config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Destination Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>
    <script type='text/javascript'>
        var root_path = "<?= $config['root_path']; ?>";
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <script src='js/delete_handler.js'></script>
<?php
//echo "<!-- SERVER DOC ROOT: {$_SERVER['DOCUMENT_ROOT']} -->";
require_once('inc/header.php');

$user = $_SESSION['USER'];
if ($user->display_name) {
?>
<div class="row">
    <div class="col-12">
        <h1>Welcome, <?= $user->display_name ?></h1>
    </div>
</div>
<?php
} else {
?>
<div class="row">
    <div class="col-12">
        <h1>Welcome!</h1>
    </div>
</div>
<form method="POST">
    <div class="mb-3">
        <label for="newname" class="form-label">What's your name?</label>
        <input type="text" class="form-control" name="newname" id="newname" aria-describedby="newname-help">
        <div class="form-text" id="newname-help">It's helpful to have your name!</div>        
        <button type="submit" class="btn btn-primary">Update!</button>
    </div>
</form>
<hr />
<?php
}
?>
<div class='card text-bg-dark'>
<div class='card-body'>
<?php
// List all playlists
$criteriaMine = ['user_id','=',$_SESSION['USER_ID']];
$my_playlists = Playlist::find($criteriaMine);
// TODO - list playlists which they have (actively) joined
$my_participations = Participation::find($criteriaMine);
$joined_playlist_ids = [];
foreach ($my_participations as $part) {
    $joined_playlist_ids[$part->playlist_id] = true;
}
$criteriaJoined = [['id','IN',array_keys($joined_playlist_ids)],];
$joined_playlists = Playlist::find($criteriaJoined);

if (count($my_playlists)==0) {
    // No playlists of our own
?>

<h2 class='card-title'>Your Playlists</h2>
<div class="row">
    <div class="col-12">
        <h3>You don't have any playlists. How sad!</h3>
        <h4>Click below to create one.</h4>
        <a class="btn btn-primary" href="playlist/create">Create</a>
    </div>
</div>
<?php
} else {
    // At least one playlist of our own
?>
<h2 class='card-title'>Your Playlists <a class="btn btn-primary mb-1" href="<?= $config['root_path'] ?>/playlist/create">+ New</a></h2>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Playlist</th>
            <th>Destination</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach ($my_playlists as $playlist) {
        echo "<tr style='vertical-align: middle;'>\n";
        echo "<th scope='row'>{$playlist->display_name}</th>\n";
        echo "<td>{$playlist->destination}</td>\n";
        echo "<td>";
        echo "<a href='playlist/manage/{$playlist->id}' class='btn btn-md btn-success m-2'><span class='bi bi-eye'></span></a>";
        echo "<a href='playlist/share/{$playlist->id}' class='btn btn-md btn-warning'><span class='bi bi-share'></span></a>";
        echo "<a href='playlist/edit/{$playlist->id}' class='btn btn-md btn-warning m-2'><span class='bi bi-pencil-square' role='edit'></span></a>";
        echo "<a href='#' class='btn btn-md btn-danger m-2' data-bs-toggle='modal' data-bs-target='#playlistDeleteModal' role='delete' onclick='deleteHandler.idToDelete = {$playlist->id};'><span class='bi bi-trash3'></span></a>";
        echo "</td>\n";
        echo "</tr>\n";
    }
?>
    </tbody>
</table>
<?php
}
?>
</div> <!-- CARD-BODY -->
</div> <!-- CARD -->
<br /><hr /><br />
<div class='card text-bg-dark'>
<div class='card-body'>
<h2 class='card-title'>Joined Playlists</h2>
<?php
if (count($joined_playlists)==0) {
    ?>
    
    <div class="row">
        <div class="col-12">
            <h4>You haven't joined any playlists.</h4>
        </div>
    </div>
    <?php
    } else {
    ?>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Playlist</th>
                <th>Destination</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    <?php
        foreach ($joined_playlists as $playlist) {
            echo "<tr style='vertical-align: middle;'>\n";
            echo "<th scope='row'>{$playlist->display_name}</th>\n";
            echo "<td>{$playlist->destination}</td>\n";
            echo "<td>";
            echo "<a href='playlist/join/".$playlist->getShareCode()."' class='btn btn-md btn-success m-2'><span class='bi bi-eye'></span></a>";
            echo "</td>\n";
            echo "</tr>\n";
        }
    ?>
        </tbody>
    </table>
    <?php
    }
?>
</div> <!-- CARD-BODY -->
</div> <!-- CARD -->

<div class="modal fade" id="playlistDeleteModal" tabindex="-1">
  <div class="modal-dialog .modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Playlist</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="deleteModalCloseX"></button>
      </div>
      <div class="modal-body">
      <div class="row">
        <div class="col-12">
            Where would you like to delete the playlist from?
        </div>
      </div>
      <div class="row">
        <div class="col-4-md p-2">
            <a class='btn btn-md btn-warning' id='deleteHere' style='width: 100%;'>Just here</a>
        </div>
        <div class="col-4-md p-2">
            <a class='btn btn-md btn-danger' id='deleteBoth' style='width: 100%;'>Here and on Spotify</a>
        </div>
        <div class="col-4-md p-2" id='search-results-container'>
            <button class='btn btn-md btn-success' id='deleteCancel' data-bs-dismiss="modal" style='width: 100%;'>Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>
    
</body>
</html>