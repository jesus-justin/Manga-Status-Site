<?php
/**
 * RequestValidator Middleware
 *
 * Centralized request validation and sanitization with CSRF protection,
 * input validation, and request logging for all API endpoints.
 *
 * @version 2.0.0
 */

class RequestValidator {
    private $errors = [];
    private $sanitized = [];
    private $logger;
    private $csrfToken;

    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->csrfToken = $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Validate CSRF token
     */
    public function validateCsrf($token = null) {
        if (empty($this->csrfToken)) {
            $this->errors[] = 'CSRF token not found in session';
            return false;
        }

        $token = $token ?? ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        
        if (empty($token)) {
            $this->errors[] = 'CSRF token missing from request';
            return false;
        }

        if (!hash_equals($this->csrfToken, $token)) {
            $this->errors[] = 'Invalid CSRF token';
            if ($this->logger) {
                $this->logger->warning('CSRF token validation failed', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            }
            return false;
        }

        return true;
    }

    /**
     * Validate required fields
     */
    public function validateRequired($fields, $data = null) {
        if ($data === null) {
            $data = $_REQUEST;
        }

        $missing = [];
        foreach ((array)$fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field] ?? ''))) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $this->errors[] = 'Missing required fields: ' . implode(', ', $missing);
            return false;
        }

        return true;
    }

    /**
     * Validate email field
     */
    public function validateEmail($field, $data = null) {
        if ($data === null) {
            $data = $_REQUEST;
        }

        if (!isset($data[$field])) {
            $this->errors[] = "$field is required";
            return false;
        }

        $email = $data[$field];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "$field must be a valid email address";
            return false;
        }

        $this->sanitized[$field] = filter_var($email, FILTER_SANITIZE_EMAIL);
        return true;
    }

    /**
     * Validate URL field
     */
    public function validateUrl($field, $data = null) {
        if ($data === null) {
            $data = $_REQUEST;
        }

        if (!isset($data[$field])) {
            $this->errors[] = "$field is required";
            return false;
        }

        $url = $data[$field];
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[] = "$field must be a valid URL";
            return false;
        }

        $this->sanitized[$field] = filter_var($url, FILTER_SANITIZE_URL);
        return true;
    }

    /**
     * Validate integer field
     */
    public function validateInteger($field, $min = null, $max = null, $data = null) {
        if ($data === null) {
            $data = $_REQUEST;
        }

        if (!isset($data[$field])) {
            $this->errors[] = "$field is required";
            return false;
        }

        $value = filter_var($data[$field], FILTER_VALIDATE_INT);
        if ($value === false) {
            $this->errors[] = "$field must be an integer";
            return false;
        }

        if ($min !== null && $value < $min) {
            $this->errors[] = "$field must be at least $min";
            return false;
        }

        if ($max !== null && $value > $max) {
            $this->errors[] = "$field must be no more than $max";
            return false;
        }

        $this->sanitized[$field] = $value;
        return true;
    }

    /**
     * Validate string field with length constraints
     */
    public function validateString($field, $minLength = 0, $maxLength = 255, $data = null) {
        if ($data === null) {
            $data = $_REQUEST;
        }

        if (!isset($data[$field])) {
            $this->errors[] = "$field is required";
            return false;
        }

        $value = trim($data[$field]);
        $length = strlen($value);

        if ($length < $minLength) {
            $this->errors[] = "$field must be at least $minLength characters";
            return false;
        }

        if ($length > $maxLength) {
            $this->errors[] = "$field must be no more than $maxLength characters";
            return false;
        }

        $this->sanitized[$field] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return true;
    }

    /**
     * Validate method (GET, POST, etc)
     */
    public function validateMethod($expected) {
        $methods = (array)$expected;
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) {
            $this->errors[] = 'Invalid HTTP method';
            return false;
        }
        return true;
    }

    /**
     * Validate JSON request
     */
    public function validateJson() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            $this->errors[] = 'Content-Type must be application/json';
            return false;
        }

        $json = file_get_contents('php://input');
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = 'Invalid JSON: ' . json_last_error_msg();
            return false;
        }

        return $decoded;
    }

    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError() {
        return $this->errors[0] ?? null;
    }

    /**
     * Has errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Get sanitized values
     */
    public function getSanitized() {
        return $this->sanitized;
    }

    /**
     * Get sanitized value by field
     */
    public function getSanitized($field) {
        return $this->sanitized[$field] ?? null;
    }

    /**
     * Log request for audit trail
     */
    public function logRequest($action, $details = []) {
        if (!$this->logger) {
            return;
        }

        $this->logger->info('API Request: ' . $action, array_merge($details, [
            'method' => $_SERVER['REQUEST_METHOD'],
            'path' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }
}
