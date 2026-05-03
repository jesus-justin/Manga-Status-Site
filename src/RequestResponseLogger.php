<?php
/**
 * Request/Response Logger Middleware
 *
 * Logs all HTTP requests and responses for audit trail, debugging,
 * and performance monitoring.
 *
 * @version 2.0.0
 */

class RequestResponseLogger {
    private $logger;
    private $startTime;
    private $startMemory;
    private $request = [];
    private $response = [];

    public function __construct($logger = null) {
        $this->logger = $logger ?? new Logger('api_requests');
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->captureRequest();
    }

    /**
     * Capture request details
     */
    private function captureRequest() {
        $this->request = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'path' => $_SERVER['REQUEST_URI'] ?? '',
            'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
            'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'headers' => $this->getCaptureableHeaders(),
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
    }

    /**
     * Get headers safe for logging
     */
    private function getCaptureableHeaders() {
        $headers = [];
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('HTTP_', '', $key);
                $headerName = strtolower(str_replace('_', '-', $headerName));

                if (!in_array($headerName, $sensitiveHeaders)) {
                    $headers[$headerName] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',   // Proxy
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Log API request
     */
    public function logRequest($action = null, $data = []) {
        $logData = array_merge([
            'type' => 'request',
            'action' => $action,
        ], $this->request, $data);

        $message = sprintf(
            "%s %s%s",
            $this->request['method'],
            $this->request['path'],
            $action ? " - $action" : ""
        );

        $this->logger->info($message, $logData);
    }

    /**
     * Log API response
     */
    public function logResponse($statusCode, $data = [], $message = null) {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2); // milliseconds
        $memoryUsed = memory_get_usage(true) - $this->startMemory;

        $this->response = [
            'type' => 'response',
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'memory_bytes' => $memoryUsed,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];

        $logLevel = $statusCode >= 400 ? 'warning' : 'info';
        $logMessage = sprintf(
            "%s %s - HTTP %d (%dms)",
            $this->request['method'],
            $this->request['path'],
            $statusCode,
            $duration
        );

        $this->logger->log($logLevel, $logMessage, array_merge($this->request, $this->response, 
            $message ? ['message' => $message] : []
        ));
    }

    /**
     * Log error response
     */
    public function logError($statusCode, $error, $details = []) {
        $this->logResponse($statusCode, array_merge(['error' => $error], $details), "Error occurred");
    }

    /**
     * Log performance metrics
     */
    public function logPerformance() {
        $duration = (microtime(true) - $this->startTime) * 1000;
        $memoryUsed = memory_get_usage(true) - $this->startMemory;
        $memoryPeak = memory_get_peak_usage(true);

        $metrics = [
            'duration_ms' => round($duration, 2),
            'memory_current' => $memoryUsed,
            'memory_peak' => $memoryPeak,
            'slow_query' => $duration > 1000  // Flag if slow
        ];

        if ($duration > 1000) {
            $this->logger->warning("Slow request detected", array_merge($this->request, $metrics));
        } else {
            $this->logger->debug("Performance metrics", metrics: $metrics);
        }

        return $metrics;
    }

    /**
     * Get request log data
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Get response log data
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Get full audit trail
     */
    public function getAuditTrail() {
        return [
            'request' => $this->request,
            'response' => $this->response,
            'duration_ms' => round((microtime(true) - $this->startTime) * 1000, 2)
        ];
    }
}

/**
 * Audit Logger for sensitive operations
 */
class AuditLogger {
    private $logger;

    public function __construct() {
        $this->logger = new Logger('audit');
    }

    /**
     * Log authentication event
     */
    public function logAuth($userId, $action, $success = true, $details = []) {
        $level = $success ? 'info' : 'warning';
        $this->logger->log($level, "Auth: $action for user $userId", array_merge([
            'user_id' => $userId,
            'action' => $action,
            'success' => $success,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ], $details));
    }

    /**
     * Log data modification
     */
    public function logDataModification($userId, $entity, $action, $entityId, $changes = []) {
        $this->logger->info("Data modification: $action on $entity #$entityId", [
            'user_id' => $userId,
            'entity' => $entity,
            'action' => $action,
            'entity_id' => $entityId,
            'changes' => $changes,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log permission denied
     */
    public function logPermissionDenied($userId, $resource, $action, $reason = '') {
        $this->logger->warning("Permission denied: $action on $resource", [
            'user_id' => $userId,
            'resource' => $resource,
            'action' => $action,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($event, $severity = 'warning', $details = []) {
        $this->logger->log($severity, "Security: $event", array_merge([
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ], $details));
    }
}
?>
