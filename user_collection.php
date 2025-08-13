<?php
require_once 'auth.php';
require_once 'db.php';

class UserCollection {
    private $conn;
    private $auth;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->auth = new Auth($conn);
    }
    
    public function addToCollection($user_id, $manga_id, $status = 'want to read', $rating = null, $progress = 0) {
        $stmt = $this->conn->prepare("INSERT INTO user_manga (user_id, manga_id, status, rating, progress) VALUES (?, ?, ?, ?, ?)
                                     ON DUPLICATE KEY UPDATE status = VALUES(status), rating = VALUES(rating), progress = VALUES(progress)");
        $stmt->bind_param("iisdi", $user_id, $manga_id, $status, $rating, $progress);
        return $stmt->execute();
    }
    
    public function getUserCollection($user_id, $status = null) {
        $sql = "SELECT um.*, m.title, m.category, m.status as manga_status, m.last_chapter, m.read_link
                FROM user_manga um
                JOIN manga m ON um.manga_id = m.id
                WHERE um.user_id = ?";
        
        if ($status) {
            $sql .= " AND um.status = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $status);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function updateProgress($user_id, $manga_id, $progress, $last_chapter = null) {
        $stmt = $this->conn->prepare("UPDATE user_manga SET progress = ?, updated_at = NOW() WHERE user_id = ? AND manga_id = ?");
        $stmt->bind_param("iii", $progress, $user_id, $manga_id);
        
        if ($stmt->execute() && $last_chapter) {
            // Also update the manga's last chapter if provided
            $stmt2 = $this->conn->prepare("UPDATE manga SET last_chapter = ? WHERE id = ?");
            $stmt2->bind_param("si", $last_chapter, $manga_id);
            $stmt2->execute();
        }
        
        return $stmt->execute();
    }
    
    public function updateRating($user_id, $manga_id, $rating) {
        $stmt = $this->conn->prepare("UPDATE user_manga SET rating = ?, updated_at = NOW() WHERE user_id = ? AND manga_id = ?");
        $stmt->bind_param("dii", $rating, $user_id, $manga_id);
        return $stmt->execute();
    }
    
    public function removeFromCollection($user_id, $manga_id) {
        $stmt = $this->conn->prepare("DELETE FROM user_manga WHERE user_id = ? AND manga_id = ?");
        $stmt->bind_param("ii", $user_id, $manga_id);
        return $stmt->execute();
    }
    
    public function getCollectionStats($user_id) {
        $stats = [
            'total' => 0,
            'want_to_read' => 0,
            'currently_reading' => 0,
            'finished' => 0,
            'stopped' => 0,
            'average_rating' => 0
        ];
        
        $sql = "SELECT status, COUNT(*) as count, AVG(rating) as avg_rating
                FROM user_manga
                WHERE user_id = ?
                GROUP BY status";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $stats[$row['status']] = $row['count'];
            $stats['total'] += $row['count'];
            if ($row['avg_rating']) {
                $stats['average_rating'] = $row['avg_rating'];
            }
        }
        
        return $stats;
    }
}
?>
