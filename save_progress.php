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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manga_id = intval($_POST['manga_id']);
    $status = $_POST['status'];
    $current_chapter = $_POST['current_chapter'] ?? null;
    $total_chapters = $_POST['total_chapters'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    // Calculate progress percentage
    $progress_percentage = 0;
    if ($total_chapters && $current_chapter) {
        $current = floatval($current_chapter);
        $total = floatval($total_chapters);
        $progress_percentage = min(100, max(0, round(($current / $total) * 100)));
    }
    
    // Handle finish date based on status
    $finish_date = null;
    if ($status === 'completed') {
        $finish_date = date('Y-m-d');
    }
    
    // Check if progress already exists
    $check_sql = "SELECT id FROM user_reading_progress WHERE user_id = ? AND manga_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $manga_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing progress
        $update_sql = "UPDATE user_reading_progress SET 
            status = ?, 
            current_chapter = ?, 
            total_chapters = ?, 
            rating = ?, 
            start_date = ?, 
            finish_date = ?, 
            notes = ?, 
            progress_percentage = ?,
            last_read_date = NOW()
        WHERE user_id = ? AND manga_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssdsssiii", 
            $status, $current_chapter, $total_chapters, $rating, $start_date, 
            $finish_date, $notes, $progress_percentage, $user_id, $manga_id
        );
    } else {
        // Insert new progress
        $insert_sql = "INSERT INTO user_reading_progress 
            (user_id, manga_id, status, current_chapter, total_chapters, rating, 
             start_date, finish_date, notes, progress_percentage, last_read_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iisssdsssi", 
            $user_id, $manga_id, $status, $current_chapter, $total_chapters, 
            $rating, $start_date, $finish_date, $notes, $progress_percentage
        );
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Reading progress saved successfully!';
    } else {
        $_SESSION['error'] = 'Error saving progress: ' . $conn->error;
    }
    
    header('Location: user_progress.php');
    exit();
}

$conn->close();
?>
