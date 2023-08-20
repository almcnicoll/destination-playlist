<!DOCTYPE html>
<?php
    require_once('class/authmethod.php');
?>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login to Destination Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
</head>
<body>    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <div class="row text-center">
        <div class="span12">
            <h1>Login page</h1>
        </div>
    </div>
    <?php
    $auth_methods = AuthMethod::getAll();
    foreach ($auth_methods as $auth_method) {
        echo "<div class='row text-center'>\n";
        echo "<div class='span12'><a class='btn btn-lg' href='{$auth_method->handler}'><img src='{$auth_method->image}' height='60' /></a></div>\n";
        echo "</div>\n";
    }
    ?>
</body>
</html>