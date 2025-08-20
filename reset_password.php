<?php
require_once 'auth_enhanced.php';
require_once 'db.php';

$error = '';
$success = '';

// Check if token is provided
if (!isset($_GET['token'])) {
    header('Location: forgot_password.php');
    exit();
}

$token = $_GET['token'];

// Verify token
try {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset_request) {
        $error = "Invalid or expired reset link.";
    }
} catch (PDOException $e) {
    $error = "Database error occurred.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate passwords
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Update user password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $reset_request['email']]);

            // Delete used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            $success = "Your password has been reset successfully. You can now <a href='login.php'>login</a>.";
        } catch (PDOException $e) {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Reset Your Password</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>
