<?php
$login_check_soft_fail = true;
?>
<div class='card text-bg-dark'>
    <div class='card-body bg-primary'>
        <h1 class='card-title'>Destination Playlist</h1>
        <h2 class='card-title'>Music for the journey</h2>
    </div>
</div>
<br />
<div class='card'>
    <div class='card-body'>
        <h3 class='card-title'>How it works</h3>
        <ol class='fs-4'>
<?php if (isset($_SESSION['USER'])): ?>
            <li class='step-done'>Sign in / register using Spotify</li>
<?php else: ?>
            <li><a href="<?= $config['root_path'] ?>/login.php">Sign in / register</a> using Spotify</li>
            <div class="fs-6 step-explain">You'll need a Spotify account to use Destination Playlist</div>
<?php endif; ?>
            <li>Enter the place you're going</li>
            <div class="fs-6 step-explain">Each letter of the destination becomes the start of a track</div>
            <li>Share the code with your travel buddies</li>
            <div class="fs-6 step-explain">Your unique code keeps the playlist between you and your friends</div>
            <li>Everyone picks tracks based on the destination</li>
            <div class="fs-6 step-explain">Which means that everyone discovers new music</div>
            <li>Listen and discover together!</li>
        </ol>
    </div>
</div>