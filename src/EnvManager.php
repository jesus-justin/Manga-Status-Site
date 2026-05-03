<?php
/**
 * Environment Manager - Load and manage .env configuration
 *
 * Reads .env file and provides easy access to environment variables
 * with fallback to default values.
 *
 * @version 2.0.0
 */

class EnvManager {
    /**
     * Loaded environment variables
     */
    private static $env = [];
    
    /**
     * Has environment been loaded
     */
    private static $loaded = false;

    /**
     * Load environment variables from .env file
     */
    public static function load($envPath = null) {
        if (self::$loaded) {
            return;
        }

        if ($envPath === null) {
            $envPath = __DIR__ . '/../.env';
        }

        if (!file_exists($envPath)) {
            error_log("Warning: .env file not found at $envPath, using defaults");
            self::$loaded = true;
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments and empty lines
            if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes from value
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                self::$env[$key] = $value;
                
                // Also set as environment variable if not already set
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable with optional default
     *
     * @param string $key Environment key
     * @param mixed $default Default value if not found
     * @return mixed Environment value or default
     */
    public static function get($key, $default = null) {
        self::load();
        
        if (isset(self::$env[$key])) {
            return self::$env[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }

    /**
     * Get as boolean
     */
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get as integer
     */
    public static function getInt($key, $default = 0) {
        $value = self::get($key, $default);
        return intval($value);
    }

    /**
     * Set environment variable (runtime only)
     */
    public static function set($key, $value) {
        self::$env[$key] = $value;
        putenv("$key=$value");
    }

    /**
     * Check if key exists
     */
    public static function has($key) {
        self::load();
        return isset(self::$env[$key]) || getenv($key) !== false;
    }

    /**
     * Get all environment variables
     */
    public static function all() {
        self::load();
        return self::$env;
    }

    /**
     * Is production environment
     */
    public static function isProduction() {
        return self::get('APP_ENV') === 'production';
    }

    /**
     * Is development environment
     */
    public static function isDevelopment() {
        return self::get('APP_ENV') === 'development';
    }

    /**
     * Debug mode enabled
     */
    public static function isDebug() {
        return self::getBool('APP_DEBUG', false);
    }
}
