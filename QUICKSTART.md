# Quick Start Guide - ajaxCRUD v7.1

## Installation

### Option 1: Composer (Recommended)
```bash
composer require ajaxcrud/ajaxcrud
```

### Option 2: Docker (Instant Demo)
```bash
git clone https://github.com/metazen11/ajaxCRUD.git
cd ajaxCRUD
docker-compose up -d
```

Visit: http://localhost:8080/examples/demo_supabase_features.php

### Option 3: Manual
```bash
git clone https://github.com/metazen11/ajaxCRUD.git
```

Then include in your PHP:
```php
require_once 'preheader.php';
require_once 'ajaxCRUD.class.php';
```

## 5-Minute Setup

### 1. Basic CRUD (Original Features)

```php
<?php
require 'vendor/autoload.php'; // or include files manually

// Database config (in preheader.php or here)
$DB_DRIVER = 'mysql';
$DB_CONFIG = ['mysql' => [
    'host' => 'localhost',
    'name' => 'mydb',
    'user' => 'root',
    'pass' => ''
]];

// Create CRUD table
$tbl = new ajaxCRUD("Contact", "contacts", "id");
$tbl->displayAs("name", "Full Name");
$tbl->displayAs("email", "Email Address");
$tbl->modifyFieldWithClass("email", "email");
$tbl->defineToggle("active", "1", "0");
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="vendor/ajaxcrud/ajaxcrud/css/default.css">
</head>
<body>
    <?php $tbl->showTable(); ?>
</body>
</html>
```

That's it! You now have inline-editable tables with auto-save.

### 2. Add Authentication (NEW in v7.1)

```php
<?php
require 'vendor/autoload.php';
require 'Auth.class.php';

// Setup RBAC
$rbac = new RoleBasedRBAC(
    $_SESSION['user_id'] ?? null, 
    $_SESSION['role'] ?? 'guest'  // admin, editor, viewer, guest
);

// Initialize auth
AuthManager::getInstance()->init($rbac);

// Now the CRUD table respects permissions!
$tbl = new ajaxCRUD("Contact", "contacts", "id");
$tbl->showTable();
?>
```

### 3. Add Row-Level Security (NEW in v7.1)

```php
<?php
require 'RLS.class.php';

// Multi-tenant: users only see their own tenant's data
RLS::getInstance()->addRule('contacts', 'tenant_id', $_SESSION['tenant_id']);

// Only show active records
RLS::getInstance()->addRule('contacts', 'status', 'active');

// All queries automatically include these filters!
$tbl = new ajaxCRUD("Contact", "contacts", "id");
$tbl->showTable();
?>
```

### 4. Enable Audit Log (NEW in v7.1)

```php
<?php
require 'AuditLog.class.php';

// One-time: create audit table
AuditLog::createTable();

// Enable logging
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser($_SESSION['user_id']);

// All changes are now automatically logged!
$tbl = new ajaxCRUD("Contact", "contacts", "id");
$tbl->showTable();

// Query audit history
$history = auditLog()->getRecordHistory('contacts', '123');
foreach ($history as $log) {
    echo "{$log['action']} by {$log['user_id']} at {$log['created_at']}\n";
}
?>
```

### 5. Expose as REST API (NEW in v7.1)

Create `api.php`:

```php
<?php
require 'vendor/autoload.php';
require 'API.class.php';

$router = new APIRouter();
$router->setBasePath('/api');

$router->register('contacts', 'contacts', 'id', function($api) {
    $api->setSearchableFields(['name', 'email']);
    $api->setRequiredFields(['name', 'email']);
    $api->addValidation('email', 'email');
});

// Get OpenAPI spec with ?openapi=1
if (isset($_GET['openapi'])) {
    $router->outputOpenAPISpec(['title' => 'My API', 'version' => '1.0']);
} else {
    $router->handle();
}
```

Use it:
```bash
# List contacts
curl http://localhost/api/contacts

# Search
curl http://localhost/api/contacts?search=john

# Create
curl -X POST http://localhost/api/contacts \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com"}'

# Get OpenAPI spec
curl http://localhost/api?openapi=1
```

## Complete Example (All Features)

```php
<?php
session_start();
require 'vendor/autoload.php';

// 1. Auth
$rbac = new RoleBasedRBAC($_SESSION['user_id'] ?? null, $_SESSION['role'] ?? 'viewer');
AuthManager::getInstance()->init($rbac);

// 2. RLS
RLS::getInstance()->addRule('orders', 'tenant_id', $_SESSION['tenant_id'] ?? 0);
RLS::getInstance()->addGlobalRule('deleted_at', null, 'IS');

// 3. Audit
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser($_SESSION['user_id'] ?? 'anonymous');

// 4. CRUD UI
$tbl = new ajaxCRUD("Order", "orders", "id");
$tbl->displayAs("customer_name", "Customer");
$tbl->displayAs("total", "Total Amount");
$tbl->modifyFieldWithClass("order_date", "date");
$tbl->addValidationRule("total", "required");
$tbl->addValidationRule("total", "numeric");
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="vendor/ajaxcrud/ajaxcrud/css/default.css">
</head>
<body>
    <h1>Orders</h1>
    <?php $tbl->showTable(); ?>
    
    <h2>Recent Changes</h2>
    <?php
    $recent = auditLog()->query(['table' => 'orders'], 20);
    foreach ($recent as $log) {
        echo "<p>{$log['action']} on order {$log['record_id']} by {$log['user_id']} at {$log['created_at']}</p>";
    }
    ?>
</body>
</html>
```

## Module Independence

All new features are **optional** and **independent**:

```php
// Use only what you need

// Minimal - just CRUD UI
require 'ajaxCRUD.class.php';

// + Auth
require 'Auth.class.php';

// + RLS
require 'RLS.class.php';

// + Audit
require 'AuditLog.class.php';

// + API
require 'API.class.php';
```

## Key Configuration Options

### Auth Roles
```php
// Predefined roles
$rbac = new RoleBasedRBAC($userId, 'admin');   // Full access
$rbac = new RoleBasedRBAC($userId, 'editor');  // Read + Write
$rbac = new RoleBasedRBAC($userId, 'viewer');  // Read only
$rbac = new RoleBasedRBAC($userId, 'guest');   // No access
```

### Custom Permissions
```php
$rbac = new SimpleRBAC($userId, [
    'orders' => [
        'read' => true,
        'write' => function($user, $row) {
            return $row['user_id'] === $user; // Own records only
        },
        'delete' => false
    ]
]);
```

### RLS Rules
```php
// Simple
RLS::getInstance()->addRule('table', 'field', 'value');

// Dynamic
RLS::getInstance()->addRule('table', 'field', function() {
    return $_SESSION['value'];
});

// IN operator
RLS::getInstance()->addRule('table', 'department_id', [1, 2, 3], 'IN');

// Global (all tables)
RLS::getInstance()->addGlobalRule('deleted_at', null, 'IS', ['audit_log']);
```

### API Configuration
```php
$api = new CrudAPI('table', 'id');

// Set searchable fields
$api->setSearchableFields(['name', 'email', 'description']);

// Set sortable fields
$api->setSortableFields(['name', 'created_at', 'updated_at']);

// Hide sensitive fields
$api->setHiddenFields(['password', 'token', 'internal_notes']);

// Only expose specific fields
$api->setAllowedFields(['id', 'name', 'email', 'status']);

// Validation
$api->addValidation('email', 'email');
$api->addValidation('age', 'min', 18);
$api->addValidation('name', 'maxlength', 100);

// Hooks
$api->before('POST', function(&$data) {
    $data['created_at'] = date('Y-m-d H:i:s');
});
```

## Next Steps

- 📖 Read [SUPABASE_FEATURES.md](SUPABASE_FEATURES.md) for detailed feature docs
- 🚀 Try the [examples/demo_supabase_features.php](examples/demo_supabase_features.php) demo
- 🔌 Test the [API demo](examples/api-demo.php)
- 📦 Check out [docker-compose.yml](docker-compose.yml) for instant setup

## Common Use Cases

### Internal Admin Panel
```php
$rbac = new RoleBasedRBAC($_SESSION['user_id'], 'admin');
AuthManager::getInstance()->init($rbac);
AuditLog::getInstance()->enable();
// Full access, everything logged
```

### Multi-Tenant SaaS
```php
RLS::getInstance()->addRule('*', 'tenant_id', $_SESSION['tenant_id']);
$rbac = new RoleBasedRBAC($_SESSION['user_id'], $_SESSION['role']);
AuthManager::getInstance()->init($rbac);
// Perfect isolation between tenants
```

### Public API
```php
$router = new APIRouter();
$router->register('posts', 'posts', 'id', function($api) {
    $api->readOnly(); // GET only
    $api->setSearchableFields(['title', 'content']);
});
$router->handle();
```

## Troubleshooting

**Auth not working?**
- Make sure to call `AuthManager::getInstance()->init($rbac)` before creating tables
- Check `AuthManager::getInstance()->isEnabled()`

**RLS not filtering?**
- Rules must be added before queries execute
- Check `RLS::getInstance()->isEnabled()`
- Debug with `RLS::getInstance()->getRules()`

**Audit not logging?**
- Run `AuditLog::createTable()` first
- Enable with `AuditLog::getInstance()->enable()`
- Set user with `AuditLog::getInstance()->setUser($userId)`

**API returns 404?**
- Check `$router->setBasePath('/api')` matches your URL
- Verify table/primary key names

## Support

- 📧 Email: arts@loudcanvas.com
- 🐛 Issues: https://github.com/metazen11/ajaxCRUD/issues
- 💬 Discussions: https://github.com/metazen11/ajaxCRUD/discussions

---

Made with ❤️ for PHP developers
