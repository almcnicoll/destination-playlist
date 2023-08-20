<!DOCTYPE html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login to Destination Playlist</title>
    <link href="css/bootstrap.min.css" cdn-src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js" cdn-src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" type="text/javascript"></script>
</head>
<?php
    require_once('class/authmethod.php');
?>
<html lang="en">
    <div class="row">
        <div class="span12">
            <h1>Login page</h1>
        </div>
    </div>
    <?php
    $auth_methods = AuthMethod::getAll();
    foreach ($auth_methods as $auth_method) {
        echo "<div class='row'>\n";
        echo "<div class='span12'><img src='{$auth_method->image}' /></div>\n";
        echo "</div>\n";
    }
    ?>
</html>