<?php
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $user = $_SESSION['USER'];
    
    // Al only
    if (!$user->isAdmin()) {
        header("Location: ../");
        die();
    }

    // Get stats
    $stats = [];
    $stats['Total users'] = User::count();
    $stats['Total playlists'] = Playlist::count();
    $recent_users = array_slice(User::getAll(),0,3);
    $usernames=[];
    foreach ($recent_users as $recent_user) {
        $usernames[] = $recent_user->display_name;
    }
    $stats['Most recent users'] = implode(', ',$usernames);

    // Display error messages
    if (count($error_messages)>0) {
        foreach($error_messages as $error_message) {
    echo <<<END_HTML
    <div class="row">
        <div class="span12 alert alert-danger">{$error_message}</div>
    </div>
END_HTML;
        }
    }
?>

<div class='top-left-menu'><a href="<?= $config['root_path'] ?>/admin/users" class='btn btn-primary btn-md'>Manage Users</a></div>

<h3 class='card-title'>Statistics</h3>
<table class="table table-sm table-striped table-hover" id="stats-table">
    <!--<thead>
        <tr>
            <th>Col 1</th>
            <th>Col 2</th>
        </tr>
    </thead>-->
    <tbody>
        <?php
        foreach ($stats as $k=>$v) {
        ?>
        <tr>
            <td><?= $k ?></td>
            <td><?= $v ?></td>
        </tr>
        <?php
        }
        ?>
    </tbody>
</table>