<?php
require_once 'db.php';
require_once 'auth.php';

$auth = new Auth($conn);
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: home.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
    die("CSRF token validation failed");
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : null;

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
