<?php
/**
 * Health Check Endpoint
 *
 * Provides system health status for monitoring and uptime checks.
 * Verifies database connectivity, file system access, and memory usage.
 *
 * No authentication required for monitoring systems
 */

require_once 'db.php';
require_once 'src/ResponseFormatter.php';
require_once 'src/Logger.php';

$response = new ResponseFormatter();
$logger = new Logger('health_check');

try {
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '2.0.0',
        'checks' => []
    ];

    // Check 1: Database Connectivity
    $dbCheck = false;
    $dbMessage = 'Database connection failed';
    
    try {
        $result = $conn->query("SELECT 1");
        if ($result) {
            $dbCheck = true;
            $dbMessage = 'Connected';
        }
    } catch (Exception $e) {
        $dbMessage = 'Connection error: ' . $e->getMessage();
    }

    $health['checks']['database'] = [
        'status' => $dbCheck ? 'healthy' : 'unhealthy',
        'message' => $dbMessage,
        'timestamp' => date('c')
    ];

    // Check 2: File System
    $uploadDir = 'uploads';
    $logsDir = 'logs';
    $cacheDir = 'cache';
    
    $fsCheck = true;
    $fsIssues = [];

    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        $fsCheck = false;
        $fsIssues[] = "Upload directory not writable";
    }

    if (!is_dir($logsDir) || !is_writable($logsDir)) {
        $fsCheck = false;
        $fsIssues[] = "Logs directory not writable";
    }

    if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
        $fsCheck = false;
        $fsIssues[] = "Cache directory not writable";
    }

    $health['checks']['filesystem'] = [
        'status' => $fsCheck ? 'healthy' : 'unhealthy',
        'message' => $fsCheck ? 'All directories accessible' : implode(', ', $fsIssues),
        'timestamp' => date('c')
    ];

    // Check 3: PHP Extensions
    $requiredExtensions = ['mysqli', 'json', 'filter', 'pdo'];
    $missingExtensions = [];

    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }

    $extCheck = empty($missingExtensions);
    $health['checks']['php_extensions'] = [
        'status' => $extCheck ? 'healthy' : 'unhealthy',
        'message' => $extCheck ? 'All required extensions loaded' : 'Missing: ' . implode(', ', $missingExtensions),
        'timestamp' => date('c')
    ];

    // Check 4: Memory Usage
    $memoryLimit = ini_get('memory_limit');
    $memoryUsage = memory_get_usage(true);
    $memoryPeakUsage = memory_get_peak_usage(true);

    // Parse memory limit to bytes
    $memoryLimitBytes = (int)$memoryLimit;
    if (strpos($memoryLimit, 'M') !== false) {
        $memoryLimitBytes = (int)$memoryLimit * 1024 * 1024;
    } elseif (strpos($memoryLimit, 'G') !== false) {
        $memoryLimitBytes = (int)$memoryLimit * 1024 * 1024 * 1024;
    }

    $memoryUsagePercent = ($memoryUsage / $memoryLimitBytes) * 100;
    $memoryCheck = $memoryUsagePercent < 90;

    $health['checks']['memory'] = [
        'status' => $memoryCheck ? 'healthy' : 'warning',
        'usage_bytes' => $memoryUsage,
        'peak_usage_bytes' => $memoryPeakUsage,
        'limit' => $memoryLimit,
        'usage_percent' => round($memoryUsagePercent, 2),
        'timestamp' => date('c')
    ];

    // Check 5: Response Time
    $startTime = $_SERVER['REQUEST_TIME_FLOAT'];
    $responseTime = (microtime(true) - $startTime) * 1000; // in milliseconds

    $health['checks']['response_time'] = [
        'ms' => round($responseTime, 2),
        'status' => $responseTime < 1000 ? 'healthy' : 'slow',
        'timestamp' => date('c')
    ];

    // Determine overall status
    $unhealthyChecks = array_filter($health['checks'], function($check) {
        return ($check['status'] ?? 'unknown') === 'unhealthy';
    });

    if (!empty($unhealthyChecks)) {
        $health['status'] = 'unhealthy';
        $logger->error('Health check failed', ['failed_checks' => array_keys($unhealthyChecks)]);
    } else {
        $health['status'] = 'healthy';
        $logger->info('Health check passed');
    }

    // Send response with appropriate status code
    $statusCode = $health['status'] === 'healthy' ? 200 : 503;
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
    $logger->error('Health check exception: ' . $e->getMessage());
}
?>
