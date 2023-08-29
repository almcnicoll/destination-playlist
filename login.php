<!DOCTYPE html>
<?php
    require_once('class/authmethod.php');
    if (isset($_REQUEST['redirect_url'])) {
        $_SESSION['redirect_url_once'] = $_REQUEST['redirect_url'];
    }
?>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login to Destination Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <div class="row text-center">
        <div class="col-12">
            <h1>Login page</h1>
        </div>
    </div>
    <?php
if(isset($_REQUEST['error'])):
    ?>
    <div class="row text-center">
        <div class="col-12">
            <div class="alert alert-warning"><?php echo $_REQUEST['error']; ?></div>
        </div>
    </div>
    <?php
endif;
    $auth_methods = AuthMethod::getAll();
    foreach ($auth_methods as $auth_method) {
        echo "<div class='row text-center'>\n";
        echo "<div class='col-12'><a class='btn btn-lg' href='{$auth_method->handler}'><img src='{$auth_method->image}' height='60' /></a></div>\n";
        echo "</div>\n";
    }
    ?>
</body>
</html>