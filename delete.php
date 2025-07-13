<?php
include 'db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $sql = "DELETE FROM manga WHERE id=$id";
    $conn->query($sql);
}

header("Location: index.php");
exit();
?>
