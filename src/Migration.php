<?php
/**
 * Database Migration Helper
 * 
 * Provides database schema migration utilities
 */

class Migration {
    private $conn;
    private $migrationsDir;

    public function __construct(mysqli $conn, $migrationsDir = 'migrations/') {
        $this->conn = $conn;
        $this->migrationsDir = $migrationsDir;
        
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
        }
    }

    /**
     * Create migrations table
     */
    public function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        return $this->conn->query($sql);
    }

    /**
     * Run pending migrations
     */
    public function runPending() {
        $this->createMigrationsTable();
        
        $files = glob($this->migrationsDir . '*.php');
        sort($files);
        
        $executed = 0;
        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            
            // Check if already run
            $stmt = $this->conn->prepare("SELECT id FROM migrations WHERE migration = ?");
            $stmt->bind_param("s", $migrationName);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                continue;
            }
            
            // Run migration
            include $file;
            
            // Record migration
            $stmt = $this->conn->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
            $batch = $this->getLastBatch() + 1;
            $stmt->bind_param("si", $migrationName, $batch);
            $stmt->execute();
            
            $executed++;
        }
        
        return ['executed' => $executed, 'message' => "$executed migration(s) executed"];
    }

    /**
     * Get last batch number
     */
    private function getLastBatch() {
        $result = $this->conn->query("SELECT MAX(batch) as batch FROM migrations");
        $row = $result->fetch_assoc();
        return $row['batch'] ?? 0;
    }

    /**
     * Get migration history
     */
    public function getHistory() {
        $stmt = $this->conn->prepare(
            "SELECT migration, batch, executed_at FROM migrations ORDER BY batch DESC, id DESC"
        );
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Rollback last batch
     */
    public function rollback() {
        $lastBatch = $this->getLastBatch();
        
        if ($lastBatch === 0) {
            return ['success' => false, 'message' => 'No migrations to rollback'];
        }
        
        $stmt = $this->conn->prepare("DELETE FROM migrations WHERE batch = ?");
        $stmt->bind_param("i", $lastBatch);
        return $stmt->execute() ? 
               ['success' => true, 'message' => 'Rolled back batch ' . $lastBatch] :
               ['success' => false, 'message' => 'Rollback failed'];
    }
}

?>
