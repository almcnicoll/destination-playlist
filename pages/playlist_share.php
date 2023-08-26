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
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded',function(e){
        let field = document.querySelector('.field');
        let input = document.querySelector('input');
        let copyBtn = document.querySelector('.field button');

        copyBtn.onclick = () =>{
            input.select();
            if(document.execCommand("copy")){
                field.classList.add('active');
                copyBtn.innerText = 'Copied';
                setTimeout(()=>{
                    field.classList.remove('active');
                    copyBtn.innerText = 'Copy';
                },3500)
            }
        }
    });
</script>

<h2>Great! Now share the love...</h2>
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

$share_link = $config['root_path'].'/playlists/join/'.$playlist->getShareCode();
?>
<div class="row">
    <div class="span12 text-center">
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary text-uppercase" data-bs-toggle="modal" data-bs-target="#myModal" id="shareBtn" data-bs-placement="top" title="Share your playlist">
            Share 
        </button>  
    </div>
</div>

    <!-- Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Share Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Share this link via</p>
                    <div class="d-flex align-items-center icons">                
                        <a href="whatsapp://send?Join%20my%20destination%20playlist%21%20<?= $share_link ?>" data-action="share/whatsapp/share"  
        target="_blank" class="fs-5 d-flex align-items-center justify-content-center p-2">
                            <span class="bi bi-whatsapp"></span>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $share_link ?>" data-action="share/facebook/share"  
        target="_blank" class="fs-5 d-flex align-items-center justify-content-center p-2">
                            <span class="bi bi-facebook"></span>
                        </a>
                        <a href="mailto:?subject=Join%20my%20destination%20playlist%21&body=<?= $share_link ?>" data-action="share/email/share" 
        target="_blank" class="fs-5 d-flex align-items-center justify-content-center p-2">
                            <span class="bi bi-email"></span>
                        </a>
                    </div>
                    <p>Or copy link</p>
                    <div class="field d-flex align-items-center justify-content-between">
                        <span class="fas fa-link text-center"></span>
                        <input type="text" value="<?= $share_link ?>">
                        <button>Copy</button>
                    </div>
                </div>
            </div>
        </div>
    </div>