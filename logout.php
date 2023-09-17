<?php
require('inc/config.php');
session_start();
session_destroy();
header("Location: {$config['root_path']}/");
die();