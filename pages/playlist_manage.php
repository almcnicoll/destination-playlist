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
<!-- Set vars -->
<script type="text/javascript">
    if (typeof trackSearch === 'undefined') { trackSearch = {}; }
    <?php
    if(isset($playlist_id) && !$playlist->hasFlags(Playlist::FLAGS_ALLOWTITLE)) {
        echo "trackSearch.allow_title=false;\n";
    } else {
        echo "trackSearch.allow_title=true;\n";
    }
    if(isset($playlist_id) && !$playlist->hasFlags(Playlist::FLAGS_ALLOWARTIST)) {
        echo "trackSearch.allow_artist=false;\n";
    } else {
        echo "trackSearch.allow_artist=true;\n";
    }
    if(isset($playlist_id) && $playlist->hasFlags(Playlist::FLAGS_STRICT)) {
        echo "trackSearch.strict_mode=true;\n";
    } else {
        echo "trackSearch.strict_mode=false;\n";
    }
    echo "trackSearch.token = \"{$_SESSION['USER_ACCESSTOKEN']}\";\n";
    $user = $_SESSION['USER'];
    //echo "/*\n".print_r($user,true)."\n*/\n\n";
    echo "trackSearch.market = \"{$user->market}\";\n";
    echo "trackSearch.playlist_id = \"{$playlist->id}\";\n\n";
    echo "var root_path = \"{$config['root_path']}\";\n";
    echo "var playlist_id = {$playlist->id};\n";
    echo "var currentUser = {$_SESSION['USER_ID']};\n";
    ?>
</script>
<!-- Include search script -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/search_mgmt.js'></script>
<!-- Include letter-refresh script -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/letter_refresh.js'></script>
<!-- Include people-refresh script -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/people_refresh.js'></script>
<!-- Include letter-assign script -->
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/letter_assign.js'></script>
<!-- Custom callback functions -->
<script type='text/javascript'>
    letterGetter.updateLettersCustom = function(data, textStatus, jqXHR) {
        $('#tracks-table tbody tr').remove();
        // Manage errors or good data
        if ('errors' in data) {            
            $('#tracks-table tbody tr').remove();
            for(var i in data.errors) {
                $('#tracks-table tbody').append("<tr><td class='error'><div class='error'>"+data.errors[i]+"</td></tr>");
            }
        } else {
            var letterData = data.result;
            for(var i in letterData) {
                var l = letterData[i];
                var user_display = "";
                var edit_own = "";
                if ((letterData[i].user_id != null) && (letterData[i].user_id != 'null')) {
                    var u = letterData[i].user;
                    user_display = "<div class='initial-display'>"+u.display_name.substr(0,1)+"</div>";
                    if (u.id == currentUser) {
                        edit_own = "<a href='#' id='edit-track-"+i+"'  class='btn' data-bs-toggle='modal' data-bs-target='#trackSearchModal' onclick=\"search_letter = '"+l.letter.toUpperCase()+"'; letter_id = "+l.id+";\"><span class='bi bi-pencil-square'></span></a>";
                    }
                }
                $('#tracks-table tbody').append("<tr><td class='letter-display'><div class='letter-display'>"+l.letter.toUpperCase()+"</div></td><td>"+l.cached_title+"</td><td>"+l.cached_artist+"</td><td class='initial-display'>"+user_display+"</td><td>"+edit_own+"</td></tr>");
            }
        }
    }

    peopleGetter.updatePeopleListCustom = function(data, textStatus, jqXHR) {
        $('#people-table tbody tr:not(:first)').remove();
        for(var i in data) {
            var u = data[i].user;
            $('#people-table tbody').append("<tr><td><div class='initial-display'>"+u.display_name.substr(0,1)+"</div></td><td>"+u.display_name+"</td></tr>");
        }
    }

    trackSearch.updateSearchBoxCustom = function(data, textStatus, jqXHR) {
        output = '';
        title_valid = false;
        artist_valid = false;
        for(var i in data.tracks.items) {
            var t = data.tracks.items[i];
            var t_a = t.artists;
            title_valid = (t.name.substr(0,1).toUpperCase() == search_letter); // check if title meets criteria (but need to handle "the")
            t.artist_string = '';
            for(var ii in t.artists) {
                if (t.artist_string != '') { t.artist_string += ' // '; }
                t.artist_string += t.artists[ii].name;
                artist_valid = artist_valid | (t.artists[ii].name.substr(0,1).toUpperCase() == search_letter); // check if artist meets criteria
            }
            // alter output if not allowed by playlist rules
            // ... code here ...
            output += "<li class='list-group-item'><a href='#' class='search-result' data-track-id='"+t.id+"' data-preview-url='"+t.preview_url+"' data-track-title=\""+encodeURIComponent(t.name)+"\" data-track-artists=\""+encodeURIComponent(t.artist_string)+"\">"+t.name+" ("+t.artist_string+")</a></li>";
        }
        $('#search-results-container').html("<ul class='list-group'>"+output+"</ul>");
    }
    trackSearch.handleSearchClickCustom = function(clickedElement) {

    }
    trackSearch.handleTrackUpdateSuccessCustom = function() {
        $('#trackSearchModalCloseX').trigger('click');
    }

    // Initialisations
    trackSearch.init('#track-search-box','#search-results-container');
    letterGetter.init(500,10000,8000);
    peopleGetter.init(0,10000,8000);
    letterAssigner.init('#btn-assign-letters');
    
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

<div class="modal fade" id="trackSearchModal" tabindex="-1">
  <div class="modal-dialog .modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Track search</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="trackSearchModalCloseX"></button>
      </div>
      <div class="modal-body">
      <div class="row">
        <div class="col-12">
            <input type="text" placeholder="Type here to search..." id="track-search-box">
        </div>
        <div class="col-12" style="min-height: 10em;" id='search-results-container'>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>