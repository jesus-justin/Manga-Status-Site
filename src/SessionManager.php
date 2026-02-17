<?php
/**
 * Session Manager Class
 * 
 * Provides secure session handling and management.
 */

class SessionManager {
    public function __construct() {
        $this->configure();
    }

    /**
     * Configure session settings
     */
    private function configure() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 100);
            
            session_set_cookie_params([
                'lifetime' => SESSION_TIMEOUT,
                'secure' => SESSION_COOKIE_SECURE,
                'httponly' => SESSION_COOKIE_HTTPONLY,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
    }

    /**
     * Check if session has key
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Get session value
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * Delete session value
     */
    public function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }

    /**
     * Clear all session data
     */
    public function flush() {
        $_SESSION = [];
        return true;
    }

    /**
     * Destroy session
     */
    public function destroy() {
        session_destroy();
        return true;
    }

    /**
     * Regenerate session ID
     */
    public function regenerate() {
        return session_regenerate_id(true);
    }

    /**
     * Get session ID
     */
    public function getID() {
        return session_id();
    }

    /**
     * Check if session is logged in
     */
    public function isLoggedIn() {
        return $this->has('user_id') && $this->has('logged_in');
    }

    /**
     * Flash message (store and clear on next access)
     */
    public function flash($key, $value = null) {
        if ($value === null) {
            $flash = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $flash;
        }
        $_SESSION['_flash'][$key] = $value;
    }
}

?>
