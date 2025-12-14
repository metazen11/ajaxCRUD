<?php
/**
 * DynamicTableEditor Demo
 *
 * Shows how to quickly scaffold a table editor with minimal code.
 * The DynamicTableEditor auto-detects table structure and applies
 * sensible defaults while allowing overrides.
 */

// Use SQLite for portable testing
$DB_DRIVER = 'sqlite';
$DB_CONFIG = [
    'sqlite' => [
        'path' => __DIR__ . '/demo_crud.sqlite',
    ],
];

require_once(__DIR__ . '/../preheader.php');
require_once(__DIR__ . '/../ajaxCRUD.class.php');

// Ensure demo table exists
$db = getDB();
$tableExists = q("SELECT name FROM sqlite_master WHERE type='table' AND name='tblContacts'");
if (empty($tableExists)) {
    $db->exec("CREATE TABLE tblContacts (
        pkID INTEGER PRIMARY KEY AUTOINCREMENT,
        fldName TEXT NOT NULL,
        fldEmail TEXT,
        fldPhone TEXT,
        fldStatus TEXT DEFAULT 'active',
        fldPriority TEXT DEFAULT 'medium',
        fldRating INTEGER DEFAULT 50,
        fldActive INTEGER DEFAULT 1,
        fldNotes TEXT,
        fldCreatedAt TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $samples = [
        ['Alice Johnson', 'alice@example.com', '555-0101', 'active', 'high', 85, 1, 'VIP customer'],
        ['Bob Smith', 'bob@test.org', '555-0102', 'pending', 'medium', 60, 1, 'Awaiting callback'],
        ['Carol White', 'carol@demo.net', '555-0103', 'completed', 'low', 95, 0, 'Project finished'],
        ['David Brown', 'david@sample.io', '555-0104', 'active', 'high', 70, 1, 'New lead'],
        ['Eva Martinez', 'eva@example.com', '555-0105', 'pending', 'medium', 45, 1, 'Follow up needed'],
    ];

    foreach ($samples as $row) {
        qr("INSERT INTO tblContacts (fldName, fldEmail, fldPhone, fldStatus, fldPriority, fldRating, fldActive, fldNotes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $row);
    }
}

// ============================================
// SIMPLE USAGE - Just pass the table name!
// ============================================
// $editor = new DynamicTableEditor('tblContacts');
// $editor->render();

// ============================================
// ADVANCED USAGE - With configuration
// ============================================
$editor = new DynamicTableEditor('tblContacts', [
    'rows_per_page' => 5,                    // Paging: 5 rows per page
    'title' => 'Contact Management',          // Custom title
    'ajax_root' => '../',                     // Path to ajaxCRUD root
    'readonly_fields' => ['fldCreatedAt'],    // Can't edit these
    'dropdowns' => [                          // Override with dropdowns
        'fldStatus' => [
            ['active', 'Active'],
            ['pending', 'Pending'],
            ['completed', 'Completed'],
            ['cancelled', 'Cancelled'],
        ],
        'fldPriority' => [
            ['low', 'Low'],
            ['medium', 'Medium'],
            ['high', 'High'],
            ['critical', 'Critical'],
        ],
    ],
    'ranges' => [
        'fldRating' => [0, 100, 5, true],     // Range slider: min, max, step, show value
    ],
    // Note: fldActive auto-detected as toggle, fldEmail auto-detected as email
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamicTableEditor Demo</title>
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="../css/default.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e;
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(to right, #2c3e50, #34495e);
            padding: 30px 40px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border-left: 5px solid #e74c3c;
        }

        header h1 {
            color: #ecf0f1;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        header p {
            color: #95a5a6;
            font-size: 15px;
        }

        .code-example {
            background: #2c3e50;
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .code-example h3 {
            background: #1a252f;
            color: #ecf0f1;
            padding: 15px 20px;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 1px solid #34495e;
        }

        .code-example pre {
            padding: 20px;
            color: #ecf0f1;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
            line-height: 1.5;
            overflow-x: auto;
        }

        .code-example .comment { color: #7f8c8d; }
        .code-example .keyword { color: #e74c3c; }
        .code-example .string { color: #2ecc71; }
        .code-example .variable { color: #3498db; }

        .crud-wrapper {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            overflow-x: auto;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .feature {
            background: #ffffff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .feature h4 {
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .feature p {
            color: #7f8c8d;
            font-size: 12px;
        }

        footer {
            text-align: center;
            padding: 25px 20px;
            margin-top: 20px;
        }

        footer p {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>DynamicTableEditor</h1>
            <p>Scaffold a complete table editor with just one line of code</p>
        </header>

        <div class="features">
            <div class="feature">
                <h4>Auto-Detection</h4>
                <p>Automatically detects primary key, field types, and applies sensible defaults</p>
            </div>
            <div class="feature">
                <h4>Smart Field Types</h4>
                <p>Email, phone, URL fields auto-detected. Boolean fields become toggles</p>
            </div>
            <div class="feature">
                <h4>Configurable Paging</h4>
                <p>Built-in pagination with customizable rows per page (default: 10)</p>
            </div>
            <div class="feature">
                <h4>Override Anything</h4>
                <p>Full control via options array - dropdowns, ranges, readonly fields</p>
            </div>
        </div>

        <div class="code-example">
            <h3>Simple Usage (2 lines)</h3>
            <pre><span class="variable">$editor</span> = <span class="keyword">new</span> DynamicTableEditor(<span class="string">'tblContacts'</span>);
<span class="variable">$editor</span>->render();</pre>
        </div>

        <div class="code-example">
            <h3>With Configuration</h3>
            <pre><span class="variable">$editor</span> = <span class="keyword">new</span> DynamicTableEditor(<span class="string">'tblContacts'</span>, [
    <span class="string">'rows_per_page'</span> => <span class="string">5</span>,
    <span class="string">'title'</span> => <span class="string">'Contact Management'</span>,
    <span class="string">'readonly_fields'</span> => [<span class="string">'fldCreatedAt'</span>],
    <span class="string">'dropdowns'</span> => [
        <span class="string">'fldStatus'</span> => [[<span class="string">'active'</span>, <span class="string">'Active'</span>], [<span class="string">'pending'</span>, <span class="string">'Pending'</span>]],
    ],
    <span class="string">'ranges'</span> => [
        <span class="string">'fldRating'</span> => [<span class="string">0</span>, <span class="string">100</span>, <span class="string">5</span>, <span class="string">true</span>],
    ],
]);
<span class="variable">$editor</span>->render();</pre>
        </div>

        <div class="crud-wrapper">
            <?php $editor->render(); ?>
        </div>

        <footer>
            <p>DynamicTableEditor - Part of ajaxCRUD v7.0 | Paging: 5 rows per page</p>
        </footer>
    </div>
</body>
</html>
