<?php
/**
 * Rate Limiter
 * 
 * Provides request rate limiting and throttling
 */

class RateLimiter {
    private $conn;
    private $maxAttempts;
    private $decayMinutes;

    public function __construct(mysqli $conn, $maxAttempts = 60, $decayMinutes = 1) {
        $this->conn = $conn;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Check if action is allowed
     */
    public function attempt($key, $maxAttempts = null, $decayMinutes = null) {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $decayMinutes = $decayMinutes ?? $this->decayMinutes;

        $this->cleanup();

        $identifier = $this->hash($key);
        $now = time();
        $windowStart = $now - ($decayMinutes * 60);

        // Get current attempts
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > ?");
        $windowStartDate = date('Y-m-d H:i:s', $windowStart);
        $stmt->bind_param("ss", $identifier, $windowStartDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $attempts = $row['count'];

        if ($attempts >= $maxAttempts) {
            return false;
        }

        // Record attempt
        $stmt = $this->conn->prepare("INSERT INTO rate_limits (identifier, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();

        return true;
    }

    /**
     * Check if too many attempts
     */
    public function tooManyAttempts($key, $maxAttempts = null) {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        return !$this->attempt($key, $maxAttempts, 0);
    }

    /**
     * Get remaining attempts
     */
    public function remaining($key, $maxAttempts = null) {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $identifier = $this->hash($key);
        $windowStart = time() - ($this->decayMinutes * 60);
        $windowStartDate = date('Y-m-d H:i:s', $windowStart);

        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > ?");
        $stmt->bind_param("ss", $identifier, $windowStartDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $attempts = $row['count'];

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get time until retry available
     */
    public function availableIn($key) {
        $identifier = $this->hash($key);
        $windowStart = time() - ($this->decayMinutes * 60);
        $windowStartDate = date('Y-m-d H:i:s', $windowStart);

        $stmt = $this->conn->prepare("SELECT created_at FROM rate_limits WHERE identifier = ? AND created_at > ? ORDER BY created_at ASC LIMIT 1");
        $stmt->bind_param("ss", $identifier, $windowStartDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $oldestAttempt = strtotime($row['created_at']);
            $availableAt = $oldestAttempt + ($this->decayMinutes * 60);
            return max(0, $availableAt - time());
        }

        return 0;
    }

    /**
     * Clear rate limit for key
     */
    public function clear($key) {
        $identifier = $this->hash($key);
        $stmt = $this->conn->prepare("DELETE FROM rate_limits WHERE identifier = ?");
        $stmt->bind_param("s", $identifier);
        return $stmt->execute();
    }

    /**
     * Reset all rate limits
     */
    public function resetAll() {
        return $this->conn->query("TRUNCATE TABLE rate_limits");
    }

    /**
     * Cleanup old entries
     */
    private function cleanup() {
        $cutoff = time() - (24 * 60 * 60); // 24 hours
        $cutoffDate = date('Y-m-d H:i:s', $cutoff);
        $stmt = $this->conn->prepare("DELETE FROM rate_limits WHERE created_at < ?");
        $stmt->bind_param("s", $cutoffDate);
        $stmt->execute();
    }

    /**
     * Hash the key
     */
    private function hash($key) {
        return hash('sha256', $key);
    }

    /**
     * Create rate limits table if not exists
     */
    public static function createTable(mysqli $conn) {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(64) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_identifier_created (identifier, created_at)
        )";
        return $conn->query($sql);
    }
}

?>
