<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth.php';
require_once 'db.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn()) {
    header('Location: login_fixed.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user_progress.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: user_progress.php');
    exit();
}

if (isset($_POST['id'])) {
    $progress_id = intval($_POST['id']);
    
    // Verify the progress belongs to the current user
    $verify_sql = "SELECT id FROM user_reading_progress WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $progress_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete the progress
        $delete_sql = "DELETE FROM user_reading_progress WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $progress_id, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Reading progress deleted successfully!';
        } else {
            $_SESSION['error'] = 'Error deleting progress: ' . $conn->error;
        }
    } else {
        $_SESSION['error'] = 'Invalid progress entry or access denied.';
    }
}

header('Location: user_progress.php');
exit();
?>
