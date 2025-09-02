-m <?php
/**
 * Authentication and Authorization Class
 *
 * Handles user authentication, registration, session management, and CSRF protection
 * for the Manga Library application. Implements security best practices including
 * password hashing, prepared statements, and session security.
 *
 * @author Manga Library Development Team
 * @version 1.0
 * @since 2024
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

/**
 * Authentication Class
 *
 * Provides secure user authentication and session management functionality.
 * Includes CSRF protection, password hashing, and session security measures.
 */
class Auth {
    /**
     * Database connection object
     * @var mysqli
     */
    private $conn;

    /**
     * Constructor - Initialize authentication system
     *
     * Sets up secure session management and CSRF protection.
     * Regenerates session ID for security and generates CSRF token.
     *
     * @param mysqli $conn Database connection object
     */
    public function __construct($conn) {
        $this->conn = $conn;

        // Regenerate session ID to prevent session fixation attacks
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }

        // Generate cryptographically secure CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Set security headers
        $this->setSecurityHeaders();
    }

    /**
     * Set security headers to prevent common web vulnerabilities
     */
    private function setSecurityHeaders() {
        // Prevent clickjacking
        header("X-Frame-Options: DENY");

        // Prevent MIME type sniffing
        header("X-Content-Type-Options: nosniff");

        // Enable XSS protection
        header("X-XSS-Protection: 1; mode=block");

        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Content Security Policy (basic)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://fonts.googleapis.com; connect-src 'self' https://api.jikan.moe");

        // HSTS (HTTP Strict Transport Security) - only if using HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
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
        
        // Insert user (simplified without verification token)
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password_hash);
        
        if ($stmt->execute()) {
            $user_id = $this->conn->insert_id;
            
            return ['success' => true, 'message' => 'Registration successful. You can now login.'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function login($username, $password) {
        // Rate limiting: Check login attempts
        $this->checkLoginAttempts($username);

        $stmt = $this->conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                // Reset login attempts on successful login
                $this->resetLoginAttempts($username);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;

                return ['success' => true, 'message' => 'Login successful'];
            }
        }

        // Record failed attempt
        $this->recordFailedAttempt($username);

        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    /**
     * Check login attempts for rate limiting
     */
    private function checkLoginAttempts($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $time_window = 15 * 60; // 15 minutes
        $max_attempts = 5;

        // Clean old attempts
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE attempt_time < ?");
        $stmt->bind_param("i", (time() - $time_window));
        $stmt->execute();

        // Check attempts
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip = ? AND username = ? AND attempt_time > ?");
        $stmt->bind_param("ssi", $ip, $username, (time() - $time_window));
        $stmt->execute();
        $result = $stmt->get_result();
        $attempts = $result->fetch_assoc()['attempts'];

        if ($attempts >= $max_attempts) {
            throw new Exception('Too many login attempts. Please try again later.');
        }
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->conn->prepare("INSERT INTO login_attempts (ip, username, attempt_time) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $ip, $username, time());
        $stmt->execute();
    }

    /**
     * Reset login attempts on successful login
     */
    private function resetLoginAttempts($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE ip = ? AND username = ?");
        $stmt->bind_param("ss", $ip, $username);
        $stmt->execute();
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
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

    public function getCsrfToken() {
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken($token) {
        return isset($token) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
