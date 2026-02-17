<?php
/**
 * Validator Class
 * 
 * Provides utility methods for validating common input types and patterns.
 */

class Validator {
    /**
     * Validate email format
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     */
    public static function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     */
    public static function integer($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate username format
     */
    public static function username($username) {
        if (strlen($username) < 3 || strlen($username) > 50) {
            return false;
        }
        return preg_match('/^[a-zA-Z0-9_]+$/', $username) === 1;
    }

    /**
     * Validate password strength
     */
    public static function password($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $number = preg_match('/[0-9]/', $password);
        
        return $uppercase && $lowercase && $number;
    }

    /**
     * Validate rating value
     */
    public static function rating($rating) {
        $value = floatval($rating);
        return $value >= MIN_RATING && $value <= MAX_RATING;
    }

    /**
     * Validate manga status
     */
    public static function mangaStatus($status) {
        return array_key_exists($status, MANGA_STATUSES);
    }

    /**
     * Validate progress status
     */
    public static function progressStatus($status) {
        return array_key_exists($status, PROGRESS_STATUSES);
    }

    /**
     * Validate genre against allowed list
     */
    public static function genre($genre) {
        return in_array($genre, DEFAULT_GENRES);
    }

    /**
     * Validate genres array
     */
    public static function genres($genres) {
        if (!is_array($genres)) {
            return false;
        }
        foreach ($genres as $genre) {
            if (!self::genre($genre)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate file upload
     */
    public static function fileUpload($file, $maxSize = MAX_UPLOAD_SIZE) {
        if (!isset($file['tmp_name']) || !isset($file['type']) || !isset($file['size'])) {
            return false;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        if ($file['size'] > $maxSize) {
            return false;
        }
        return in_array($file['type'], ALLOWED_IMAGE_TYPES);
    }

    /**
     * Validate chapter format
     */
    public static function chapter($chapter) {
        if (empty($chapter)) {
            return true;
        }
        return preg_match('/^(Chapter\s+)?(\d+)(\.\d+)?$/i', trim($chapter)) === 1;
    }

    /**
     * Validate array of required keys
     */
    public static function requiredKeys(array $data, array $keys) {
        foreach ($keys as $key) {
            if (!isset($data[$key]) || empty($data[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate string length
     */
    public static function stringLength($string, $min = 1, $max = null) {
        $len = strlen($string);
        if ($len < $min) {
            return false;
        }
        return $max === null || $len <= $max;
    }

    /**
     * Validate date format YYYY-MM-DD
     */
    public static function date($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate boolean value
     */
    public static function boolean($value) {
        return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no']);
    }
}

?>
