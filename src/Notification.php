<?php
/**
 * Notification System
 * 
 * Handles in-app notifications and alerts
 */

class Notification {
    private $conn;
    private $tableName = 'notifications';

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Create notification
     */
    public function create($userId, $title, $message, $type = 'info', $actionUrl = null) {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->tableName} 
            (user_id, title, message, type, action_url, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())"
        );

        $stmt->bind_param(
            "issss",
            $userId,
            $title,
            $message,
            $type,
            $actionUrl
        );

        return $stmt->execute();
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $unreadOnly = false) {
        $sql = "SELECT * FROM {$this->tableName} WHERE user_id = ?";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Mark as read
     */
    public function markAsRead($notificationId) {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tableName} SET is_read = 1, read_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tableName} SET is_read = 1, read_at = NOW() WHERE user_id = ?"
        );
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    /**
     * Delete notification
     */
    public function delete($notificationId) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->tableName} WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM {$this->tableName} WHERE user_id = ? AND is_read = 0"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }

    /**
     * Create table
     */
    public static function createTable(mysqli $conn) {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255),
            message TEXT,
            type VARCHAR(50),
            action_url VARCHAR(500),
            is_read BOOLEAN DEFAULT 0,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_is_read (is_read),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        return $conn->query($sql);
    }
}

?>
