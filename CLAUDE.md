# ajaxCRUD Project Context

## Overview
ajaxCRUD is a PHP library for creating AJAX-based CRUD (Create, Read, Update, Delete) form elements with automatic asynchronous saving to database tables via parameter mapping.

## Workflow
- **Session Start**: Read root `*.md` files and `todo.json`
- **Session End**: Update `session-handoff.md` (gitignored) for continuity
- **Task Tracking**: Active tasks in `todo.json`, completed in `done.json`
- **Version Control**: Always use git; initialize if no repo exists

## Key Principles
- **No attribution in commits** - No "Generated with" or "Co-Authored-By" lines
- **No proprietary information** - Keep competitive advantages confidential
- Each form element should auto-save when changed (no submit button needed)
- Database abstraction via PDO - supports MySQL, SQLite, PostgreSQL
- Security first - all queries use prepared statements

## Coding Standards (Senior/Lead Level)
- **DRY (Don't Repeat Yourself)** - Extract common patterns into reusable methods/classes
- **OOP & Inheritance** - Use classes with proper inheritance hierarchy
- **SOLID principles** - Single responsibility, Open/closed, Liskov substitution, Interface segregation, Dependency inversion
- **Elegant simplicity** - Simplest thorough solution; no over-engineering
- **Documentation** - PHPDoc/JSDoc for all public methods, inline comments for complex logic
- **Self-review** - Try to break your own code before submission
- **Leverage frameworks** - Use tested modules and proven solutions

## Testing Requirements
- Automated testing via Playwright + database verification scripts
- Tests must actually execute and verify with real data checks
- Screenshots captured on all test runs (`tests/test-results/`)
- Run tests before any commit

## Security Checklist
- [ ] Input validation (whitelist approach)
- [ ] Output encoding (htmlspecialchars for HTML, json_encode for JSON)
- [ ] SQL injection prevention (prepared statements only)
- [ ] XSS prevention (escape all user-generated content)
- [x] CSRF protection (token validation on state-changing requests)
- [ ] Secure file operations (validate paths, restrict extensions)
- [ ] Error messages don't leak system information

## Web Security & Folder Structure
- Sensitive files (configs, classes, includes) outside web root
- Only public-facing files in `/public_html` or equivalent
- Use `.htaccess` to restrict access to sensitive directories
- Default deny, explicit allow

## Architecture

### Core Files
- `preheader.php` - Database connection, query helpers, CSRF protection
- `ajaxCRUD.class.php` - Main class with form element generators
- `javascript_functions.js` - Client-side AJAX handlers (ES6+ with fetch API)
- `validation.js` - Input masking and validation helpers
- `css/default.css` - Styling for form elements

### Database Configuration
Set `$DB_DRIVER` before including preheader.php:
```php
$DB_DRIVER = 'sqlite'; // or 'mysql', 'pgsql'
```

### Form Element Methods
- `makeAjaxEditor()` - Text, textarea, number, decimal, date, datetime, time, email, url, tel, color inputs
- `makeAjaxDropdown()` - Select dropdowns
- `makeAjaxCheckbox()` - Checkbox inputs (supports toggle switch via defineToggle)
- `makeAjaxRadio()` - Radio button groups
- `makeAjaxRange()` - Range slider with live value preview
- `makeAjaxMultiSelect()` - Multiple selection dropdown
- `makeAjaxAutocomplete()` - Autocomplete with datalist
- `makeAjaxPassword()` - Masked password field
- `makeAjaxRichText()` - WYSIWYG editor with toolbar
- `showUploadForm()` - File uploads

### Configuration Methods
- `defineAllowableValues($field, $values)` - Predefined dropdown values
- `defineRelationship($fk, $table, $pk, $field)` - Foreign key dropdowns
- `defineCheckbox($field, $on, $off)` - Checkbox on/off values
- `defineToggle($field, $on, $off)` - Modern toggle switch
- `defineRadioButtons($field, $options, $inline)` - Radio button options
- `defineRange($field, $min, $max, $step, $show_value)` - Range slider config
- `defineMultiSelect($field, $options, $separator)` - Multi-select config
- `defineAutocomplete($field, $table, $display_field, $value_field)` - Autocomplete config
- `setRichText($field, $toolbar)` - Rich text editor (basic/full toolbar)
- `setPassword($field, $confirm)` - Password field config
- `setTextareaHeight($field, $height)` - Textarea sizing
- `setFileUpload($field, $folder)` - File upload destination
- `modifyFieldWithClass($field, $class)` - Add CSS class (datepicker, phone, zip)
- `disallowEdit($field)` - Make field read-only
- `omitField($field)` - Hide field from display
- `omitPrimaryKey()` - Hide primary key column

## Code Style
- PHP 8.1+ with type hints
- ES6+ JavaScript (const/let, arrow functions, async/await, fetch)
- Prepared statements for all SQL queries
- Escape identifiers with backticks for table/column names

## Testing

### Quick SQLite Testing
```php
$DB_DRIVER = 'sqlite';
$DB_CONFIG = ['sqlite' => ['path' => __DIR__ . '/test.sqlite']];
require_once('preheader.php');
```

### Playwright E2E Tests
Located in `tests/` directory:
```bash
cd tests
npm install
npx playwright install
npm test                  # Run all tests
npm run test:security     # Security tests only
npm run test:crud         # CRUD functionality
npm run test:validation   # Validation tests
```

Tests include:
- CRUD operations (create, read, update, delete)
- Security (SQL injection, XSS, CSRF)
- Client-side validation (HTML5 types, masks)
- Server-side validation (data types, sanitization)

Screenshots are automatically captured on test failure and can be found in `tests/test-results/`

## Security Considerations
- All user input sanitized via prepared statements
- Table/column names validated with escapeIdentifier()
- CSRF tokens available via getCsrfToken(), csrfField()
- File paths validated before operations
