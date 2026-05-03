<?php
/**
 * Automated Database Backup Manager
 *
 * Handles automated database backups with retention policy,
 * compression, and restoration capability.
 *
 * @version 2.0.0
 */

class DatabaseBackupManager {
    private $conn;
    private $logger;
    private $backupDir;
    private $retentionDays;
    private $maxBackups;

    /**
     * Constructor
     *
     * @param mysqli $conn Database connection
     * @param string $backupDir Directory to store backups
     * @param int $retentionDays Number of days to keep backups
     * @param int $maxBackups Maximum number of backups to keep
     */
    public function __construct($conn, $backupDir = 'backups', $retentionDays = 30, $maxBackups = 10) {
        $this->conn = $conn;
        $this->logger = new Logger('database_backup');
        $this->backupDir = $backupDir;
        $this->retentionDays = $retentionDays;
        $this->maxBackups = $maxBackups;

        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0700, true);
        }
    }

    /**
     * Create full database backup
     */
    public function backup($compress = true) {
        try {
            $dbName = $this->conn->select_db(
                getenv('DB_NAME') ?: ini_get('mysqli.default_db')
            ) ? $this->conn->get_server_info() : '';

            // Get actual database name from connection
            $result = $this->conn->query("SELECT DATABASE() as db");
            $row = $result->fetch_assoc();
            $dbName = $row['db'];

            // Get all tables
            $tables = [];
            $result = $this->conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }

            if (empty($tables)) {
                throw new Exception("No tables found in database");
            }

            // Generate backup filename
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$dbName}_{$timestamp}.sql";
            $filepath = $this->backupDir . '/' . $filename;

            // Create backup content
            $backupContent = $this->generateSqlDump($tables);

            // Write to file
            if (file_put_contents($filepath, $backupContent) === false) {
                throw new Exception("Failed to write backup file");
            }

            // Compress if requested
            if ($compress && extension_loaded('zlib')) {
                $gzipPath = $filepath . '.gz';
                $this->compressFile($filepath, $gzipPath);
                unlink($filepath);
                $filepath = $gzipPath;
                $filename = $filename . '.gz';
            }

            // Log success
            $fileSize = filesize($filepath);
            $this->logger->info("Database backup created", [
                'filename' => $filename,
                'size_bytes' => $fileSize,
                'size_mb' => round($fileSize / 1024 / 1024, 2),
                'compressed' => $compress,
                'tables' => count($tables)
            ]);

            // Clean old backups
            $this->cleanOldBackups();

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'size' => $fileSize,
                'timestamp' => $timestamp,
                'tables' => count($tables)
            ];

        } catch (Exception $e) {
            $this->logger->error("Backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate SQL dump content
     */
    private function generateSqlDump($tables) {
        $dump = "-- Manga Library Database Backup\n";
        $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $dump .= "-- Database: " . $this->conn->real_escape_string($this->conn->select_db) . "\n\n";

        foreach ($tables as $table) {
            $dump .= $this->getTableDump($table);
        }

        return $dump;
    }

    /**
     * Get dump for single table
     */
    private function getTableDump($table) {
        $dump = "\n\n-- Table structure for `$table`\n";
        $dump .= "DROP TABLE IF EXISTS `$table`;\n";

        // Get CREATE TABLE statement
        $result = $this->conn->query("SHOW CREATE TABLE `$table`");
        if ($result) {
            $row = $result->fetch_row();
            $dump .= $row[1] . ";\n";
        }

        // Get table data
        $result = $this->conn->query("SELECT * FROM `$table`");
        if ($result && $result->num_rows > 0) {
            $dump .= "\n-- Data for table `$table`\n";
            $dump .= "LOCK TABLES `$table` WRITE;\n";

            while ($row = $result->fetch_assoc()) {
                $dump .= "INSERT INTO `$table` VALUES (";
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = "NULL";
                    } else {
                        $values[] = "'" . $this->conn->real_escape_string($value) . "'";
                    }
                }
                $dump .= implode(",", $values) . ");\n";
            }

            $dump .= "UNLOCK TABLES;\n";
        }

        return $dump;
    }

    /**
     * Compress file using gzip
     */
    private function compressFile($source, $destination) {
        $data = file_get_contents($source);
        $gzData = gzencode($data, 9);
        file_put_contents($destination, $gzData);
    }

    /**
     * Restore database from backup
     */
    public function restore($backupFile) {
        try {
            if (!file_exists($backupFile)) {
                throw new Exception("Backup file not found: $backupFile");
            }

            // Handle gzip files
            if (substr($backupFile, -3) === '.gz') {
                $sqlContent = gzdecode(file_get_contents($backupFile));
            } else {
                $sqlContent = file_get_contents($backupFile);
            }

            if (!$sqlContent) {
                throw new Exception("Failed to read backup file");
            }

            // Execute SQL statements
            $statements = array_filter(explode(';', $sqlContent));
            $executed = 0;

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
                    if ($this->conn->query($statement) === false) {
                        $error = $this->conn->error;
                        // Log error but continue with non-critical errors
                        $this->logger->warning("Query error during restore: $error");
                    }
                    $executed++;
                }
            }

            $this->logger->info("Database restored", [
                'filename' => basename($backupFile),
                'statements_executed' => $executed
            ]);

            return [
                'success' => true,
                'statements' => $executed,
                'filename' => basename($backupFile)
            ];

        } catch (Exception $e) {
            $this->logger->error("Restore failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List all available backups
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . '/backup_*.sql*');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'size_mb' => round(filesize($file) / 1024 / 1024, 2),
                'created' => filemtime($file),
                'created_date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        // Sort by creation time descending
        usort($backups, function($a, $b) {
            return $b['created'] <=> $a['created'];
        });

        return $backups;
    }

    /**
     * Clean old backups based on retention policy
     */
    private function cleanOldBackups() {
        $backups = $this->listBackups();

        // Remove backups older than retention days
        $cutoffTime = time() - ($this->retentionDays * 86400);
        foreach ($backups as $backup) {
            if ($backup['created'] < $cutoffTime) {
                if (unlink($backup['path'])) {
                    $this->logger->info("Old backup deleted: " . $backup['filename']);
                }
            }
        }

        // Keep only latest N backups
        $backups = $this->listBackups();
        if (count($backups) > $this->maxBackups) {
            $toDelete = array_slice($backups, $this->maxBackups);
            foreach ($toDelete as $backup) {
                if (unlink($backup['path'])) {
                    $this->logger->info("Excess backup deleted: " . $backup['filename']);
                }
            }
        }
    }

    /**
     * Delete specific backup
     */
    public function deleteBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);

        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup not found'];
        }

        if (!unlink($filepath)) {
            return ['success' => false, 'error' => 'Failed to delete backup'];
        }

        $this->logger->info("Backup deleted: $filename");
        return ['success' => true, 'filename' => $filename];
    }

    /**
     * Download backup file
     */
    public function downloadBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);

        if (!file_exists($filepath)) {
            return false;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    /**
     * Get backup statistics
     */
    public function getStatistics() {
        $backups = $this->listBackups();
        $totalSize = array_sum(array_column($backups, 'size'));

        return [
            'total_backups' => count($backups),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'oldest_backup' => end($backups)['created_date'] ?? 'N/A',
            'latest_backup' => $backups[0]['created_date'] ?? 'N/A',
            'retention_days' => $this->retentionDays,
            'max_backups' => $this->maxBackups
        ];
    }
}
?>
