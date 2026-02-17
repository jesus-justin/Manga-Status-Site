<?php
/**
 * Sanitizer Class
 * 
 * Provides input sanitization for security and data integrity.
 */

class Sanitizer {
    /**
     * Sanitize string input
     */
    public static function string($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Sanitize email
     */
    public static function email($input) {
        return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize URL
     */
    public static function url($input) {
        return filter_var(trim($input), FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize integer
     */
    public static function integer($input) {
        return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float
     */
    public static function float($input) {
        return (float)filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize boolean
     */
    public static function boolean($input) {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sanitize file path
     */
    public static function filePath($input) {
        $path = realpath($input);
        return $path !== false ? $path : null;
    }

    /**
     * Sanitize filename
     */
    public static function filename($input) {
        $filename = basename($input);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }

    /**
     * Sanitize HTML (allow safe tags)
     */
    public static function html($input) {
        $allowed = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a>';
        return strip_tags($input, $allowed);
    }

    /**
     * Sanitize JSON string
     */
    public static function json($input) {
        $decoded = json_decode($input, true);
        if ($decoded === null) {
            return null;
        }
        return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Sanitize array data
     */
    public static function array($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $key = self::string($key);
            if (is_array($value)) {
                $sanitized[$key] = self::array($value);
            } else {
                $sanitized[$key] = self::string($value);
            }
        }
        return $sanitized;
    }

    /**
     * Remove null bytes
     */
    public static function removeNullBytes($input) {
        return str_replace('\0', '', $input);
    }

    /**
     * Remove special characters
     */
    public static function removeSpecial($input, $allowed = '') {
        return preg_replace('/[^a-zA-Z0-9' . preg_quote($allowed) . ']/', '', $input);
    }
}

?>
