<?php
/**
 * Global Error Handler Bootstrap
 *
 * Registers global error handlers for all PHP errors and exceptions.
 * Should be included at the very beginning of all entry points.
 *
 * Include this file first in:
 * - index.php
 * - admin_dashboard.php
 * - api_routes.php
 * - All public entry points
 *
 * @version 2.0.0
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/ResponseFormatter.php';

class GlobalErrorHandler {
    private static $logger;
    private static $registered = false;

    /**
     * Initialize and register global error handlers
     */
    public static function init($debug = false, $environment = 'development') {
        if (self::$registered) {
            return;
        }

        // Create logger instance
        self::$logger = new Logger('errors');

        // Set error reporting based on environment
        if ($environment === 'production') {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
            ini_set('error_log', __DIR__ . '/logs/php_errors.log');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', $debug ? '1' : '0');
            ini_set('log_errors', '1');
        }

        // Register error handler
        set_error_handler([self::class, 'handleError'], E_ALL);

        // Register exception handler
        set_exception_handler([self::class, 'handleException']);

        // Register shutdown handler for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);
        $message = "[$errorType] $errstr in " . basename($errfile) . " on line $errline";

        // Log the error
        if (self::$logger) {
            switch ($errno) {
                case E_WARNING:
                case E_USER_WARNING:
                    self::$logger->warning($message);
                    break;
                case E_NOTICE:
                case E_USER_NOTICE:
                    self::$logger->info($message);
                    break;
                default:
                    self::$logger->error($message);
            }
        } else {
            error_log($message);
        }

        // Don't show errors in production
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            // API request - return JSON error
            $response = new ResponseFormatter();
            $response->serverError('An error occurred');
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $message = sprintf(
            "Uncaught Exception: %s in %s on line %d",
            $exception->getMessage(),
            basename($exception->getFile()),
            $exception->getLine()
        );

        if (self::$logger) {
            self::$logger->error($message, [
                'stack_trace' => $exception->getTraceAsString(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode()
            ]);
        } else {
            error_log($message);
        }

        // Return JSON error for API requests
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'status' => 500,
                'error' => 'Internal Server Error',
                'message' => 'An unexpected error occurred'
            ]);
        } else {
            // Show generic error page
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Error</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background: #f5efe3;
                        color: #141414;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        margin: 0;
                        padding: 20px;
                    }
                    .error-container {
                        background: white;
                        border-left: 4px solid #d7263d;
                        padding: 30px;
                        border-radius: 4px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        max-width: 600px;
                    }
                    h1 {
                        color: #d7263d;
                        margin: 0 0 10px 0;
                    }
                    p {
                        margin: 10px 0;
                        line-height: 1.6;
                    }
                    .back-link {
                        display: inline-block;
                        margin-top: 20px;
                        color: #d7263d;
                        text-decoration: none;
                    }
                    .back-link:hover {
                        text-decoration: underline;
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <h1>500 - Internal Server Error</h1>
                    <p>An unexpected error occurred while processing your request.</p>
                    <p>Our team has been notified of this issue and is working on a fix.</p>
                    <a href="/" class="back-link">← Go back home</a>
                </div>
            </body>
            </html>
            <?php
        }

        exit(1);
    }

    /**
     * Handle fatal errors on shutdown
     */
    public static function handleShutdown() {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], 
            [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR])) {
            
            $errorType = self::getErrorType($error['type']);
            $message = "Fatal Error [$errorType]: {$error['message']} in " . 
                      basename($error['file']) . " on line {$error['line']}";

            if (self::$logger) {
                self::$logger->error($message);
            } else {
                error_log($message);
            }

            // Return error response
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'status' => 500,
                    'error' => 'Fatal Error',
                    'message' => 'A fatal error occurred'
                ]);
            } else {
                http_response_code(500);
                echo "Fatal Error: Contact administrator";
            }
        }
    }

    /**
     * Get human-readable error type
     */
    private static function getErrorType($errno) {
        $types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Fatal Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Runtime Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecation Notice',
            E_USER_DEPRECATED => 'User Deprecation Notice'
        ];
        return $types[$errno] ?? 'Unknown Error';
    }

    /**
     * Log a message directly
     */
    public static function log($level, $message, $context = []) {
        if (self::$logger) {
            self::$logger->log($level, $message, $context);
        }
    }
}

// Auto-initialize on include
GlobalErrorHandler::init(
    $_ENV['APP_DEBUG'] ?? false,
    $_ENV['APP_ENV'] ?? 'development'
);
?>
