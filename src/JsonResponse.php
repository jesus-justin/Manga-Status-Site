<?php
/**
 * JSON Response Helper
 * 
 * Provides standardized JSON response utilities
 */

class JsonResponse {
    /**
     * Send success response
     */
    public static function success($data = [], $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = []) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send paginated response
     */
    public static function paginated($data, $pagination, $message = 'Success') {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send created response
     */
    public static function created($data = [], $message = 'Resource created') {
        return self::success($data, $message, 201);
    }

    /**
     * Send validation error response
     */
    public static function validationError($errors = []) {
        return self::error('Validation failed', 422, $errors);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Forbidden') {
        return self::error($message, 403);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        return self::error($message, 404);
    }

    /**
     * Send server error response
     */
    public static function serverError($message = 'Server error') {
        return self::error($message, 500);
    }
}

?>
