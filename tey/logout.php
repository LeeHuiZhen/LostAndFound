<?php
session_start();
require_once '../config.php';
$_SESSION = array();
session_destroy();
header("location: login.php");
exit;
?>

