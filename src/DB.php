<?php
/**
 * Database Helper Class
 * 
 * Provides utility methods for common database operations.
 */

class DB {
    private $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Execute query with prepared statement
     */
    public function query($sql, $types, ...$params) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        return $stmt->get_result();
    }

    /**
     * Get single row
     */
    public function getRow($sql, $types, ...$params) {
        $result = $this->query($sql, $types, ...$params);
        return $result->fetch_assoc();
    }

    /**
     * Get all rows
     */
    public function getRows($sql, $types, ...$params) {
        $result = $this->query($sql, $types, ...$params);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get single value
     */
    public function getValue($sql, $types, ...$params) {
        $row = $this->getRow($sql, $types, ...$params);
        if ($row) {
            return reset($row);
        }
        return null;
    }

    /**
     * Get column values
     */
    public function getColumn($sql, $types, ...$params) {
        $rows = $this->getRows($sql, $types, ...$params);
        $column = array_column($rows, array_key_first($rows[0] ?? []));
        return $column;
    }

    /**
     * Count rows
     */
    public function count($table, $where = '') {
        $sql = "SELECT COUNT(*) as count FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    /**
     * Check if record exists
     */
    public function exists($table, $where) {
        $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
        $result = $this->conn->query($sql);
        return $result->num_rows > 0;
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    /**
     * Get affected rows
     */
    public function affectedRows() {
        return $this->conn->affected_rows;
    }

    /**
     * Escape string
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
}

?>
