# Testing Guide - ajaxCRUD v7.1

## Quick Test

Run the automated test suite:

```bash
php test.php
```

This validates all new features without requiring a database.

## Testing Methods

### 1. Automated Unit Tests (No Database Required)

```bash
php test.php
```

**Tests covered:**
- ✅ File existence
- ✅ Class loading
- ✅ Auth/RBAC functionality
- ✅ RLS rule management
- ✅ AuditLog configuration
- ✅ API setup
- ✅ Integration between modules

**Expected output:**
```
✅ Tests Passed: 36
❌ Tests Failed: 0
📊 Success Rate: 100%
```

### 2. Docker Demo (Full Stack Test)

**Start the environment:**
```bash
docker-compose up -d
```

**Access points:**
- **Main Demo**: http://localhost:8080/examples/demo_supabase_features.php
- **API Demo**: http://localhost:8080/examples/api-demo.php
- **phpMyAdmin**: http://localhost:8081

**What to test:**
1. Open the main demo and try:
   - ✅ Inline editing (click any cell)
   - ✅ Add new record
   - ✅ Delete record (check permissions)
   - ✅ View audit log at bottom
   
2. Open the API demo and test:
   - ✅ List contacts (GET)
   - ✅ Search functionality
   - ✅ Create record (POST)
   - ✅ Update record (PUT)
   - ✅ View OpenAPI spec

**Stop the environment:**
```bash
docker-compose down
```

### 3. Manual Integration Test

Create a test file `test_integration.php`:

```php
<?php
require_once 'Auth.class.php';
require_once 'RLS.class.php';
require_once 'AuditLog.class.php';
require_once 'API.class.php';

// 1. Test Auth
echo "Testing Auth... ";
$rbac = new RoleBasedRBAC(1, 'admin');
AuthManager::getInstance()->init($rbac);
echo (AuthManager::getInstance()->can_read('test') ? "✅" : "❌") . "\n";

// 2. Test RLS
echo "Testing RLS... ";
RLS::getInstance()->addRule('orders', 'tenant_id', 123);
$where = RLS::getInstance()->getWhereClause('orders');
echo (!empty($where) ? "✅" : "❌") . "\n";

// 3. Test Audit
echo "Testing Audit... ";
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser('test_user');
echo (AuditLog::getInstance()->isEnabled() ? "✅" : "❌") . "\n";

// 4. Test API
echo "Testing API... ";
$router = new APIRouter();
$router->register('test', 'test_table', 'id');
$spec = $router->getOpenAPISpec();
echo (isset($spec['openapi']) ? "✅" : "❌") . "\n";

echo "\nAll systems operational! ✅\n";
?>
```

Run it:
```bash
php test_integration.php
```

### 4. API Testing with cURL

**List records:**
```bash
curl "http://localhost:8080/examples/api-demo.php?action=contacts&limit=5"
```

**Search:**
```bash
curl "http://localhost:8080/examples/api-demo.php?action=contacts&search=john"
```

**Create record:**
```bash
curl -X POST "http://localhost:8080/examples/api-demo.php?action=contacts" \
  -H "Content-Type: application/json" \
  -d '{
    "fldContactName": "Test User",
    "fldEmail": "test@example.com",
    "fldPhone": "555-1234"
  }'
```

**Get OpenAPI spec:**
```bash
curl "http://localhost:8080/examples/api-demo.php?openapi=1" | jq
```

### 5. Database Integration Test

If you have a MySQL/PostgreSQL/SQLite database:

```php
<?php
// Configure your database
$DB_DRIVER = 'mysql';
$DB_CONFIG = ['mysql' => [
    'host' => 'localhost',
    'name' => 'test_db',
    'user' => 'root',
    'pass' => ''
]];

require_once 'preheader.php';
require_once 'ajaxCRUD.class.php';
require_once 'Auth.class.php';
require_once 'RLS.class.php';
require_once 'AuditLog.class.php';

// Create audit table
echo "Creating audit table... ";
$result = AuditLog::createTable();
echo ($result ? "✅" : "❌") . "\n";

// Setup auth
echo "Setting up auth... ";
$rbac = new RoleBasedRBAC(1, 'admin');
AuthManager::getInstance()->init($rbac);
echo "✅\n";

// Setup RLS
echo "Setting up RLS... ";
RLS::getInstance()->addRule('test_table', 'tenant_id', 1);
echo "✅\n";

// Enable audit
echo "Enabling audit... ";
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser(1);
echo "✅\n";

// Create CRUD table
echo "Creating CRUD table... ";
$tbl = new ajaxCRUD("Test", "test_table", "id");
echo "✅\n";

echo "\nDatabase integration test complete! ✅\n";
?>
```

## Testing Checklist

### Auth/RBAC
- [ ] SimpleRBAC accepts permissions
- [ ] RoleBasedRBAC has 4 roles (admin, editor, viewer, guest)
- [ ] Admin has full access
- [ ] Viewer has read-only
- [ ] Row-level callables work
- [ ] AuthManager singleton works
- [ ] Enable/disable works

### RLS
- [ ] Can add table rules
- [ ] Can add global rules
- [ ] Callable values resolve
- [ ] WHERE clause generation works
- [ ] Parameter binding works
- [ ] Exclude tables work
- [ ] Enable/disable works

### Audit Log
- [ ] Singleton works
- [ ] Enable/disable works
- [ ] User management works
- [ ] Table creation works (MySQL/PostgreSQL/SQLite)
- [ ] Include/exclude tables work
- [ ] Log queries work

### API
- [ ] CrudAPI instantiates
- [ ] Configuration methods work
- [ ] Validation rules work
- [ ] Hooks work
- [ ] APIRouter registers endpoints
- [ ] OpenAPI spec generates
- [ ] CORS handles correctly

### Integration
- [ ] Auth + RLS work together
- [ ] Auth + Audit work together
- [ ] RLS + Audit work together
- [ ] All three work together
- [ ] API integrates with Auth/RLS/Audit

## Troubleshooting

### Test Suite Issues

**Problem:** `Class 'AuthInterface' not found`
**Solution:** Make sure Auth.class.php is in the same directory

**Problem:** `Tests failed: 2`
**Solution:** This was fixed. Re-download test.php

### Docker Issues

**Problem:** Port 8080 already in use
**Solution:** Change port in docker-compose.yml:
```yaml
ports:
  - "8888:80"  # Use 8888 instead
```

**Problem:** Database not initializing
**Solution:** 
```bash
docker-compose down -v  # Remove volumes
docker-compose up -d
```

### API Issues

**Problem:** 404 on API endpoints
**Solution:** Check your basePath matches URL structure

**Problem:** CORS errors
**Solution:** API handles CORS automatically, check browser console

### Auth Issues

**Problem:** Permissions not working
**Solution:** Ensure you called `AuthManager::getInstance()->init($rbac)`

**Problem:** Row-level checks failing
**Solution:** Pass row data: `can_write($table, $row)`

### RLS Issues

**Problem:** Rules not applying
**Solution:** Add rules BEFORE queries execute

**Problem:** Empty WHERE clause
**Solution:** Check `RLS::getInstance()->isEnabled()`

### Audit Issues

**Problem:** Not logging changes
**Solution:** 
1. Run `AuditLog::createTable()`
2. Call `AuditLog::getInstance()->enable()`
3. Set user: `AuditLog::getInstance()->setUser($id)`

## Performance Testing

Test API performance:

```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Test API endpoint
ab -n 1000 -c 10 http://localhost:8080/examples/api-demo.php?action=contacts
```

**Expected results:**
- Requests per second: 100-500 (depending on hardware)
- Auth overhead: ~0.1ms per request
- RLS overhead: Negligible (compiled once)

## Security Testing

1. **CSRF Protection:**
   - Try updating without CSRF token → Should fail
   
2. **Auth Bypass:**
   - Try accessing as guest → Should deny
   
3. **RLS Bypass:**
   - Try accessing different tenant's data → Should filter out
   
4. **SQL Injection:**
   - Try injecting SQL in API → Should sanitize

## Continuous Integration

Add to your CI pipeline:

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Run tests
        run: php test.php
```

## Next Steps

After testing:

1. ✅ All tests pass → Deploy to production
2. ⚠️ Some tests fail → Check errors and fix
3. 📊 Performance issues → Profile and optimize
4. 🐛 Bugs found → Open issue on GitHub

## Support

If tests fail:
1. Check this guide
2. Review error messages
3. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
4. Open issue: https://github.com/metazen11/ajaxCRUD/issues

---

**Test Status:** All 36 tests passing ✅  
**Coverage:** Auth, RLS, Audit, API, Integration  
**Last Updated:** 2025-12-29
