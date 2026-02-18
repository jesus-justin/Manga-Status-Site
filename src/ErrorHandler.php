<?php
/**
 * Error Handler
 * 
 * Custom error and exception handling with logging
 */

class ErrorHandler {
    private $logger;
    private $displayErrors;
    private $logErrors;

    public function __construct($displayErrors = false, $logErrors = true) {
        $this->displayErrors = $displayErrors;
        $this->logErrors = $logErrors;
        
        if ($this->logErrors) {
            $this->logger = new Logger('errors');
        }
    }

    /**
     * Register error and exception handlers
     */
    public function register() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = $this->getErrorType($errno);
        $message = "[$errorType] $errstr in $errfile on line $errline";

        if ($this->logErrors) {
            $this->logger->error($message);
        }

        if ($this->displayErrors) {
            $this->displayError($errorType, $errstr, $errfile, $errline);
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception) {
        $message = sprintf(
            "Uncaught Exception: %s in %s on line %d\nStack trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        if ($this->logErrors) {
            $this->logger->error($message);
        }

        if ($this->displayErrors) {
            $this->displayException($exception);
        } else {
            $this->displayGenericError();
        }

        exit(1);
    }

    /**
     * Handle fatal errors on shutdown
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
            
            if (!$this->displayErrors) {
                $this->displayGenericError();
            }
        }
    }

    /**
     * Get error type string
     */
    private function getErrorType($errno) {
        $types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $types[$errno] ?? 'Unknown Error';
    }

    /**
     * Display error in development mode
     */
    private function displayError($type, $message, $file, $line) {
        echo "<div style='background:#fee;border:2px solid #d00;padding:20px;margin:20px;border-radius:8px;font-family:monospace;'>";
        echo "<h3 style='color:#d00;margin:0 0 10px 0;'>⚠️ $type</h3>";
        echo "<p style='margin:5px 0;'><strong>Message:</strong> " . htmlspecialchars($message) . "</p>";
        echo "<p style='margin:5px 0;'><strong>File:</strong> " . htmlspecialchars($file) . "</p>";
        echo "<p style='margin:5px 0;'><strong>Line:</strong> $line</p>";
        echo "</div>";
    }

    /**
     * Display exception in development mode
     */
    private function displayException($exception) {
        echo "<div style='background:#fee;border:2px solid #d00;padding:20px;margin:20px;border-radius:8px;font-family:monospace;'>";
        echo "<h3 style='color:#d00;margin:0 0 10px 0;'>⚠️ Uncaught Exception</h3>";
        echo "<p style='margin:5px 0;'><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p style='margin:5px 0;'><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p style='margin:5px 0;'><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<details style='margin-top:10px;'><summary style='cursor:pointer;color:#d00;'>Stack Trace</summary>";
        echo "<pre style='background:#fff;padding:10px;margin-top:5px;overflow:auto;'>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        echo "</details></div>";
    }

    /**
     * Display generic error in production
     */
    private function displayGenericError() {
        http_response_code(500);
        echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
        echo "<div style='max-width:600px;margin:100px auto;text-align:center;font-family:sans-serif;'>";
        echo "<h1 style='color:#d7263d;font-size:3em;'>⚠️</h1>";
        echo "<h2 style='color:#141414;'>Something went wrong</h2>";
        echo "<p style='color:#666;'>We're sorry, but something went wrong. Please try again later.</p>";
        echo "</div></body></html>";
    }

    /**
     * Log custom error
     */
    public function logError($message, $context = []) {
        if ($this->logErrors && $this->logger) {
            $this->logger->error($message, $context);
        }
    }
}

?>
