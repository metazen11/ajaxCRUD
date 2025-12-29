# ajaxCRUD v7.1 - Supabase-Level Features

[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![License: GPL-2.0](https://img.shields.io/badge/License-GPL--2.0-green)](https://opensource.org/licenses/GPL-2.0)
[![Composer](https://img.shields.io/badge/Composer-Ready-orange)](https://packagist.org/)

🚀 **NEW in v7.1**: Auth/RBAC, Row-Level Security, Audit Logs, REST API with OpenAPI!

## One-Command Demo

```bash
docker-compose up -d
```

Then open:
- **Demo App**: http://localhost:8080/examples/
- **phpMyAdmin**: http://localhost:8081
- **API Docs**: http://localhost:8080/api-demo.php

## What's New in 7.1

### 🔐 Authentication & RBAC

Simple yet powerful permission system:

```php
use AuthManager, SimpleRBAC, RoleBasedRBAC;

// Option 1: Simple permissions
$rbac = new SimpleRBAC($_SESSION['user_id'], [
    'tblContacts' => ['read' => true, 'write' => true, 'delete' => false],
    '*' => ['read' => true, 'write' => false, 'delete' => false] // default for all tables
]);

// Option 2: Role-based (admin, editor, viewer, guest)
$rbac = new RoleBasedRBAC($_SESSION['user_id'], 'editor');

// Initialize
AuthManager::getInstance()->init($rbac);

// Row-level checks with callables
$rbac->addTablePermission('orders', [
    'read' => true,
    'write' => function($user, $row) {
        return $row['user_id'] === $user; // Can only edit own orders
    },
    'delete' => false
]);
```

### 🛡️ Row-Level Security (RLS)

Automatic WHERE clauses - perfect for multi-tenant apps:

```php
// Tenant isolation
RLS::getInstance()->addRule('orders', 'tenant_id', $_SESSION['tenant_id']);
RLS::getInstance()->addRule('customers', 'tenant_id', $_SESSION['tenant_id']);

// User-specific data
RLS::getInstance()->addRule('documents', 'user_id', function() {
    return $_SESSION['user_id'] ?? 0;
});

// Soft deletes (global)
RLS::getInstance()->addGlobalRule('deleted_at', null, 'IS', ['audit_log']);

// All SELECT/UPDATE/DELETE queries automatically include these WHERE clauses!
```

### 📝 Audit Log

Track who changed what, when:

```php
// One-time setup
AuditLog::createTable();

// Enable
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser($_SESSION['user_id']);

// That's it! All INSERT/UPDATE/DELETE operations are now logged automatically

// Query audit history
$history = auditLog()->getRecordHistory('tblContacts', '123');
$recent = auditLog()->query(['user_id' => '42', 'action' => 'DELETE'], 50);
```

Audit table stores:
- Who (user_id, IP, user agent)
- What (table, record, action)
- When (timestamp)
- Old/new values (JSON)
- Changed fields

### 🔌 REST API with OpenAPI

Expose your CRUD as JSON endpoints:

```php
// Single endpoint
$api = new CrudAPI('tblContacts', 'pkID');
$api->setSearchableFields(['fldName', 'fldEmail']);
$api->setSortableFields(['fldName', 'fldCreated']);
$api->setHiddenFields(['password', 'internal_notes']);
$api->handle();

// Or use the router
$router = new APIRouter();
$router->setBasePath('/api');

$router->register('contacts', 'tblContacts', 'pkID', function($api) {
    $api->setSearchableFields(['fldName', 'fldEmail']);
    $api->setRequiredFields(['fldName', 'fldEmail']);
    $api->addValidation('fldEmail', 'email');
});

$router->register('products', 'tblProducts', 'pkID');

$router->handle();

// Generate OpenAPI spec
$router->outputOpenAPISpec(['title' => 'My API', 'version' => '1.0']);
```

API Features:
- Full CRUD: GET, POST, PUT, DELETE
- Pagination: `?page=1&limit=50`
- Search: `?search=john`
- Sort: `?sort=name&order=DESC`
- Filter: `?status=active&country=US`
- CORS support
- Validation
- Auth integration
- Audit log integration
- OpenAPI/Swagger spec generation

### 📦 Installation

**Via Composer:**
```bash
composer require ajaxcrud/ajaxcrud
```

**Manual:**
```bash
git clone https://github.com/metazen11/ajaxCRUD.git
cd ajaxCRUD
```

**Docker:**
```bash
docker-compose up -d
```

## Complete Example

```php
<?php
session_start();
require 'vendor/autoload.php'; // or require 'preheader.php';

// 1. Setup database connection (in preheader.php)
$DB_DRIVER = 'mysql';
$DB_CONFIG = ['mysql' => [
    'host' => 'localhost',
    'name' => 'mydb',
    'user' => 'root',
    'pass' => ''
]];

// 2. Setup Auth
$rbac = new RoleBasedRBAC($_SESSION['user_id'] ?? null, $_SESSION['role'] ?? 'guest');
AuthManager::getInstance()->init($rbac);

// 3. Setup RLS (multi-tenant)
RLS::getInstance()->addRule('orders', 'tenant_id', $_SESSION['tenant_id'] ?? 0);
RLS::getInstance()->addGlobalRule('deleted_at', null, 'IS');

// 4. Enable Audit
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser($_SESSION['user_id'] ?? 'anonymous');

// 5. Create CRUD interface
$tbl = new ajaxCRUD("Order", "orders", "id");
$tbl->displayAs("customer_id", "Customer");
$tbl->displayAs("total_amount", "Total");
$tbl->modifyFieldWithClass("order_date", "date");
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
</body>
</html>
```

## API Example

Create `api.php`:

```php
<?php
require 'vendor/autoload.php';

// Setup (same as above)
$rbac = new RoleBasedRBAC($_SESSION['user_id'] ?? null, 'admin');
AuthManager::getInstance()->init($rbac);
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser($_SESSION['user_id'] ?? null);

// Setup API router
$router = new APIRouter();
$router->setBasePath('/api');

$router->register('contacts', 'tblContacts', 'pkID', function($api) {
    $api->setSearchableFields(['fldName', 'fldEmail', 'fldPhone']);
    $api->setHiddenFields(['password']);
    $api->setRequiredFields(['fldName', 'fldEmail']);
    $api->addValidation('fldEmail', 'email');
});

// Handle request
if ($_GET['openapi'] ?? false) {
    $router->outputOpenAPISpec(['title' => 'Contacts API', 'version' => '1.0']);
} else {
    $router->handle();
}
```

Usage:
```bash
# List contacts
curl http://localhost/api/contacts?page=1&limit=10

# Search
curl http://localhost/api/contacts?search=john

# Get single contact
curl http://localhost/api/contacts/123

# Create contact
curl -X POST http://localhost/api/contacts \
  -H "Content-Type: application/json" \
  -d '{"fldName":"John Doe","fldEmail":"john@example.com"}'

# Update contact
curl -X PUT http://localhost/api/contacts/123 \
  -H "Content-Type: application/json" \
  -d '{"fldPhone":"555-1234"}'

# Delete contact
curl -X DELETE http://localhost/api/contacts/123

# Get OpenAPI spec
curl http://localhost/api?openapi=1
```

## Original Features (v7.0)

All existing features remain:
- ✅ Inline editing with AJAX auto-save
- ✅ HTML5 inputs (date, color, email, tel, url, range)
- ✅ Toggle switches, dropdowns, multi-select
- ✅ Rich text editor, autocomplete
- ✅ File uploads
- ✅ Validation (client + server)
- ✅ CSRF protection
- ✅ Pagination, sorting, filtering
- ✅ Multi-database (MySQL, PostgreSQL, SQLite)
- ✅ DynamicTableEditor (zero-config scaffolding)

See [README.md](README.md) for full original documentation.

## Architecture

```
ajaxCRUD.class.php    → Core CRUD UI
Auth.class.php        → RBAC system
RLS.class.php         → Row-level security
AuditLog.class.php    → Audit trail
API.class.php         → REST API + OpenAPI
preheader.php         → Database + helpers
```

All modules are **optional** and **independent**. Use only what you need:

```php
// Minimal - just CRUD UI
require 'ajaxCRUD.class.php';

// Add auth
require 'Auth.class.php';
AuthManager::getInstance()->init(new SimpleRBAC(...));

// Add RLS
require 'RLS.class.php';
RLS::getInstance()->addRule(...);

// Add audit
require 'AuditLog.class.php';
AuditLog::getInstance()->enable();

// Expose as API
require 'API.class.php';
$api = new CrudAPI('table', 'pk');
$api->handle();
```

## Configuration Examples

### Custom Auth Provider

Implement `AuthInterface` for custom logic:

```php
class MyAuth implements AuthInterface {
    public function can_read($user, string $table, array $row = []): bool {
        // Custom logic - check JWT, query permissions DB, etc.
        return $this->checkPermission($user, $table, 'read');
    }
    
    public function can_write($user, string $table, array $row = []): bool {
        return $this->checkPermission($user, $table, 'write');
    }
    
    public function can_delete($user, string $table, array $row = []): bool {
        return $this->checkPermission($user, $table, 'delete');
    }
    
    public function getCurrentUser() {
        return $this->getUserFromJWT();
    }
}

AuthManager::getInstance()->init(new MyAuth());
```

### Complex RLS Rules

```php
$rls = RLS::getInstance();

// Multi-tenant with role bypass
$rls->addRule('data', 'tenant_id', function() {
    // Admins see all tenants
    if ($_SESSION['role'] === 'admin') {
        return null; // Skip this rule
    }
    return $_SESSION['tenant_id'];
});

// Department-based access
$rls->addRule('employees', 'department_id', function() {
    $depts = $_SESSION['accessible_departments'] ?? [];
    return count($depts) > 0 ? $depts : [0]; // Empty array = no access
}, 'IN');

// Published content only (except for editors)
if ($_SESSION['role'] !== 'editor') {
    $rls->addGlobalRule('status', 'published', '=', ['users', 'audit_log']);
}
```

### API Hooks

```php
$api = new CrudAPI('orders', 'id');

// Before create - set defaults
$api->before('POST', function(&$data) {
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['user_id'] = $_SESSION['user_id'];
});

// After create - send notification
$api->after('POST', function($record) {
    sendEmail("New order: " . $record['id']);
});

// Before delete - check constraints
$api->before('DELETE', function($oldRecord) {
    if ($oldRecord['status'] === 'completed') {
        throw new Exception("Cannot delete completed orders");
    }
});

$api->handle();
```

## Performance Notes

- **Auth checks**: Minimal overhead (~0.1ms per operation)
- **RLS**: Compiled once per request, adds to WHERE clause
- **Audit log**: Async-ready (wrap in queue for high-traffic)
- **API**: Supports ETags, pagination, field selection

## Python Version

The Python equivalent would share the same API contract (OpenAPI spec):

```python
# Python FastAPI implementation (coming soon)
from ajaxcrud import CrudAPI

api = CrudAPI('contacts', 'id')
api.set_searchable_fields(['name', 'email'])

# Same endpoints, same contract!
```

## Comparison to Supabase

| Feature | Supabase | ajaxCRUD v7.1 | Notes |
|---------|----------|---------------|-------|
| Auth/RBAC | ✅ | ✅ | Supabase=JWT, ajaxCRUD=flexible |
| Row-Level Security | ✅ | ✅ | Supabase=Postgres RLS, ajaxCRUD=PHP layer |
| Audit Log | ❌ | ✅ | Supabase needs extension |
| REST API | ✅ | ✅ | Both auto-generate from schema |
| OpenAPI | ✅ | ✅ | |
| Realtime | ✅ | ❌ | Supabase advantage |
| Hosted | ✅ | ❌ | Self-host ajaxCRUD |
| Inline Editing UI | ❌ | ✅ | ajaxCRUD advantage |
| Learning Curve | Medium | Low | |

**Use ajaxCRUD when:**
- Building internal tools / admin panels
- Need inline editing UI out-of-box
- Want self-hosted PHP solution
- Simple auth requirements
- Legacy PHP app modernization

**Use Supabase when:**
- Building public-facing apps
- Need realtime features
- Want managed infrastructure
- Complex auth (OAuth, MFA)

## Contributing

PRs welcome! Focus areas:
- [ ] WebSocket support for realtime
- [ ] GraphQL endpoint
- [ ] UI themes
- [ ] More auth providers (OAuth, SAML)
- [ ] Query builder UI
- [ ] Bulk operations API

## License

GPL-2.0-or-later

## Support

- 📖 [Full Documentation](DOCUMENTATION.md)
- 💬 [Discussions](https://github.com/metazen11/ajaxCRUD/discussions)
- 🐛 [Issues](https://github.com/metazen11/ajaxCRUD/issues)
- 📧 arts@loudcanvas.com

---

**Made with ❤️ for PHP developers who want Supabase-level features without the complexity**
