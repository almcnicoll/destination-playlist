<?php
require_once('autoload.php');

$sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_GET, '');

var_dump($config);

die("OK: ".__LINE__);