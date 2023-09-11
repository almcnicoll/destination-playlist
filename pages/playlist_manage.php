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
    var root_path = "<?= $config['root_path'] ?>";
    var playlist_id = "<?= $playlist->id ?>";
    var currentUser = <?= $_SESSION['USER_ID'] ?>;
</script>
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/letter_refresh.js'></script>
<script type="text/javascript">
    var timer1;
    var script1Url = "<?= $config['root_path'] ?>/ajax/get_participants.php?playlist_id=<?= $playlist->id ?>";
    function updatePeopleList(data, textStatus, jqXHR) {
        $('#people-table tbody tr:not(:first)').remove();
        for(var i in data) {
            var u = data[i].user;
            $('#people-table tbody').append("<tr><td><div class='initial-display'>"+u.display_name.substr(0,1)+"</div></td><td>"+u.display_name+"</td></tr>");
        }
    }
    var ajax1Options = {
        async: true,
        cache: false,
        success: updatePeopleList,
        dataType: 'json',
        method: 'GET',
        timeout: 8000
    };
    function getParticipants() {
        $.ajax(script1Url, ajax1Options);
        timer1 = setTimeout('getParticipants()',10000);
    }
    
    

    var script3Url = "<?= $config['root_path'] ?>/ajax/assign_letters.php?playlist_id=<?= $playlist->id ?>";
    var ajax3Options = {
        async: true,
        cache: false,
        /*success: someFunction,*/
        dataType: 'json',
        method: 'GET',
        timeout: 4000
    };

    $(document).ready(
        function () {
            //timer = setTimeout('getParticipants()',5000);
            getParticipants();
            var tmp_timer = setTimeout('getLetters();',1000); // Give it a little offset

            $('#btn-assign-letters').on('click',function() {
                $.ajax(script3Url, ajax3Options);
            });
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

<ul class="nav nav-tabs" id="nav1" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link active" aria-current="page" href="#nav1-content-1" data-bs-toggle="tab" id="nav1-tab-1" aria-controls="nav-content-1" aria-selected="true">People</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="#nav1-content-2" data-bs-toggle="tab" id="nav1-tab-2" aria-controls="nav-content-2">Tracks</a>
    </li>
</ul>

<div class="tab-content" id="nav1-content">
    <div class="tab-pane fade show active" role="tabpanel" id="nav1-content-1" aria-labelledby="nav1-tab-1">
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
    <div class="tab-pane fade" role="tabpanel" id="nav1-content-2" aria-labelledby="nav1-tab-2">
        <table class="table table-light table-striped" id="tracks-table">
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-4">
        <a href="#" class="btn btn-md btn-success" id='btn-assign-letters'>Assign letters</a>
    </div>
    <div class="col-8"></div>
</div>