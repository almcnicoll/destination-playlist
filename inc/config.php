<?php
session_start();
$config = [];
// Include secrets
if (!@include_once('inc/secret.php')) {
    if (!@include_once('../inc/secret.php')) {
        if (!@include_once('../../inc/secret.php')) {
            require_once('../../../inc/secret.php');
        }
    }
}

// Include local config
if (!@include_once('inc/config.local.php')) {
    if (!@include_once('../inc/config.local.php')) {
        if (!@include_once('../../inc/config.local.php')) {
            @include_once('../../../inc/config.local.php');
        }
    }
}