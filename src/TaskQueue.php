<?php
/**
 * Task Queue Manager
 * 
 * Manages async task queue and job scheduling
 */

class TaskQueue {
    private $conn;
    private $tableName = 'task_queue';

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
        $this->createTable();
    }

    /**
     * Create queue table if not exists
     */
    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_type VARCHAR(255) NOT NULL,
            payload JSON,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            error_message TEXT,
            priority INT DEFAULT 0,
            scheduled_for TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            INDEX idx_scheduled (scheduled_for)
        )";
        
        return $this->conn->query($sql);
    }

    /**
     * Queue a job
     */
    public function queue($jobType, $payload = [], $priority = 0, $scheduledFor = null) {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->tableName} (job_type, payload, priority, scheduled_for) 
             VALUES (?, ?, ?, ?)"
        );
        
        $payloadJson = json_encode($payload);
        $stmt->bind_param("ssis", $jobType, $payloadJson, $priority, $scheduledFor);
        
        return $stmt->execute() ? $this->conn->insert_id : false;
    }

    /**
     * Get pending job
     */
    public function getPendingJob() {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE status = 'pending' 
                AND (scheduled_for IS NULL OR scheduled_for <= NOW())
                AND attempts < max_attempts
                ORDER BY priority DESC, created_at ASC
                LIMIT 1";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    /**
     * Mark job as processing
     */
    public function markProcessing($jobId) {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tableName} SET status = 'processing' WHERE id = ?"
        );
        $stmt->bind_param("i", $jobId);
        return $stmt->execute();
    }

    /**
     * Mark job as completed
     */
    public function markCompleted($jobId) {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tableName} SET status = 'completed' WHERE id = ?"
        );
        $stmt->bind_param("i", $jobId);
        return $stmt->execute();
    }

    /**
     * Mark job as failed with error
     */
    public function markFailed($jobId, $errorMessage = '') {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tableName} 
             SET status = 'failed', error_message = ?, attempts = attempts + 1 
             WHERE id = ?"
        );
        $stmt->bind_param("si", $errorMessage, $jobId);
        return $stmt->execute();
    }

    /**
     * Retry failed job
     */
    public function retry($jobId) {
        $stmt = $this->conn->prepare(
            "UPDATE {$this->tableName} 
             SET status = 'pending', attempts = 0 
             WHERE id = ?"
        );
        $stmt->bind_param("i", $jobId);
        return $stmt->execute();
    }

    /**
     * Get job by ID
     */
    public function getJob($jobId) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->tableName} WHERE id = ?");
        $stmt->bind_param("i", $jobId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get job status
     */
    public function getStatus($jobId) {
        $job = $this->getJob($jobId);
        return $job ? $job['status'] : null;
    }

    /**
     * Get queue stats
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
                FROM {$this->tableName}";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }

    /**
     * Clear completed jobs older than days
     */
    public function clearCompleted($days = 7) {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->tableName} 
             WHERE status = 'completed' AND updated_at < ?"
        );
        $stmt->bind_param("s", $date);
        return $stmt->execute();
    }

    /**
     * Process queue (worker)
     */
    public function processQueue($handler) {
        while (true) {
            $job = $this->getPendingJob();
            
            if (!$job) {
                usleep(1000000); // Sleep 1 second
                continue;
            }
            
            $this->markProcessing($job['id']);
            
            try {
                $payload = json_decode($job['payload'], true);
                call_user_func($handler, $job['job_type'], $payload, $job['id']);
                $this->markCompleted($job['id']);
            } catch (Exception $e) {
                $this->markFailed($job['id'], $e->getMessage());
            }
        }
    }
}

?>
