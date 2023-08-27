<?php
// NB - try to get rid of $get_back - it's unreliable. Use $config['root_path'] instead

$page_parts = explode('/', $_GET['params']);
$params = [];
//print_r($page_parts);
// If no params, redirect to index
$get_back = '../';
if (count($page_parts)==0) {
    header('Location: ./');
    die();
} elseif (count($page_parts)==1) {
    $page_parts[] = 'index';
    $get_back = '';
} elseif (count($page_parts)>2) {
    // Move extraneous page parts to params 
    /*echo "<pre>";
    var_dump($page_parts);
    var_dump($params);
    echo "</pre>";*/
    while (count($page_parts)>2) {
        array_unshift($params, (array_pop($page_parts)) );
        $get_back .= '../';
    }
    /*echo "<pre>";
    echo print_r($page_parts,true);
    echo print_r($params,true);
    echo "</pre>";
    die();*/
}
$page = "pages/{$page_parts[0]}_{$page_parts[1]}.php";

require_once('inc/login_check.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Destination Playlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="<?= $get_back ?>css/app.css" rel="stylesheet">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.7.0.slim.js" integrity="sha256-7GO+jepT9gJe9LB4XFf8snVOjX3iYNb0FHYr5LI1N5c=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>

<?php
if (!@include_once($page)) {
    http_response_code(404);
    echo "<h1>You’ve Lost That Lovin’ Feelin’...</h1>\n";
    echo "<h2>Or, more accurately, you've clicked a wrong link.</h2>\n";
    echo "<p>Look, it's most likely our fault - sorry. Or you've mistyped something. Who knows?</p>\n";
    echo "<p class='text-body-secondary'><small>{$page}</small></p>\n";
    die();
}
?>
</body>
</html>