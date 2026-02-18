<?php
/**
 * Activity Logger
 * 
 * Tracks user activities and system events
 */

class ActivityLogger {
    private $conn;
    private $tableName = 'activity_logs';

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Log user activity
     */
    public function log($action, $description = '', $userId = null, $metadata = []) {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $metadataJson = !empty($metadata) ? json_encode($metadata) : null;

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->tableName} 
            (user_id, action, description, ip_address, user_agent, url, metadata, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        $stmt->bind_param(
            "issssss",
            $userId,
            $action,
            $description,
            $ipAddress,
            $userAgent,
            $url,
            $metadataJson
        );

        return $stmt->execute();
    }

    /**
     * Log login activity
     */
    public function logLogin($userId, $success = true) {
        $action = $success ? 'login_success' : 'login_failed';
        $description = $success ? 'User logged in successfully' : 'Failed login attempt';
        return $this->log($action, $description, $userId);
    }

    /**
     * Log logout activity
     */
    public function logLogout($userId) {
        return $this->log('logout', 'User logged out', $userId);
    }

    /**
     * Log manga action
     */
    public function logMangaAction($action, $mangaId, $mangaTitle = '', $userId = null) {
        $description = ucfirst($action) . " manga: $mangaTitle";
        return $this->log("manga_$action", $description, $userId, ['manga_id' => $mangaId]);
    }

    /**
     * Log progress update
     */
    public function logProgressUpdate($mangaId, $status, $userId = null) {
        return $this->log(
            'progress_update',
            "Updated reading progress to: $status",
            $userId,
            ['manga_id' => $mangaId, 'status' => $status]
        );
    }

    /**
     * Log API request
     */
    public function logApiRequest($endpoint, $method, $statusCode, $userId = null) {
        return $this->log(
            'api_request',
            "$method request to $endpoint",
            $userId,
            ['endpoint' => $endpoint, 'method' => $method, 'status_code' => $statusCode]
        );
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($event, $description, $userId = null) {
        return $this->log("security_$event", $description, $userId, ['severity' => 'high']);
    }

    /**
     * Get user activities
     */
    public function getUserActivities($userId, $limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tableName} 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?"
        );
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 100) {
        $stmt = $this->conn->prepare(
            "SELECT a.*, u.username 
            FROM {$this->tableName} a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC 
            LIMIT ?"
        );
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get activities by action
     */
    public function getActivitiesByAction($action, $limit = 50) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tableName} 
            WHERE action = ? 
            ORDER BY created_at DESC 
            LIMIT ?"
        );
        $stmt->bind_param("si", $action, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get activities by date range
     */
    public function getActivitiesByDateRange($startDate, $endDate, $limit = 1000) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->tableName} 
            WHERE created_at BETWEEN ? AND ? 
            ORDER BY created_at DESC 
            LIMIT ?"
        );
        $stmt->bind_param("ssi", $startDate, $endDate, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get activity statistics
     */
    public function getStatistics($days = 30) {
        $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $stmt = $this->conn->prepare(
            "SELECT 
                action,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users,
                DATE(created_at) as date
            FROM {$this->tableName}
            WHERE created_at >= ?
            GROUP BY action, DATE(created_at)
            ORDER BY created_at DESC"
        );
        $stmt->bind_param("s", $startDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Clean old logs
     */
    public function cleanup($daysToKeep = 90) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$daysToKeep days"));
        $stmt = $this->conn->prepare("DELETE FROM {$this->tableName} WHERE created_at < ?");
        $stmt->bind_param("s", $cutoffDate);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    /**
     * Create activity logs table
     */
    public static function createTable(mysqli $conn) {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            url VARCHAR(500),
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        return $conn->query($sql);
    }
}

?>
