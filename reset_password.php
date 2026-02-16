<?php
require_once 'auth_enhanced.php';
require_once 'db.php';

$auth = new Auth($conn);
$error = '';
$success = '';

// Check if token is provided
if (!isset($_GET['token'])) {
    header('Location: forgot_password.php');
    exit();
}

$token = $_GET['token'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify token for display
$stmt = $conn->prepare("SELECT id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = FALSE");
$stmt->bind_param("s", $token);
$stmt->execute();
$reset_request = $stmt->get_result()->fetch_assoc();
if (!$reset_request) {
    $error = "Invalid or expired reset link.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = "Invalid request. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate passwords
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $result = $auth->resetPassword($token, $password);
            if ($result['success']) {
                $success = "Your password has been reset successfully. You can now <a href='login_fixed.php'>login</a>.";
            } else {
                $error = $result['message'];
            }
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
        
        <p><a href="login_fixed.php">Back to Login</a></p>
    </div>
</body>
</html>
