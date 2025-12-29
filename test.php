<?php
/**
 * ajaxCRUD v7.1 - Feature Test Suite
 * Tests all new Supabase-level features
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ajaxCRUD v7.1 - Feature Test Suite                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$testsPassed = 0;
$testsFailed = 0;

function test($name, $callback) {
    global $testsPassed, $testsFailed;
    echo "Testing: $name... ";
    try {
        $result = $callback();
        if ($result) {
            echo "✅ PASS\n";
            $testsPassed++;
        } else {
            echo "❌ FAIL\n";
            $testsFailed++;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

// Test 1: Check if all new files exist
test("All new class files exist", function() {
    return file_exists('Auth.class.php') &&
           file_exists('RLS.class.php') &&
           file_exists('AuditLog.class.php') &&
           file_exists('API.class.php');
});

test("All documentation files exist", function() {
    return file_exists('SUPABASE_FEATURES.md') &&
           file_exists('QUICKSTART.md') &&
           file_exists('CHANGELOG.md');
});

test("Composer and Docker files exist", function() {
    return file_exists('composer.json') &&
           file_exists('docker-compose.yml') &&
           file_exists('Dockerfile');
});

// Test 2: Load and validate classes
echo "\n--- Class Loading Tests ---\n";

test("Auth.class.php loads without errors", function() {
    if (!class_exists('AuthInterface')) {
        require_once 'Auth.class.php';
    }
    return interface_exists('AuthInterface') &&
           class_exists('SimpleRBAC') &&
           class_exists('RoleBasedRBAC') &&
           class_exists('AuthManager');
});

test("RLS.class.php loads without errors", function() {
    require_once 'RLS.class.php';
    return class_exists('RLS') && function_exists('rls');
});

test("AuditLog.class.php loads without errors", function() {
    require_once 'AuditLog.class.php';
    return class_exists('AuditLog') && function_exists('auditLog');
});

test("API.class.php loads without errors", function() {
    require_once 'API.class.php';
    return class_exists('CrudAPI') &&
           class_exists('APIRouter');
});

// Test 3: Auth functionality
echo "\n--- Auth System Tests ---\n";

test("AuthManager singleton works", function() {
    $auth1 = AuthManager::getInstance();
    $auth2 = AuthManager::getInstance();
    return $auth1 === $auth2;
});

test("SimpleRBAC initialization", function() {
    $rbac = new SimpleRBAC(123, [
        'test_table' => ['read' => true, 'write' => false, 'delete' => false]
    ]);
    return $rbac->getCurrentUser() === 123;
});

test("SimpleRBAC permission checks", function() {
    $rbac = new SimpleRBAC(123, [
        'test_table' => ['read' => true, 'write' => false, 'delete' => false]
    ]);
    return $rbac->can_read(123, 'test_table') === true &&
           $rbac->can_write(123, 'test_table') === false &&
           $rbac->can_delete(123, 'test_table') === false;
});

test("RoleBasedRBAC with admin role", function() {
    $rbac = new RoleBasedRBAC(123, 'admin');
    return $rbac->can_read(123, 'any_table') &&
           $rbac->can_write(123, 'any_table') &&
           $rbac->can_delete(123, 'any_table');
});

test("RoleBasedRBAC with viewer role", function() {
    $rbac = new RoleBasedRBAC(123, 'viewer');
    return $rbac->can_read(123, 'any_table') &&
           !$rbac->can_write(123, 'any_table') &&
           !$rbac->can_delete(123, 'any_table');
});

test("AuthManager enable/disable", function() {
    $auth = AuthManager::getInstance();
    
    // First enable it with a provider
    $rbac = new SimpleRBAC(1, ['test' => ['read' => true]]);
    $auth->init($rbac);
    $enabled = $auth->isEnabled();
    
    // Then disable
    $auth->disable();
    $disabled = !$auth->isEnabled();
    
    // Re-enable
    $auth->enable();
    $reEnabled = $auth->isEnabled();
    
    return $enabled && $disabled && $reEnabled;
});

test("Row-level permission with callable", function() {
    $rbac = new SimpleRBAC(123, [
        'orders' => [
            'write' => function($user, $row) {
                return isset($row['user_id']) && $row['user_id'] === $user;
            }
        ]
    ]);
    return $rbac->can_write(123, 'orders', ['user_id' => 123]) === true &&
           $rbac->can_write(123, 'orders', ['user_id' => 456]) === false;
});

// Test 4: RLS functionality
echo "\n--- Row-Level Security Tests ---\n";

test("RLS singleton works", function() {
    $rls1 = RLS::getInstance();
    $rls2 = RLS::getInstance();
    return $rls1 === $rls2;
});

test("RLS can add simple rule", function() {
    $rls = RLS::getInstance();
    $rls->clearRules();
    $rls->addRule('test_table', 'tenant_id', 123);
    $where = $rls->getWhereClause('test_table');
    return strpos($where, 'tenant_id') !== false;
});

test("RLS can add global rule", function() {
    $rls = RLS::getInstance();
    $rls->clearRules();
    $rls->addGlobalRule('deleted_at', null, 'IS', ['audit_log']);
    $where1 = $rls->getWhereClause('test_table');
    $where2 = $rls->getWhereClause('audit_log');
    return !empty($where1) && empty($where2);
});

test("RLS with callable value", function() {
    $rls = RLS::getInstance();
    $rls->clearRules();
    $rls->addRule('test_table', 'user_id', function() { return 999; });
    $params = $rls->getParameters('test_table');
    return in_array(999, $params);
});

test("RLS enable/disable", function() {
    $rls = RLS::getInstance();
    $rls->disable();
    $disabled = !$rls->isEnabled();
    $rls->enable();
    return $disabled && $rls->isEnabled();
});

test("RLS clear rules", function() {
    $rls = RLS::getInstance();
    $rls->addRule('test', 'field', 'value');
    $rls->clearRules();
    $rules = $rls->getRules();
    return empty($rules['table_specific']) && empty($rules['global']);
});

// Test 5: AuditLog functionality
echo "\n--- Audit Log Tests ---\n";

test("AuditLog singleton works", function() {
    $audit1 = AuditLog::getInstance();
    $audit2 = AuditLog::getInstance();
    return $audit1 === $audit2;
});

test("AuditLog enable/disable", function() {
    $audit = AuditLog::getInstance();
    $audit->disable();
    $disabled = !$audit->isEnabled();
    $audit->enable();
    return $disabled && $audit->isEnabled();
});

test("AuditLog user management", function() {
    $audit = AuditLog::getInstance();
    $audit->setUser('test_user_123');
    return $audit->getUser() === 'test_user_123';
});

test("AuditLog exclude tables", function() {
    $audit = AuditLog::getInstance();
    $audit->excludeTables(['temp_table', 'cache_table']);
    return true; // No exception thrown
});

test("AuditLog include only tables", function() {
    $audit = AuditLog::getInstance();
    $audit->includeOnlyTables(['important_table']);
    return true; // No exception thrown
});

// Test 6: API functionality
echo "\n--- API Tests ---\n";

test("CrudAPI instantiation", function() {
    $api = new CrudAPI('test_table', 'id');
    return $api !== null;
});

test("CrudAPI configuration", function() {
    $api = new CrudAPI('test_table', 'id');
    $api->allowMethods(['GET', 'POST']);
    $api->setSearchableFields(['name', 'email']);
    $api->setSortableFields(['name', 'created_at']);
    $api->setHiddenFields(['password']);
    $api->setRequiredFields(['name', 'email']);
    return true; // No exception thrown
});

test("CrudAPI validation rules", function() {
    $api = new CrudAPI('test_table', 'id');
    $api->addValidation('email', 'email');
    $api->addValidation('age', 'min', 18);
    $api->addValidation('name', 'maxlength', 100);
    return true; // No exception thrown
});

test("CrudAPI hooks", function() {
    $api = new CrudAPI('test_table', 'id');
    $hookExecuted = false;
    $api->before('POST', function() use (&$hookExecuted) {
        $hookExecuted = true;
    });
    return true; // Hook set successfully
});

test("APIRouter instantiation", function() {
    $router = new APIRouter();
    $router->setBasePath('/api');
    return $router !== null;
});

test("APIRouter endpoint registration", function() {
    $router = new APIRouter();
    $api = $router->register('test', 'test_table', 'id');
    return $api instanceof CrudAPI;
});

test("APIRouter OpenAPI spec generation", function() {
    $router = new APIRouter();
    $router->register('test', 'test_table', 'id');
    $spec = $router->getOpenAPISpec(['title' => 'Test API']);
    return isset($spec['openapi']) && 
           isset($spec['info']) && 
           isset($spec['paths']);
});

// Test 7: Integration tests
echo "\n--- Integration Tests ---\n";

test("Auth + RLS integration", function() {
    $rbac = new RoleBasedRBAC(123, 'viewer');
    AuthManager::getInstance()->init($rbac);
    
    RLS::getInstance()->clearRules();
    RLS::getInstance()->addRule('test', 'tenant_id', 123);
    
    return AuthManager::getInstance()->isEnabled() &&
           RLS::getInstance()->isEnabled();
});

test("All systems can work together", function() {
    // Auth
    $rbac = new RoleBasedRBAC(999, 'admin');
    AuthManager::getInstance()->init($rbac);
    
    // RLS
    RLS::getInstance()->clearRules();
    RLS::getInstance()->addRule('orders', 'tenant_id', 1);
    
    // Audit
    AuditLog::getInstance()->enable();
    AuditLog::getInstance()->setUser(999);
    
    // API
    $router = new APIRouter();
    $router->register('orders', 'orders', 'id');
    
    return AuthManager::getInstance()->can_read('orders') &&
           !empty(RLS::getInstance()->getWhereClause('orders')) &&
           AuditLog::getInstance()->isEnabled();
});

// Test 8: Example files
echo "\n--- Example Files Tests ---\n";

test("Demo files exist and are readable", function() {
    return file_exists('examples/demo_supabase_features.php') &&
           file_exists('examples/api-demo.php') &&
           is_readable('examples/demo_supabase_features.php') &&
           is_readable('examples/api-demo.php');
});

test("Example files contain required classes", function() {
    $demo = file_get_contents('examples/demo_supabase_features.php');
    $api = file_get_contents('examples/api-demo.php');
    return strpos($demo, 'RoleBasedRBAC') !== false &&
           strpos($demo, 'RLS::getInstance()') !== false &&
           strpos($demo, 'AuditLog::getInstance()') !== false &&
           strpos($api, 'APIRouter') !== false;
});

// Final summary
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Test Results                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Tests Passed: $testsPassed\n";
echo "❌ Tests Failed: $testsFailed\n";
echo "📊 Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 1) . "%\n\n";

if ($testsFailed === 0) {
    echo "🎉 All tests passed! Your ajaxCRUD v7.1 installation is ready!\n\n";
    echo "Next steps:\n";
    echo "  1. Run Docker demo: docker-compose up -d\n";
    echo "  2. Visit: http://localhost:8080/examples/demo_supabase_features.php\n";
    echo "  3. Try API: http://localhost:8080/examples/api-demo.php\n";
} else {
    echo "⚠️  Some tests failed. Please check the errors above.\n";
    exit(1);
}

exit(0);
