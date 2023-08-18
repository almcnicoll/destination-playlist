<?php
session_start();
if(!isset($_SESSION['auth.user'])) {
    header("Location: ./login.php");
    die();
}
?>
<!DOCTYPE html>
