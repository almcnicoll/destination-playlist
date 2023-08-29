<?php
    // Include Playlist class
    if (!@include_once('class/playlist.php')) {
        if (!@include_once('../class/playlist.php')) {
            require_once('../../class/playlist.php');
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
    $playlist_id = $params[0];

    $playlist = Playlist::getById($playlist_id);
    if ($playlist == null) {
        $error_messages[] = "Playlist not found";
        $fatal_error = true;
    }

    if ($playlist->user_id != $_SESSION['USER_ID']) {
        $error_messages[] = "You do not own this playlist!";
        $fatal_error = true;
    }

?>

<h2 class="text-center">Great! Now share the love...</h2>
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

$share_link = $config['root_path'].'/playlist/join/'.$playlist->getShareCode();
?>
<!--
<div class="row">
    <div class="span12 text-center">
        <button type="button" class="btn btn-primary text-uppercase" data-bs-toggle="modal" data-bs-target="#myModal" id="shareBtn" data-bs-placement="top" title="Share your playlist">
            Share 
        </button>  
    </div>
</div>
-->
<div class="row text-center">
    <div class="col-md-6">
        <h3>Share on</h3>
        <div class="row text-center fs-1">
            <div class="col">
                <a href="whatsapp://send?Join%20my%20destination%20playlist%21%20<?= $share_link ?>" data-action="share/whatsapp/share"  
        target="_blank" class="">
                    <span class="bi bi-whatsapp" style="color: #25D366;"></span>
                </a>
            </div>
            <div class="col">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $share_link ?>" data-action="share/facebook/share"  
        target="_blank" class="">
                    <span class="bi bi-facebook" style="color: #4267B2;"></span>
                </a>
            </div>
            <div class="col">
                <a href="mailto:?subject=Join%20my%20destination%20playlist%21&body=<?= $share_link ?>" data-action="share/email/share" 
        target="_blank" class="">
                    <span class="bi bi-envelope" style="color: #000;"></span>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <h3>or copy link</h3>
        <div class="row fs-3 text-center">
            <div class="col-sm-12">
                <div class="input-group mt-3">
                    <input type="text" class="form-control" value="<?= $share_link ?>">
                    <button class="btn btn-outline-secondary" type="button">Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12 text-center">
        <a class="btn btn-lg btn-success" href="<?= $config['root_path'].'/playlist/manage/'.$playlist->id ?>">Next</a>
        <p class="fs-5 text-body-secondary">Done sharing? Click here to continue.</p>
    </div>
</div>

<!--
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Share Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                </div>
            </div>
        </div>
    </div>
-->