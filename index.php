<?php
require_once('inc/login_check.php');
require_once('class/user.php');
require_once('class/playlist.php');

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>

<?php
$user = $_SESSION['USER'];
if ($user->display_name) {
?>
<div class="row">
    <div class="span12">
        <h1>Welcome, <?= $user->display_name ?></h1>
    </div>
</div>
<?php
} else {
?>
<div class="row">
    <div class="span12">
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
<?php
// List all playlists
$criteria = [['user_id','=',$_SESSION['USER_ID']],];
$my_playlists = Playlist::find($criteria);
if (count($my_playlists)==0) {
?>

<div class="row">
    <div class="span12">
        <h2>You don't have any playlists. How sad!</h2>
        <h3>Click below to create one.</h3>
        <a class="btn btn-primary" href="playlist/create">Create</a>
    </div>
</div>
<?php
} else {
?>
<table class="table table-striped table-hover">
    <tbody>
<?php
    foreach ($my_playlists as $playlist) {
        echo "<tr>\n";
        echo "<th scope='row'>{$playlist->display_name}</th>\n";
        echo "<td>{$playlist->destination}</td>\n";
        echo "<td>Actions...</td>\n";
        echo "</tr>\n";
    }
?>
    </tbody>
</table>
<?php
}
?>
</body>
</html>