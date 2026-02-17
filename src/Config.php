<?php
/**
 * Configuration Class
 * 
 * Centralized configuration management.
 */

class Config {
    private static $config = [];

    /**
     * Load configuration from constants
     */
    public static function init() {
        self::$config = [
            'app' => [
                'name' => APP_NAME,
                'version' => APP_VERSION,
                'debug' => APP_DEBUG
            ],
            'database' => [
                'host' => DB_HOST,
                'user' => DB_USER,
                'password' => DB_PASS,
                'name' => DB_NAME
            ],
            'security' => [
                'session_timeout' => SESSION_TIMEOUT,
                'login_max_attempts' => LOGIN_MAX_ATTEMPTS,
                'login_attempt_window' => LOGIN_ATTEMPT_WINDOW
            ],
            'upload' => [
                'max_size' => MAX_UPLOAD_SIZE,
                'allowed_types' => ALLOWED_IMAGE_TYPES
            ]
        ];
    }

    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set configuration value
     */
    public static function set($key, $value) {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Check if key exists
     */
    public static function has($key) {
        return self::get($key) !== null;
    }

    /**
     * Get all configuration
     */
    public static function all() {
        return self::$config;
    }
}

// Initialize on load
Config::init();

?>
