<?php
/**
 * API Response Handler
 * 
 * Standardizes API responses and HTTP headers
 */

class ApiResponse {
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        return self::send([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
        return self::send([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    /**
     * Send validation error response
     */
    public static function validationError($errors, $code = 422) {
        return self::send([
            'status' => 'error',
            'code' => $code,
            'message' => 'Validation failed',
            'errors' => $errors
        ], $code);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        return self::send([
            'status' => 'error',
            'code' => 404,
            'message' => $message
        ], 404);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        return self::send([
            'status' => 'error',
            'code' => 401,
            'message' => $message
        ], 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Forbidden') {
        return self::send([
            'status' => 'error',
            'code' => 403,
            'message' => $message
        ], 403);
    }

    /**
     * Send paginated response
     */
    public static function paginated($items, $total, $page, $perPage, $message = 'Success') {
        return self::send([
            'status' => 'success',
            'code' => 200,
            'message' => $message,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ], 200);
    }

    /**
     * Send response with headers
     */
    private static function send($response, $httpCode = 200) {
        // Set HTTP response code
        http_response_code($httpCode);
        
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Set content type
        header('Content-Type: application/json; charset=utf-8');
        
        // Prevent caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Handle JSON request body
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    /**
     * Check if request is JSON
     */
    public static function isJsonRequest() {
        return stripos($_SERVER['CONTENT_TYPE'] ?? '', 'json') !== false;
    }
}

?>
