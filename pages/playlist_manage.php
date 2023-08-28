<?php
    // Include Playlist class
    if (!@include_once('class/participation.php')) {
        if (!@include_once('../class/participation.php')) {
            require_once('../../class/participation.php');
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
    var timer;
    var scriptUrl = "<?= $config['root_path'] ?>/ajax/get_participants.php?playlist_id=<?= $playlist->id ?>";
    function updateList(data, textStatus, jqXHR) {
        $('#people-table tbody tr:not(:first)').remove();
        for(var i in data) {
            var u = data[i].user;
            $('#people tbody').append("<tr><td><div class='initial-display'>"+u.display_name.substr(0,1)+"</div></td><td>"+u.display_name+"</td></tr>");
        }
    }
    var ajaxOptions = {
        async: false,
        cache: false,
        success: updateList,
        dataType: 'json',
        method: 'GET',
        timeout: 4000
    };
    function getParticipants() {
        $.ajax(scriptUrl, ajaxOptions);
        timer = setTimeout('getParticipants()',5000);
    }
    $(document).ready(
        function () {
            //timer = setTimeout('getParticipants()',5000);
            getParticipants();
        }
    );
</script>

<h2 class="text-center"><?= $playlist->display_name ?></h2>
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
?>

<nav>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#" data-bs-toggle="tab">People</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-bd-toggle="tab">Tracks</a>
        </li>
    </ul>
</nav>
<div class="tab-content">
    <div class="tab-pane fade show active" id="people">
        <table class="table table-light table-striped" id="people-table">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><div class="initial-display"><?= strtoupper(substr($_SESSION['USER']->display_name,0,1)) ?></div></td>
                    <td><?= $_SESSION['USER']->display_name ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="tab-pane fade" id="tracks">
        <table class="table table-light table-striped">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>Track</th>
                </tr>
            </thead>
        </table>
    </div>
</div>