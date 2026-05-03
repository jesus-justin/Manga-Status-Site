<?php
/**
 * Database Transaction Manager
 *
 * Provides ACID-compliant transaction support with automatic rollback,
 * savepoint support, and deadlock detection.
 *
 * @version 2.0.0
 */

class DatabaseTransactionManager {
    private $conn;
    private $logger;
    private $inTransaction = false;
    private $savepointStack = [];
    private $transactionStartTime = 0;

    public function __construct($conn, $logger = null) {
        $this->conn = $conn;
        $this->logger = $logger ?? new Logger('database_transactions');
    }

    /**
     * Begin transaction
     */
    public function begin() {
        if ($this->inTransaction) {
            throw new Exception("Transaction already in progress");
        }

        try {
            $this->conn->begin_transaction();
            $this->inTransaction = true;
            $this->transactionStartTime = microtime(true);
            $this->logger->debug("Transaction started");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to begin transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Commit transaction
     */
    public function commit() {
        if (!$this->inTransaction) {
            throw new Exception("No transaction in progress");
        }

        try {
            $duration = (microtime(true) - $this->transactionStartTime) * 1000;
            $this->conn->commit();
            $this->inTransaction = false;
            $this->savepointStack = [];
            $this->logger->debug("Transaction committed", ['duration_ms' => round($duration, 2)]);
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to commit transaction: " . $e->getMessage());
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        if (!$this->inTransaction) {
            throw new Exception("No transaction in progress");
        }

        try {
            $this->conn->rollback();
            $this->inTransaction = false;
            $this->savepointStack = [];
            $this->logger->warning("Transaction rolled back");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to rollback transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create savepoint within transaction
     */
    public function savepoint($name) {
        if (!$this->inTransaction) {
            throw new Exception("No transaction in progress");
        }

        // Ensure valid savepoint name
        $name = 'sp_' . preg_replace('/[^a-z0-9_]/i', '', $name);

        try {
            $this->conn->query("SAVEPOINT `$name`");
            $this->savepointStack[] = $name;
            $this->logger->debug("Savepoint created: $name");
            return $name;
        } catch (Exception $e) {
            $this->logger->error("Failed to create savepoint: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Rollback to savepoint
     */
    public function rollbackToSavepoint($name) {
        if (!$this->inTransaction) {
            throw new Exception("No transaction in progress");
        }

        $name = 'sp_' . preg_replace('/[^a-z0-9_]/i', '', $name);

        try {
            $this->conn->query("ROLLBACK TO `$name`");
            $this->logger->debug("Rolled back to savepoint: $name");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to rollback to savepoint: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Release savepoint
     */
    public function releaseSavepoint($name) {
        if (!$this->inTransaction) {
            throw new Exception("No transaction in progress");
        }

        $name = 'sp_' . preg_replace('/[^a-z0-9_]/i', '', $name);

        try {
            $this->conn->query("RELEASE SAVEPOINT `$name`");
            $this->savepointStack = array_filter($this->savepointStack, fn($sp) => $sp !== $name);
            $this->logger->debug("Savepoint released: $name");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to release savepoint: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute function within transaction
     */
    public function execute(callable $callback) {
        $this->begin();

        try {
            $result = call_user_func($callback, $this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Execute with automatic retry on deadlock
     */
    public function executeWithRetry(callable $callback, $maxRetries = 3, $retryDelay = 100) {
        $retries = 0;

        while ($retries < $maxRetries) {
            try {
                return $this->execute($callback);
            } catch (Exception $e) {
                // Check if it's a deadlock error (1213)
                if ($this->isDeadlock($e) && $retries < $maxRetries - 1) {
                    $retries++;
                    $this->logger->warning("Deadlock detected, retry $retries/$maxRetries", [
                        'error' => $e->getMessage(),
                        'delay_ms' => $retryDelay
                    ]);
                    usleep($retryDelay * 1000);
                    continue;
                }

                throw $e;
            }
        }

        throw new Exception("Max retries exceeded");
    }

    /**
     * Check if exception is a deadlock
     */
    private function isDeadlock(Exception $e) {
        return strpos($e->getMessage(), '1213') !== false ||
               strpos($e->getMessage(), 'Deadlock') !== false;
    }

    /**
     * Get transaction status
     */
    public function isInTransaction() {
        return $this->inTransaction;
    }

    /**
     * Get active savepoints
     */
    public function getSavepoints() {
        return $this->savepointStack;
    }

    /**
     * Get transaction duration in milliseconds
     */
    public function getTransactionDuration() {
        if (!$this->inTransaction) {
            return 0;
        }
        return (microtime(true) - $this->transactionStartTime) * 1000;
    }

    /**
     * Lock table for writing
     */
    public function lockTableWrite($table) {
        try {
            $this->conn->query("LOCK TABLES `$table` WRITE");
            $this->logger->debug("Table locked for write: $table");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to lock table: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lock table for reading
     */
    public function lockTableRead($table) {
        try {
            $this->conn->query("LOCK TABLES `$table` READ");
            $this->logger->debug("Table locked for read: $table");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to lock table: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Unlock all tables
     */
    public function unlockTables() {
        try {
            $this->conn->query("UNLOCK TABLES");
            $this->logger->debug("All tables unlocked");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to unlock tables: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Set isolation level
     */
    public function setIsolationLevel($level = 'READ_COMMITTED') {
        $validLevels = ['READ_UNCOMMITTED', 'READ_COMMITTED', 'REPEATABLE_READ', 'SERIALIZABLE'];

        if (!in_array($level, $validLevels)) {
            throw new Exception("Invalid isolation level: $level");
        }

        try {
            $this->conn->query("SET TRANSACTION ISOLATION LEVEL $level");
            $this->logger->debug("Isolation level set to: $level");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to set isolation level: " . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Transaction helper function
 */
function transaction($conn, callable $callback, $logger = null) {
    $txn = new DatabaseTransactionManager($conn, $logger);
    return $txn->executeWithRetry($callback);
}
?>
