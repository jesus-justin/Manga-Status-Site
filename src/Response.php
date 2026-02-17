<?php
/**
 * Response Helper Class
 * 
 * Provides standardized response formatting for API and web handlers.
 */

class Response {
    /**
     * Send JSON response
     */
    public static function json($data, $statusCode = 200, $message = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'data' => $data
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send success response
     */
    public static function success($data, $message = 'Success', $statusCode = 200) {
        self::json($data, $statusCode, $message);
    }

    /**
     * Send error response
     */
    public static function error($message, $statusCode = 400, $data = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send redirect
     */
    public static function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Send file download
     */
    public static function file($filepath, $filename = null) {
        if (!file_exists($filepath)) {
            self::error('File not found', 404);
        }
        
        $filename = $filename ?? basename($filepath);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($filepath);
        exit;
    }

    /**
     * Send HTML response
     */
    public static function html($html, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /**
     * Send plain text response
     */
    public static function text($text, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=utf-8');
        echo $text;
        exit;
    }

    /**
     * Send 404 Not Found
     */
    public static function notFound($message = 'Not Found') {
        self::error($message, 404);
    }

    /**
     * Send 401 Unauthorized
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * Send 403 Forbidden
     */
    public static function forbidden($message = 'Access Denied') {
        self::error($message, 403);
    }

    /**
     * Send 429 Too Many Requests
     */
    public static function tooManyRequests($message = 'Too Many Requests', $retryAfter = 60) {
        header('Retry-After: ' . $retryAfter);
        self::error($message, 429);
    }

    /**
     * Send 500 Server Error
     */
    public static function serverError($message = 'Internal Server Error') {
        self::error($message, 500);
    }
}

?>
