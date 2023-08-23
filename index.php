<?php
require_once('inc/login_check.php');
require_once('class/user.php');

if (isset($_REQUEST['newname'])) {
    $user = $_SESSION['user'];
    $user->display_name = $_REQUEST['newname'];
    $user->save();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login to Destination Playlist</title>
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
<?php
}
?>
</body>
</html>