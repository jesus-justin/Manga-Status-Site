<?php
/**
 * ResponseFormatter Middleware
 *
 * Standardizes all HTTP responses with consistent JSON format,
 * error handling, CORS headers, and security headers.
 *
 * @version 2.0.0
 */

class ResponseFormatter {
    private $statusCode = 200;
    private $data = null;
    private $error = null;
    private $message = '';
    private $headers = [];

    public function __construct() {
        $this->setDefaultHeaders();
    }

    /**
     * Set default security and CORS headers
     */
    private function setDefaultHeaders() {
        // Security Headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // CORS Headers - Allow requests from same origin and configured hosts
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = [
            'http://localhost',
            'http://localhost:3000',
            'http://localhost:8000',
        ];

        if (in_array($origin, $allowedOrigins) || preg_match('/^https:\/\/([a-z0-9-]+\.)*manga-library\.com$/', $origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');

        // Content Type
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Send success response
     */
    public function success($data = null, $message = 'Success', $statusCode = 200) {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->message = $message;
        $this->sendJson();
    }

    /**
     * Send error response
     */
    public function error($error, $statusCode = 400, $details = null) {
        $this->statusCode = $statusCode;
        $this->error = $error;
        $this->message = 'Error';
        $this->data = $details;
        $this->sendJson();
    }

    /**
     * Send not found response
     */
    public function notFound($message = 'Resource not found') {
        $this->error($message, 404);
    }

    /**
     * Send unauthorized response
     */
    public function unauthorized($message = 'Unauthorized access') {
        $this->error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public function forbidden($message = 'Access forbidden') {
        $this->error($message, 403);
    }

    /**
     * Send validation error response
     */
    public function validationError($errors, $message = 'Validation failed') {
        $this->error($message, 422, ['errors' => $errors]);
    }

    /**
     * Send server error response
     */
    public function serverError($message = 'Internal server error') {
        $this->error($message, 500);
    }

    /**
     * Send JSON response
     */
    private function sendJson() {
        http_response_code($this->statusCode);

        $response = [
            'success' => $this->statusCode >= 200 && $this->statusCode < 300,
            'status' => $this->statusCode,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->error !== null) {
            $response['error'] = $this->error;
        }

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send file download
     */
    public function download($filePath, $fileName = null) {
        if (!file_exists($filePath)) {
            $this->notFound('File not found');
        }

        $fileName = $fileName ?? basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: no-cache, no-store, must-revalidate');

        readfile($filePath);
        exit;
    }

    /**
     * Send file view (inline)
     */
    public function view($filePath, $fileName = null) {
        if (!file_exists($filePath)) {
            $this->notFound('File not found');
        }

        $fileName = $fileName ?? basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Cache-Control: public, max-age=3600');

        readfile($filePath);
        exit;
    }

    /**
     * Send redirect response
     */
    public function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    /**
     * Send HTML response
     */
    public function html($content, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Send XML response
     */
    public function xml($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/xml; charset=utf-8');
        echo $data;
        exit;
    }

    /**
     * Send CSV response
     */
    public function csv($data, $fileName = 'export.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Handle OPTIONS request (CORS preflight)
     */
    public static function handleCorsPreFlight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
