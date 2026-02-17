<?php
/**
 * Logger Class
 * 
 * Provides logging functionality with file rotation and levels.
 */

class Logger {
    private $logFile;
    private $maxFileSize;

    public function __construct($logFile = null) {
        $this->logFile = $logFile ?? LOG_FILE;
        $this->maxFileSize = LOG_MAX_SIZE;
        $this->ensureLogDirectory();
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory() {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Rotate log file if it exceeds max size
     */
    private function rotateIfNeeded() {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $timestamp = date('Y-m-d_H-i-s');
            $rotated = $this->logFile . '.' . $timestamp;
            rename($this->logFile, $rotated);
            
            // Keep only last 10 rotated files
            $this->cleanOldLogs();
        }
    }

    /**
     * Clean old log files
     */
    private function cleanOldLogs() {
        $pattern = $this->logFile . '.*';
        $files = glob($pattern);
        if (count($files) > 10) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $toDelete = array_slice($files, 0, count($files) - 10);
            foreach ($toDelete as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Log a message
     */
    public function log($level, $message, $context = []) {
        $this->rotateIfNeeded();
        
        if (!in_array($level, ['debug', 'info', 'warning', 'error', 'critical'])) {
            $level = 'info';
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logLine = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        error_log($logLine, 3, $this->logFile);
    }

    /**
     * Log debug message
     */
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }

    /**
     * Log info message
     */
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }

    /**
     * Log error message
     */
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }

    /**
     * Log critical message
     */
    public function critical($message, $context = []) {
        $this->log('critical', $message, $context);
    }

    /**
     * Log an exception
     */
    public function exception(\Exception $e) {
        $this->error('Exception: ' . $e->getMessage(), [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Get log tail
     */
    public function getTail($lines = 100) {
        if (!file_exists($this->logFile)) {
            return '';
        }
        
        $file = new \SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $collection = [];

        foreach (new \LimitIterator($file, max(0, $lastLine - $lines), $lastLine) as $line) {
            $collection[] = $line;
        }

        return implode('', array_reverse($collection));
    }
}

?>
