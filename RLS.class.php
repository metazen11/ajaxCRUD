<?php
/************************************************************************/
/* RLS.class.php - Row-Level Security (Guards)                          */
/* Implements automatic WHERE scopes for multi-tenant/secured data      */
/************************************************************************/

/**
 * Row-Level Security Manager
 * 
 * Automatically applies WHERE clauses to all SELECT, UPDATE, DELETE queries
 * Example use cases:
 * - Multi-tenant apps: Only show rows where tenant_id = current_tenant
 * - User-specific data: Only show rows where user_id = current_user
 * - Soft deletes: Only show rows where deleted_at IS NULL
 * - Published content: Only show rows where status = 'published'
 */
class RLS {
    private static $instance = null;
    private $rules = []; // Per-table rules
    private $globalRules = []; // Applied to all tables
    private $enabled = true;

    private function __construct() {}

    public static function getInstance(): RLS {
        if (self::$instance === null) {
            self::$instance = new RLS();
        }
        return self::$instance;
    }

    /**
     * Add a row-level security rule for a specific table
     * 
     * @param string $table Table name
     * @param string $column Column name to filter on
     * @param mixed $value Value to match (or callable that returns value)
     * @param string $operator SQL operator (=, !=, IN, NOT IN, etc.)
     * 
     * Examples:
     *   RLS::getInstance()->addRule('orders', 'tenant_id', 123);
     *   RLS::getInstance()->addRule('posts', 'status', 'published');
     *   RLS::getInstance()->addRule('users', 'deleted_at', null, 'IS');
     *   RLS::getInstance()->addRule('data', 'user_id', function() { return $_SESSION['user_id'] ?? 0; });
     */
    public function addRule(string $table, string $column, $value, string $operator = '='): void {
        if (!isset($this->rules[$table])) {
            $this->rules[$table] = [];
        }

        $this->rules[$table][] = [
            'column' => $column,
            'value' => $value,
            'operator' => $operator
        ];
    }

    /**
     * Add a global rule that applies to all tables
     * Useful for soft-deletes or global tenant scoping
     * 
     * @param string $column Column name (must exist in all tables)
     * @param mixed $value Value to match
     * @param string $operator SQL operator
     * @param array $exceptTables Tables to exclude from this rule
     */
    public function addGlobalRule(string $column, $value, string $operator = '=', array $exceptTables = []): void {
        $this->globalRules[] = [
            'column' => $column,
            'value' => $value,
            'operator' => $operator,
            'except' => $exceptTables
        ];
    }

    /**
     * Get WHERE clause for a table
     * 
     * @param string $table Table name
     * @param bool $includeWhere Whether to prepend "WHERE" keyword
     * @return string SQL WHERE clause
     */
    public function getWhereClause(string $table, bool $includeWhere = false): string {
        if (!$this->enabled) {
            return '';
        }

        $conditions = [];

        // Apply table-specific rules
        if (isset($this->rules[$table])) {
            foreach ($this->rules[$table] as $rule) {
                $conditions[] = $this->buildCondition($rule);
            }
        }

        // Apply global rules
        foreach ($this->globalRules as $rule) {
            if (!in_array($table, $rule['except'])) {
                $conditions[] = $this->buildCondition($rule);
            }
        }

        if (empty($conditions)) {
            return '';
        }

        $whereClause = implode(' AND ', $conditions);
        return $includeWhere ? "WHERE $whereClause" : $whereClause;
    }

    /**
     * Get parameters for prepared statements
     * 
     * @param string $table Table name
     * @return array Parameters to bind
     */
    public function getParameters(string $table): array {
        if (!$this->enabled) {
            return [];
        }

        $params = [];

        // Table-specific rules
        if (isset($this->rules[$table])) {
            foreach ($this->rules[$table] as $rule) {
                $value = $this->resolveValue($rule['value']);
                if ($rule['operator'] !== 'IS' && $rule['operator'] !== 'IS NOT') {
                    $params[] = $value;
                }
            }
        }

        // Global rules
        foreach ($this->globalRules as $rule) {
            if (!in_array($table, $rule['except'])) {
                $value = $this->resolveValue($rule['value']);
                if ($rule['operator'] !== 'IS' && $rule['operator'] !== 'IS NOT') {
                    $params[] = $value;
                }
            }
        }

        return $params;
    }

    /**
     * Build a SQL condition from a rule
     */
    private function buildCondition(array $rule): string {
        $column = $rule['column'];
        $operator = $rule['operator'];
        $value = $this->resolveValue($rule['value']);

        // Handle NULL values
        if ($value === null) {
            return "`$column` IS NULL";
        }

        // Handle IS / IS NOT operators
        if ($operator === 'IS' || $operator === 'IS NOT') {
            if ($value === null) {
                return "`$column` $operator NULL";
            }
            return "`$column` $operator ?";
        }

        // Handle IN / NOT IN operators
        if ($operator === 'IN' || $operator === 'NOT IN') {
            if (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                return "`$column` $operator ($placeholders)";
            }
        }

        // Standard operators
        return "`$column` $operator ?";
    }

    /**
     * Resolve value (execute callable if needed)
     */
    private function resolveValue($value) {
        if (is_callable($value)) {
            return $value();
        }
        return $value;
    }

    /**
     * Clear all rules
     */
    public function clearRules(): void {
        $this->rules = [];
        $this->globalRules = [];
    }

    /**
     * Clear rules for a specific table
     */
    public function clearTableRules(string $table): void {
        unset($this->rules[$table]);
    }

    /**
     * Disable RLS (useful for admin operations)
     */
    public function disable(): void {
        $this->enabled = false;
    }

    /**
     * Enable RLS
     */
    public function enable(): void {
        $this->enabled = true;
    }

    /**
     * Check if RLS is enabled
     */
    public function isEnabled(): bool {
        return $this->enabled;
    }

    /**
     * Get all rules for debugging
     */
    public function getRules(): array {
        return [
            'table_specific' => $this->rules,
            'global' => $this->globalRules,
            'enabled' => $this->enabled
        ];
    }
}

/**
 * Helper function for quick RLS access
 */
function rls(): RLS {
    return RLS::getInstance();
}
