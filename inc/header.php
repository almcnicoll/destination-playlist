<nav class="navbar navbar-dark navbar-expand-lg bg-dark mb-2">
  <div class="container-fluid">
    <!-- Branding -->
    <a class="navbar-brand" alt="logo" class="d-inline-block align-text-top" href="<?= $config['root_path']; ?>"><img src="<?= $config['root_path'] ?>/img/dp-logo.png" height="24"><span class="d-none d-md-inline p-2">Destination Playlist</span></a>
    <!-- Toggler -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUserMenu" aria-controls="navbarUserMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <!-- User menu -->
    <!-- <div class="collapse navbar-collapse" id="navbarCreateButton">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li>
                <a class="nav-link" aria-current="page" href="<?= $config['root_path'] ?>/playlist/create">Home</a>      
            </li>
        </ul>
    </div> -->
<?php
    if (empty($_SESSION['USER'])) {
?>
    <!-- User menu (not logged in) -->
            <div class="collapse navbar-collapse" id="navbarUserMenu">
                <ul class="navbar-nav ml-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="<?= $config['root_path'] ?>/login.php" role="button">Login</a>
                    </li>    
                </ul>
            </div>
<?php
    } else {
?>
    <!-- User menu (logged in) -->
    <div class="collapse navbar-collapse" id="navbarUserMenu">
        <ul class="navbar-nav ml-auto mb-2 mb-lg-0">
            <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class='d-none d-md-inline'><?= $_SESSION['USER']->display_name ?></span>
                <span class='d-md-none initial-display'><?= substr($_SESSION['USER']->display_name,0,1) ?></span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Logout</a></li>
            </ul>
            </li>    
        </ul>
    </div>
<?php
    }
?>
    <!-- sample items -->
    <!--
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="#">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Link</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Dropdown
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link disabled" aria-disabled="true">Disabled</a>
        </li>
      </ul>
      <form class="d-flex" role="search">
        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
        <button class="btn btn-outline-success" type="submit">Search</button>
      </form>
    -->
    </div>
  </div>
</nav>