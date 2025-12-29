<?php
/************************************************************************/
/* Auth.class.php - Authentication & Authorization System              */
/* Provides RBAC (Role-Based Access Control) and auth hooks            */
/************************************************************************/

/**
 * Interface for custom authentication/authorization implementations
 */
interface AuthInterface {
    /**
     * Check if user can read from a table/row
     * @param mixed $user Current user object/ID
     * @param string $table Table name
     * @param array $row Row data (for row-level checks)
     * @return bool
     */
    public function can_read($user, string $table, array $row = []): bool;

    /**
     * Check if user can write/update a table/row
     * @param mixed $user Current user object/ID
     * @param string $table Table name
     * @param array $row Row data (for row-level checks)
     * @return bool
     */
    public function can_write($user, string $table, array $row = []): bool;

    /**
     * Check if user can delete from a table/row
     * @param mixed $user Current user object/ID
     * @param string $table Table name
     * @param array $row Row data (for row-level checks)
     * @return bool
     */
    public function can_delete($user, string $table, array $row = []): bool;

    /**
     * Get current authenticated user
     * @return mixed User object/ID/array or null if not authenticated
     */
    public function getCurrentUser();
}

/**
 * Simple default RBAC implementation
 * You can extend this class or implement AuthInterface for custom logic
 */
class SimpleRBAC implements AuthInterface {
    protected $user;
    protected $permissions = [];

    /**
     * @param mixed $user Current user (can be ID, array, object)
     * @param array $permissions Permission matrix:
     *   [
     *     'table_name' => ['read' => true, 'write' => false, 'delete' => false],
     *     '*' => ['read' => true, 'write' => true, 'delete' => true] // wildcard for all tables
     *   ]
     */
    public function __construct($user = null, array $permissions = []) {
        $this->user = $user;
        $this->permissions = $permissions;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    public function setPermissions(array $permissions) {
        $this->permissions = $permissions;
    }

    public function addTablePermission(string $table, array $perms) {
        $this->permissions[$table] = $perms;
    }

    public function getCurrentUser() {
        return $this->user;
    }

    public function can_read($user, string $table, array $row = []): bool {
        return $this->checkPermission($table, 'read', $row);
    }

    public function can_write($user, string $table, array $row = []): bool {
        return $this->checkPermission($table, 'write', $row);
    }

    public function can_delete($user, string $table, array $row = []): bool {
        return $this->checkPermission($table, 'delete', $row);
    }

    /**
     * Internal permission check
     * Checks table-specific permissions first, then wildcard
     */
    protected function checkPermission(string $table, string $action, array $row = []): bool {
        // No user = no access (unless explicitly configured)
        if ($this->user === null && empty($this->permissions['*'])) {
            return false;
        }

        // Check table-specific permission
        if (isset($this->permissions[$table][$action])) {
            $permission = $this->permissions[$table][$action];
            
            // If permission is a callable, execute it for row-level checks
            if (is_callable($permission)) {
                return $permission($this->user, $row);
            }
            
            return (bool)$permission;
        }

        // Check wildcard permission
        if (isset($this->permissions['*'][$action])) {
            $permission = $this->permissions['*'][$action];
            
            if (is_callable($permission)) {
                return $permission($this->user, $row);
            }
            
            return (bool)$permission;
        }

        // Default deny
        return false;
    }
}

/**
 * Role-based RBAC with predefined roles
 */
class RoleBasedRBAC extends SimpleRBAC {
    protected $role;
    protected $rolePermissions = [
        'admin' => [
            '*' => ['read' => true, 'write' => true, 'delete' => true]
        ],
        'editor' => [
            '*' => ['read' => true, 'write' => true, 'delete' => false]
        ],
        'viewer' => [
            '*' => ['read' => true, 'write' => false, 'delete' => false]
        ],
        'guest' => [
            '*' => ['read' => false, 'write' => false, 'delete' => false]
        ]
    ];

    /**
     * @param mixed $user Current user
     * @param string $role Role name (admin, editor, viewer, guest)
     * @param array $customRolePermissions Override default role permissions
     */
    public function __construct($user = null, string $role = 'guest', array $customRolePermissions = []) {
        $this->user = $user;
        $this->role = $role;
        
        if (!empty($customRolePermissions)) {
            $this->rolePermissions = array_merge($this->rolePermissions, $customRolePermissions);
        }
        
        $this->permissions = $this->rolePermissions[$role] ?? $this->rolePermissions['guest'];
    }

    public function setRole(string $role) {
        $this->role = $role;
        $this->permissions = $this->rolePermissions[$role] ?? $this->rolePermissions['guest'];
    }

    public function getRole(): string {
        return $this->role;
    }
}

/**
 * Auth Manager - Global singleton for managing auth throughout ajaxCRUD
 */
class AuthManager {
    private static $instance = null;
    private $authProvider = null;
    private $enabled = false;

    private function __construct() {}

    public static function getInstance(): AuthManager {
        if (self::$instance === null) {
            self::$instance = new AuthManager();
        }
        return self::$instance;
    }

    /**
     * Initialize auth system with a provider
     * @param AuthInterface $provider Auth provider instance
     */
    public function init(AuthInterface $provider) {
        $this->authProvider = $provider;
        $this->enabled = true;
    }

    /**
     * Disable auth checks (useful for admin operations)
     */
    public function disable() {
        $this->enabled = false;
    }

    /**
     * Enable auth checks
     */
    public function enable() {
        $this->enabled = true;
    }

    public function isEnabled(): bool {
        return $this->enabled && $this->authProvider !== null;
    }

    public function getProvider(): ?AuthInterface {
        return $this->authProvider;
    }

    /**
     * Check read permission
     */
    public function can_read(string $table, array $row = []): bool {
        if (!$this->isEnabled()) {
            return true; // Auth disabled = allow all
        }
        
        $user = $this->authProvider->getCurrentUser();
        return $this->authProvider->can_read($user, $table, $row);
    }

    /**
     * Check write permission
     */
    public function can_write(string $table, array $row = []): bool {
        if (!$this->isEnabled()) {
            return true;
        }
        
        $user = $this->authProvider->getCurrentUser();
        return $this->authProvider->can_write($user, $table, $row);
    }

    /**
     * Check delete permission
     */
    public function can_delete(string $table, array $row = []): bool {
        if (!$this->isEnabled()) {
            return true;
        }
        
        $user = $this->authProvider->getCurrentUser();
        return $this->authProvider->can_delete($user, $table, $row);
    }

    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isEnabled()) {
            return null;
        }
        return $this->authProvider->getCurrentUser();
    }

    /**
     * Deny access with proper HTTP response
     */
    public function denyAccess(string $message = "Access denied") {
        http_response_code(403);
        echo "auth_error|forbidden|$message";
        exit();
    }
}
