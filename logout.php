<?php
require_once 'auth.php';
require_once 'db.php';

$auth = new Auth($conn);
$auth->logout();
header('Location: home.php');
exit();
?>
