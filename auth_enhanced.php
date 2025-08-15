<?php
session_start();
require_once 'db.php';

class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        // Regenerate session ID for security
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
    
    public function register($username, $email, $password) {
        // Validate inputs
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }
        
        // Check if username or email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password_hash, verification_token) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password_hash, $verification_token);
        
        if ($stmt->execute()) {
            $user_id = $this->conn->insert_id;
            
            // Set default preferences
            $stmt = $this->conn->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Send verification email (implement email sending here)
            // $this->sendVerificationEmail($email, $verification_token);
            
            return ['success' => true, 'message' => 'Registration successful. Please check your email to verify your account.'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function login($username, $password) {
        // Check rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $attempts = $stmt->get_result()->fetch_row()[0];
        
        if ($attempts >= 5) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }
        
        $stmt = $this->conn->prepare("SELECT id, username, password_hash, email_verified FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                if (!$user['email_verified']) {
                    return ['success' => false, 'message' => 'Please verify your email address before logging in'];
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;
                
                // Update last login
                $stmt = $this->conn->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Login successful'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    public function logout() {
        // Clear remember me tokens
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->bind_param("s", $_COOKIE['remember_token']);
            $stmt->execute();
            setcookie('remember_token', '', time() - 3600, "/");
        }
        
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }
        
        // Check remember me token
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->conn->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
            $stmt->bind_param("s", $_COOKIE['remember_token']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['logged_in'] = true;
                
                // Update session token
                $new_token = bin2hex(random_bytes(32));
                $stmt = $this->conn->prepare("UPDATE user_sessions SET session_token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE session_token = ?");
                $stmt->bind_param("ss", $new_token, $_COOKIE['remember_token']);
                $stmt->execute();
                
                setcookie('remember_token', $new_token, time() + (86400 * 30), "/");
                return true;
            }
        }
        
        return false;
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            $stmt = $this->conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
        return null;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: login_new.php');
            exit();
        }
    }
    
    public function forgotPassword($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $this->conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires);
            $stmt->execute();
            
            // Send password reset email (implement email sending here)
            // $this->sendPasswordResetEmail($email, $token);
            
            return ['success' => true, 'message' => 'Password reset instructions have been sent to your email'];
        }
        
        return ['success' => true, 'message' => 'If the email exists, reset instructions have been sent'];
    }
    
    public function resetPassword($token, $password) {
        $stmt = $this->conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = FALSE");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $email = $result->fetch_assoc()['email'];
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt->bind_param("ss", $password_hash, $email);
            $stmt->execute();
            
            // Mark token as used
            $stmt = $this->conn->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Password reset successful'];
        }
        
        return ['success' => false, 'message' => 'Invalid or expired reset token'];
    }
}
?>
