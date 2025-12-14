# ajaxCRUD v7.0

A lightweight PHP library for creating inline-editable database tables with AJAX auto-save. Click any cell to edit - changes save automatically without page refresh.

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue)
![License: GPL-2.0](https://img.shields.io/badge/License-GPL--2.0-green)

## Features

- **Inline Editing** - Click any cell to edit, auto-saves on blur/enter
- **HTML5 Form Elements** - Date pickers, color pickers, email/tel/url inputs, range sliders
- **Toggle Switches** - Modern on/off toggles for boolean fields
- **Dropdown Menus** - From arrays or database relationships
- **Multi-Database Support** - MySQL, PostgreSQL, SQLite via PDO
- **Pagination** - Built-in paging with configurable rows per page
- **Sorting & Filtering** - Column sorting and search filters
- **Client + Server Validation** - Dual-layer validation with real-time feedback
- **CSRF Protection** - Token validation on all state-changing operations
- **DynamicTableEditor** - Auto-scaffold CRUD interfaces from any table

## Requirements

- PHP 8.1 or higher
- PDO extension
- MySQL, PostgreSQL, or SQLite

## Quick Start

```php
<?php
// Database configuration
$DB_DRIVER = 'mysql';  // or 'sqlite', 'pgsql'
$DB_CONFIG = [
    'mysql' => [
        'host' => 'localhost',
        'name' => 'mydb',
        'user' => 'root',
        'pass' => ''
    ]
];

require_once('preheader.php');
require_once('ajaxCRUD.class.php');

// Create CRUD instance
$tbl = new ajaxCRUD("Contact", "tblContacts", "pkID");

// Configure fields
$tbl->displayAs("fldEmail", "Email Address");
$tbl->modifyFieldWithClass("fldEmail", "email");
$tbl->defineToggle("fldActive", "1", "0");

// Output in HTML
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="css/default.css">
</head>
<body>
    <?php $tbl->showTable(); ?>
</body>
</html>
```

## DynamicTableEditor (Zero-Config)

Scaffold a complete CRUD interface with just 2 lines:

```php
$editor = new DynamicTableEditor('tblContacts');
$editor->render();
```

With configuration:

```php
$editor = new DynamicTableEditor('tblContacts', [
    'rows_per_page' => 10,
    'title' => 'Contact Management',
    'readonly_fields' => ['fldCreatedAt'],
    'dropdowns' => [
        'fldStatus' => [['active', 'Active'], ['pending', 'Pending']]
    ],
    'ranges' => [
        'fldRating' => [0, 100, 5, true]  // min, max, step, show value
    ]
]);
$editor->render();
```

## Form Element Types

| Method | Input Type | Example |
|--------|-----------|---------|
| `modifyFieldWithClass($field, "email")` | Email input | `user@example.com` |
| `modifyFieldWithClass($field, "tel")` | Phone input | `555-123-4567` |
| `modifyFieldWithClass($field, "url")` | URL input | `https://...` |
| `modifyFieldWithClass($field, "date")` | Date picker | `2025-01-15` |
| `modifyFieldWithClass($field, "datetime-local")` | DateTime picker | `2025-01-15T14:30` |
| `modifyFieldWithClass($field, "time")` | Time picker | `14:30` |
| `modifyFieldWithClass($field, "color")` | Color picker | `#3498db` |
| `modifyFieldWithClass($field, "number")` | Number input | `42` |
| `modifyFieldWithClass($field, "decimal")` | Decimal input | `99.95` |
| `defineToggle($field, "on", "off")` | Toggle switch | On/Off |
| `defineRange($field, 0, 100, 5, true)` | Range slider | 0-100 |
| `defineAllowableValues($field, [...])` | Dropdown | Select menu |
| `setTextareaHeight($field, 3)` | Textarea | Multi-line text |

## Security

ajaxCRUD v7.0 includes multiple security layers:

- **SQL Injection Prevention** - All queries use PDO prepared statements
- **XSS Protection** - Output escaped with `htmlspecialchars()`
- **CSRF Protection** - Token validation on update/delete operations
- **Input Validation** - Client-side + server-side validation
- **Identifier Sanitization** - Table/field names validated against injection

## Configuration Methods

```php
// Display customization
$tbl->displayAs("fldName", "Display Name");
$tbl->omitPrimaryKey();                    // Hide PK column
$tbl->omitField("fldInternal");            // Hide specific field
$tbl->disallowEdit("fldCreatedAt");        // Read-only field
$tbl->disallowDelete();                    // Disable row deletion

// Relationships
$tbl->defineRelationship("fldCategoryID", "tblCategories", "pkCatID", "fldCatName");

// Validation
$tbl->defineValidation("fldEmail", ['required' => true, 'type' => 'email']);

// Callbacks
$tbl->onAddExecuteCallBackFunction("myCallbackFunction");
```

## File Structure

```
ajaxCRUD/
├── ajaxCRUD.class.php    # Main CRUD class + DynamicTableEditor
├── preheader.php         # Database connection, query helpers, CSRF
├── javascript_functions.js # AJAX handlers, validation
├── css/default.css       # Default styling
└── examples/
    ├── demo_crud.php           # Basic CRUD example
    ├── demo_all_elements.php   # All form element types
    └── demo_dynamic_editor.php # DynamicTableEditor demo
```

## Database Setup

### MySQL
```php
$DB_DRIVER = 'mysql';
$DB_CONFIG = ['mysql' => [
    'host' => 'localhost',
    'name' => 'database',
    'user' => 'username',
    'pass' => 'password'
]];
```

### SQLite
```php
$DB_DRIVER = 'sqlite';
$DB_CONFIG = ['sqlite' => [
    'path' => __DIR__ . '/data.sqlite'
]];
```

### PostgreSQL
```php
$DB_DRIVER = 'pgsql';
$DB_CONFIG = ['pgsql' => [
    'host' => 'localhost',
    'name' => 'database',
    'user' => 'username',
    'pass' => 'password'
]];
```

## Browser Support

Modern browsers with ES6+ support (Chrome, Firefox, Safari, Edge).

## License

GNU General Public License v2.0

## History

- **v7.0** (2025) - PHP 8.1+ modernization, PDO, HTML5 inputs, security hardening
- **v6.2** (2011) - Original release by Loud Canvas Media

## Contributing

Issues and pull requests welcome at [github.com/yourusername/ajaxCRUD](https://github.com/yourusername/ajaxCRUD)
