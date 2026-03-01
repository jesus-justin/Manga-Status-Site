<?php
/**
 * Request Handler
 * 
 * Provides utilities for HTTP request handling
 */

class Request {
    /**
     * Get request method
     */
    public static function getMethod() {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Check if POST request
     */
    public static function isPost() {
        return self::getMethod() === 'POST';
    }

    /**
     * Check if GET request
     */
    public static function isGet() {
        return self::getMethod() === 'GET';
    }

    /**
     * Get input value
     */
    public static function get($key, $default = null) {
        if (self::isPost()) {
            return $_POST[$key] ?? $default;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get all input
     */
    public static function all() {
        if (self::isPost()) {
            return $_POST;
        }
        return $_GET;
    }

    /**
     * Check if input exists
     */
    public static function has($key) {
        if (self::isPost()) {
            return isset($_POST[$key]);
        }
        return isset($_GET[$key]);
    }

    /**
     * Get header
     */
    public static function header($name, $default = null) {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$name] ?? $default;
    }

    /**
     * Check if AJAX request
     */
    public static function isAjax() {
        return self::header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get client IP
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /**
     * Get request URI
     */
    public static function getUri() {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get referrer
     */
    public static function getReferrer() {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Get JSON input
     */
    public static function getJSON($key = null, $default = null) {
        $json = json_decode(file_get_contents('php://input'), true);
        
        if ($key === null) {
            return $json;
        }
        
        return $json[$key] ?? $default;
    }
}

?>
