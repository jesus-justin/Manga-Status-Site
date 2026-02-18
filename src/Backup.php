<?php
/**
 * Database Backup Utility
 * 
 * Provides database backup and restore functionality
 */

class Backup {
    private $conn;
    private $backupDir;

    public function __construct(mysqli $conn, $backupDir = 'backups/') {
        $this->conn = $conn;
        $this->backupDir = $backupDir;
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create database backup
     */
    public function createBackup($database = null) {
        if ($database === null) {
            $database = $this->conn->query("SELECT DATABASE()")->fetch_row()[0];
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$database}_backup_{$timestamp}.sql";
        $filepath = $this->backupDir . $filename;

        $tables = [];
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }

        $sqlDump = "-- Database Backup: {$database}\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop table
            $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";

            // Create table
            $createTable = $this->conn->query("SHOW CREATE TABLE `{$table}`")->fetch_row();
            $sqlDump .= $createTable[1] . ";\n\n";

            // Insert data
            $rows = $this->conn->query("SELECT * FROM `{$table}`");
            if ($rows && $rows->num_rows > 0) {
                while ($row = $rows->fetch_assoc()) {
                    $values = array_map(function($value) {
                        return $value === null ? 'NULL' : "'" . $this->conn->real_escape_string($value) . "'";
                    }, array_values($row));
                    
                    $sqlDump .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (file_put_contents($filepath, $sqlDump)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'tables' => count($tables)
            ];
        }

        return ['success' => false, 'error' => 'Failed to write backup file'];
    }

    /**
     * Restore from backup
     */
    public function restore($filename) {
        $filepath = $this->backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }

        $sql = file_get_contents($filepath);
        
        if ($this->conn->multi_query($sql)) {
            do {
                if ($result = $this->conn->store_result()) {
                    $result->free();
                }
            } while ($this->conn->next_result());
            
            return ['success' => true, 'message' => 'Database restored successfully'];
        }

        return ['success' => false, 'error' => $this->conn->error];
    }

    /**
     * List all backups
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . '*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => filemtime($file),
                'created_formatted' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });

        return $backups;
    }

    /**
     * Delete backup
     */
    public function deleteBackup($filename) {
        $filepath = $this->backupDir . $filename;
        
        if (file_exists($filepath) && unlink($filepath)) {
            return ['success' => true, 'message' => 'Backup deleted'];
        }

        return ['success' => false, 'error' => 'Failed to delete backup'];
    }

    /**
     * Auto cleanup old backups
     */
    public function cleanupOldBackups($daysToKeep = 30) {
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        $files = glob($this->backupDir . '*.sql');
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return ['success' => true, 'deleted' => $deleted];
    }
}

?>
