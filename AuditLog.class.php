<?php
/************************************************************************/
/* AuditLog.class.php - Audit Trail System                             */
/* Tracks all CRUD operations with old/new values                       */
/************************************************************************/

/**
 * Audit Log System
 * 
 * Automatically logs all CREATE, UPDATE, DELETE operations
 * Stores: who, what, when, old values, new values
 * 
 * Setup:
 * 1. Run AuditLog::createTable() to create the audit table
 * 2. Enable: AuditLog::getInstance()->enable()
 * 3. Set user: AuditLog::getInstance()->setUser($userId)
 */
class AuditLog {
    private static $instance = null;
    private $enabled = false;
    private $currentUser = null;
    private $tableName = 'crud_audit';
    private $excludeTables = []; // Tables to exclude from auditing
    private $includeOnlyTables = []; // If set, only audit these tables

    private function __construct() {}

    public static function getInstance(): AuditLog {
        if (self::$instance === null) {
            self::$instance = new AuditLog();
        }
        return self::$instance;
    }

    /**
     * Enable audit logging
     */
    public function enable(): void {
        $this->enabled = true;
    }

    /**
     * Disable audit logging
     */
    public function disable(): void {
        $this->enabled = false;
    }

    /**
     * Check if enabled
     */
    public function isEnabled(): bool {
        return $this->enabled;
    }

    /**
     * Set current user (for audit trail)
     * @param mixed $user User ID, username, email, or any identifier
     */
    public function setUser($user): void {
        $this->currentUser = $user;
    }

    /**
     * Get current user
     */
    public function getUser() {
        return $this->currentUser;
    }

    /**
     * Set custom audit table name
     */
    public function setTableName(string $tableName): void {
        $this->tableName = $tableName;
    }

    /**
     * Exclude tables from auditing
     */
    public function excludeTables(array $tables): void {
        $this->excludeTables = $tables;
    }

    /**
     * Only audit specific tables
     */
    public function includeOnlyTables(array $tables): void {
        $this->includeOnlyTables = $tables;
    }

    /**
     * Check if a table should be audited
     */
    private function shouldAudit(string $table): bool {
        if (!$this->enabled) {
            return false;
        }

        // Don't audit the audit table itself
        if ($table === $this->tableName) {
            return false;
        }

        // Check exclusions
        if (in_array($table, $this->excludeTables)) {
            return false;
        }

        // Check inclusions (if set)
        if (!empty($this->includeOnlyTables)) {
            return in_array($table, $this->includeOnlyTables);
        }

        return true;
    }

    /**
     * Log an INSERT operation
     * 
     * @param string $table Table name
     * @param string $recordId Primary key value
     * @param array $newData New record data
     * @param array $metadata Optional additional metadata
     */
    public function logInsert(string $table, string $recordId, array $newData, array $metadata = []): void {
        if (!$this->shouldAudit($table)) {
            return;
        }

        $this->writeLog([
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => 'INSERT',
            'old_values' => null,
            'new_values' => json_encode($newData, JSON_UNESCAPED_UNICODE),
            'user_id' => $this->getUserIdentifier(),
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log an UPDATE operation
     * 
     * @param string $table Table name
     * @param string $recordId Primary key value
     * @param array $oldData Old record data
     * @param array $newData New record data
     * @param array $metadata Optional additional metadata
     */
    public function logUpdate(string $table, string $recordId, array $oldData, array $newData, array $metadata = []): void {
        if (!$this->shouldAudit($table)) {
            return;
        }

        // Only log if there are actual changes
        $changes = $this->getChanges($oldData, $newData);
        if (empty($changes)) {
            return;
        }

        $this->writeLog([
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => 'UPDATE',
            'old_values' => json_encode($oldData, JSON_UNESCAPED_UNICODE),
            'new_values' => json_encode($newData, JSON_UNESCAPED_UNICODE),
            'changed_fields' => json_encode(array_keys($changes), JSON_UNESCAPED_UNICODE),
            'user_id' => $this->getUserIdentifier(),
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log a DELETE operation
     * 
     * @param string $table Table name
     * @param string $recordId Primary key value
     * @param array $oldData Record data before deletion
     * @param array $metadata Optional additional metadata
     */
    public function logDelete(string $table, string $recordId, array $oldData = [], array $metadata = []): void {
        if (!$this->shouldAudit($table)) {
            return;
        }

        $this->writeLog([
            'table_name' => $table,
            'record_id' => $recordId,
            'action' => 'DELETE',
            'old_values' => !empty($oldData) ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => null,
            'user_id' => $this->getUserIdentifier(),
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Write log entry to database
     */
    private function writeLog(array $data): void {
        try {
            $columns = array_keys($data);
            $values = array_values($data);
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $columnList = '`' . implode('`,`', $columns) . '`';

            $sql = "INSERT INTO `{$this->tableName}` ($columnList) VALUES ($placeholders)";
            qr($sql, $values);
        } catch (Exception $e) {
            // Silently fail - don't break application if audit fails
            error_log("AuditLog error: " . $e->getMessage());
        }
    }

    /**
     * Get user identifier (string representation)
     */
    private function getUserIdentifier(): ?string {
        if ($this->currentUser === null) {
            return null;
        }

        if (is_scalar($this->currentUser)) {
            return (string)$this->currentUser;
        }

        if (is_array($this->currentUser)) {
            return json_encode($this->currentUser);
        }

        if (is_object($this->currentUser)) {
            if (method_exists($this->currentUser, '__toString')) {
                return (string)$this->currentUser;
            }
            return json_encode($this->currentUser);
        }

        return null;
    }

    /**
     * Get client IP address
     */
    private function getClientIP(): ?string {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }

        return null;
    }

    /**
     * Get changes between old and new data
     */
    private function getChanges(array $oldData, array $newData): array {
        $changes = [];
        
        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            
            // Handle type juggling
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    /**
     * Query audit logs
     * 
     * @param array $filters Filters: table, record_id, action, user_id, date_from, date_to
     * @param int $limit Maximum records to return
     * @param int $offset Offset for pagination
     * @return array Audit log records
     */
    public function query(array $filters = [], int $limit = 100, int $offset = 0): array {
        $where = [];
        $params = [];

        if (!empty($filters['table'])) {
            $where[] = 'table_name = ?';
            $params[] = $filters['table'];
        }

        if (!empty($filters['record_id'])) {
            $where[] = 'record_id = ?';
            $params[] = $filters['record_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM `{$this->tableName}` $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return q($sql, $params);
    }

    /**
     * Get audit history for a specific record
     */
    public function getRecordHistory(string $table, string $recordId, int $limit = 50): array {
        return $this->query([
            'table' => $table,
            'record_id' => $recordId
        ], $limit);
    }

    /**
     * Create the audit log table
     * Call this once to set up the database table
     * 
     * @param string|null $driver Database driver (mysql, pgsql, sqlite) - auto-detected if null
     * @return bool Success
     */
    public static function createTable(?string $driver = null): bool {
        global $DB_DRIVER;
        $driver = $driver ?? $DB_DRIVER ?? 'mysql';

        $sql = '';

        if ($driver === 'mysql') {
            $sql = "CREATE TABLE IF NOT EXISTS `crud_audit` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `table_name` VARCHAR(64) NOT NULL,
                `record_id` VARCHAR(255) NOT NULL,
                `action` VARCHAR(10) NOT NULL,
                `old_values` JSON,
                `new_values` JSON,
                `changed_fields` JSON,
                `user_id` VARCHAR(255),
                `ip_address` VARCHAR(45),
                `user_agent` VARCHAR(512),
                `metadata` JSON,
                `created_at` DATETIME NOT NULL,
                INDEX `idx_table_record` (`table_name`, `record_id`),
                INDEX `idx_action` (`action`),
                INDEX `idx_user` (`user_id`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        } elseif ($driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS crud_audit (
                id BIGSERIAL PRIMARY KEY,
                table_name VARCHAR(64) NOT NULL,
                record_id VARCHAR(255) NOT NULL,
                action VARCHAR(10) NOT NULL,
                old_values JSONB,
                new_values JSONB,
                changed_fields JSONB,
                user_id VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent VARCHAR(512),
                metadata JSONB,
                created_at TIMESTAMP NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_table_record ON crud_audit(table_name, record_id);
            CREATE INDEX IF NOT EXISTS idx_action ON crud_audit(action);
            CREATE INDEX IF NOT EXISTS idx_user ON crud_audit(user_id);
            CREATE INDEX IF NOT EXISTS idx_created ON crud_audit(created_at);";
        } elseif ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS crud_audit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name TEXT NOT NULL,
                record_id TEXT NOT NULL,
                action TEXT NOT NULL,
                old_values TEXT,
                new_values TEXT,
                changed_fields TEXT,
                user_id TEXT,
                ip_address TEXT,
                user_agent TEXT,
                metadata TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_table_record ON crud_audit(table_name, record_id);
            CREATE INDEX IF NOT EXISTS idx_action ON crud_audit(action);
            CREATE INDEX IF NOT EXISTS idx_user ON crud_audit(user_id);
            CREATE INDEX IF NOT EXISTS idx_created ON crud_audit(created_at);";
        }

        try {
            if ($driver === 'pgsql') {
                // Execute multiple statements for PostgreSQL
                $statements = explode(';', $sql);
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (!empty($stmt)) {
                        qr($stmt);
                    }
                }
            } else {
                qr($sql);
            }
            return true;
        } catch (Exception $e) {
            error_log("AuditLog table creation error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function for quick AuditLog access
 */
function auditLog(): AuditLog {
    return AuditLog::getInstance();
}
