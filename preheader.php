<?php
/**
 * ajaxCRUD Database Connection & Helper Functions
 * Modernized for PHP 8.1+ with PDO
 * Supports: MySQL, SQLite, PostgreSQL
 *
 * @version 7.0
 */

session_start();

####################################################################################
## CSRF Protection
####################################################################################

/**
 * Generate or retrieve CSRF token
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field HTML
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}

/**
 * Get CSRF token for JavaScript/AJAX requests
 */
function csrfMeta(): string
{
    return '<meta name="csrf-token" content="' . htmlspecialchars(getCsrfToken()) . '">';
}

// Error reporting - show all errors except notices in development
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

####################################################################################
## Database Configuration
## Set $DB_DRIVER and $DB_CONFIG before including this file to override defaults
####################################################################################

/**
 * Database driver: 'mysql', 'sqlite', or 'pgsql'
 * Can be overridden by setting before include
 */
if (!isset($DB_DRIVER)) {
    $DB_DRIVER = 'mysql';
}

/**
 * Database configuration per driver
 * Can be overridden by setting before include
 */
if (!isset($DB_CONFIG)) {
    $DB_CONFIG = [
        'mysql' => [
            'host'     => 'localhost',
            'dbname'   => 'ajaxcrud_demos',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8mb4',
        ],
        'sqlite' => [
            'path'     => __DIR__ . '/database.sqlite',
        ],
        'pgsql' => [
            'host'     => 'localhost',
            'port'     => 5432,
            'dbname'   => 'ajaxcrud_demos',
            'username' => 'postgres',
            'password' => '',
        ],
    ];
}

// Common PDO options for all drivers
$DB_OPTIONS = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

####################################################################################

/**
 * Global PDO connection instance
 */
$pdo = null;

/**
 * Get or create PDO database connection
 */
function getDB(): PDO
{
    global $pdo, $DB_DRIVER, $DB_CONFIG, $DB_OPTIONS;

    if ($pdo === null) {
        try {
            $config = $DB_CONFIG[$DB_DRIVER] ?? null;

            if (!$config) {
                throw new Exception("Unknown database driver: $DB_DRIVER");
            }

            switch ($DB_DRIVER) {
                case 'mysql':
                    $dsn = sprintf(
                        'mysql:host=%s;dbname=%s;charset=%s',
                        $config['host'],
                        $config['dbname'],
                        $config['charset']
                    );
                    $pdo = new PDO($dsn, $config['username'], $config['password'], $DB_OPTIONS);
                    break;

                case 'sqlite':
                    $dsn = 'sqlite:' . $config['path'];
                    $pdo = new PDO($dsn, null, null, $DB_OPTIONS);
                    // Enable foreign keys for SQLite
                    $pdo->exec('PRAGMA foreign_keys = ON');
                    break;

                case 'pgsql':
                    $dsn = sprintf(
                        'pgsql:host=%s;port=%d;dbname=%s',
                        $config['host'],
                        $config['port'],
                        $config['dbname']
                    );
                    $pdo = new PDO($dsn, $config['username'], $config['password'], $DB_OPTIONS);
                    break;

                default:
                    throw new Exception("Unsupported database driver: $DB_DRIVER");
            }
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Unable to connect to database. Please check your configuration.');
        }
    }

    return $pdo;
}

/**
 * Get current database driver
 */
function getDBDriver(): string
{
    global $DB_DRIVER;
    return $DB_DRIVER;
}

/**
 * Set database driver (useful for testing)
 */
function setDBDriver(string $driver): void
{
    global $DB_DRIVER, $pdo;
    $DB_DRIVER = $driver;
    $pdo = null; // Reset connection for new driver
}

// Initialize connection on load
$pdo = getDB();

####################################################################################
## Query Helper Functions - Compatible API with modern security
####################################################################################

/**
 * Execute a query and return all results as an array
 *
 * For SELECT: Returns array of rows (empty array if no results)
 * For INSERT/UPDATE/DELETE: Returns true on success, false on failure
 *
 * @param string $sql The SQL query (use ? for placeholders)
 * @param array $params Parameters to bind (optional)
 * @param bool $debug Output the query for debugging
 * @return array|bool
 */
if (!function_exists('q')) {
    function q(string $sql, array $params = [], bool $debug = false): array|bool
    {
        $db = getDB();

        if ($debug) {
            echo "<br>" . htmlspecialchars($sql) . "<br>";
            if (!empty($params)) {
                echo "Params: " . htmlspecialchars(print_r($params, true)) . "<br>";
            }
        }

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Check if this is a SELECT query
            $firstWord = strtoupper(substr(ltrim($sql), 0, 6));

            if (in_array($firstWord, ['SELECT', 'SHOW', 'DESCRI', 'PRAGMA'])) {
                return $stmt->fetchAll();
            }

            // For INSERT/UPDATE/DELETE, return success based on affected rows
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log('Query error: ' . $e->getMessage() . ' SQL: ' . $sql);
            if ($debug) {
                echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
            return [];
        }
    }
}

/**
 * Execute a query and return a single value or single row
 *
 * @param string $sql The SQL query
 * @param array $params Parameters to bind
 * @param bool $debug Output the query for debugging
 * @return mixed Single value, row array, or null
 */
if (!function_exists('q1')) {
    function q1(string $sql, array $params = [], bool $debug = false): mixed
    {
        $db = getDB();

        if ($debug) {
            echo "<br>" . htmlspecialchars($sql) . "<br>";
        }

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();

            if ($row === false) {
                return null;
            }

            // If only one column, return just the value
            if (count($row) === 1) {
                return reset($row);
            }

            return $row;

        } catch (PDOException $e) {
            error_log('Query error: ' . $e->getMessage() . ' SQL: ' . $sql);
            if ($debug) {
                echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
            return null;
        }
    }
}

/**
 * Execute a query and return a single row or success boolean
 *
 * @param string $sql The SQL query
 * @param array $params Parameters to bind
 * @param bool $debug Output the query for debugging
 * @return array|bool Single row array or boolean for write operations
 */
if (!function_exists('qr')) {
    function qr(string $sql, array $params = [], bool $debug = false): array|bool
    {
        $db = getDB();

        if ($debug) {
            echo "<br>" . htmlspecialchars($sql) . "<br>";
        }

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Check if this is a write operation
            $firstWord = strtoupper(substr(ltrim($sql), 0, 6));

            if (in_array($firstWord, ['INSERT', 'UPDATE', 'DELETE'])) {
                return $stmt->rowCount() > 0;
            }

            $row = $stmt->fetch();
            return $row ?: [];

        } catch (PDOException $e) {
            error_log('Query error: ' . $e->getMessage() . ' SQL: ' . $sql);
            if ($debug) {
                echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
            return false;
        }
    }
}

/**
 * Get the last inserted ID
 */
if (!function_exists('lastInsertId')) {
    function lastInsertId(): string
    {
        return getDB()->lastInsertId();
    }
}

/**
 * Escape an identifier (table/column name) for safe use in SQL
 * Note: Use prepared statements with parameters for values!
 */
if (!function_exists('escapeIdentifier')) {
    function escapeIdentifier(string $identifier): string
    {
        // Only allow alphanumeric and underscore
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }
}

/**
 * Begin a transaction
 */
if (!function_exists('beginTransaction')) {
    function beginTransaction(): bool
    {
        return getDB()->beginTransaction();
    }
}

/**
 * Commit a transaction
 */
if (!function_exists('commitTransaction')) {
    function commitTransaction(): bool
    {
        return getDB()->commit();
    }
}

/**
 * Rollback a transaction
 */
if (!function_exists('rollbackTransaction')) {
    function rollbackTransaction(): bool
    {
        return getDB()->rollBack();
    }
}
