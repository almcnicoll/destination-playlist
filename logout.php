<?php
require('autoload.php');
session_destroy();
header("Location: {$config['root_path']}/");
die();