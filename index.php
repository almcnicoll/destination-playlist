<?php
$login_check_redirect_on_fail = "/dp/intro";
require_once('inc/login_check.php');
require_once('class/user.php');
require_once('class/playlist.php');
require_once('class/participation.php');

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
    <title>Destination Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.7.0.slim.js" integrity="sha256-7GO+jepT9gJe9LB4XFf8snVOjX3iYNb0FHYr5LI1N5c=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>

<?php
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
<h2 class='card-title'>Your Playlists</h2>
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
?>

<div class="row">
    <div class="col-12">
        <h3>You don't have any playlists. How sad!</h3>
        <h4>Click below to create one.</h4>
        <a class="btn btn-primary" href="playlist/create">Create</a>
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
    foreach ($my_playlists as $playlist) {
        echo "<tr style='vertical-align: middle;'>\n";
        echo "<th scope='row'>{$playlist->display_name}</th>\n";
        echo "<td>{$playlist->destination}</td>\n";
        echo "<td>";
        echo "<a href='playlist/share/{$playlist->id}' class='btn btn-md btn-success'>Share</a>";
        echo "<a href='playlist/manage/{$playlist->id}' class='btn btn-md btn-primary m-2'>Manage</a>";
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
            echo "<a href='playlist/join/".$playlist->getShareCode()."' class='btn btn-md btn-success'>View</a>";
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
    
</body>
</html>