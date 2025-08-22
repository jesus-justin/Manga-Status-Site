<?php
include 'db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // Use prepared statement for DELETE query
    $sql = "DELETE FROM manga WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: home.php");
exit();
?>
