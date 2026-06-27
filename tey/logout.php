<?php
session_start();
require_once '../config.php';
$_SESSION = array();
session_destroy();
header("location: index.php");
exit;
?>



