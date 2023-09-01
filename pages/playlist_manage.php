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
        timeout: 4000
    };
    function getParticipants() {
        $.ajax(script1Url, ajax1Options);
        timer1 = setTimeout('getParticipants()',5000);
    }
    
    var timer2;
    var script2Url = "<?= $config['root_path'] ?>/ajax/get_letters.php?playlist_id=<?= $playlist->id ?>";
    function updateTrackList(data, textStatus, jqXHR) {
        $('#tracks-table tbody tr').remove();
        for(var i in data) {
            var l = data[i];
            var user_display = "";
            if ((data[i].user_id != null) && (data[i].user_id != 'null')) {
                var u = data[i].user;
                user_display = "<div class='initial-display'>"+u.display_name.substr(0,1)+"</div>";
            }
            $('#tracks-table tbody').append("<tr><td><div class='letter-display'>"+l.letter.toUpperCase()+"</div></td><td>"+user_display+"</td><td>"+l.cached_title+"</td><td>"+l.cached_artist+"</td></tr>");
        }
    }
    var ajax2Options = {
        async: true,
        cache: false,
        success: updateTrackList,
        dataType: 'json',
        method: 'GET',
        timeout: 4000
    };
    function getLetters() {
        $.ajax(script2Url, ajax2Options);
        timer2 = setTimeout('getLetters()',5000);
    }

    $(document).ready(
        function () {
            //timer = setTimeout('getParticipants()',5000);
            getParticipants();
            getLetters();
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
            <!--<thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>Track</th>
                </tr>
            </thead>-->
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-4">
        <a href="#" class="btn btn-md btn-success">Assign letters</a>
    </div>
    <div class="col-8"></div>
</div>