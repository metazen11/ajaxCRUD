<?php
/**
 * ajaxCRUD v7.0 - Form Elements Showcase
 *
 * Demonstrates each form element type with actual database-connected
 * CRUD operations. Click any field to edit - changes save automatically.
 */

// Use SQLite for portable testing
$DB_DRIVER = 'sqlite';
$DB_CONFIG = [
    'sqlite' => [
        'path' => __DIR__ . '/demo_elements.sqlite',
    ],
];

require_once(__DIR__ . '/../preheader.php');
require_once(__DIR__ . '/../ajaxCRUD.class.php');

// Initialize demo table with all field types
$db = getDB();

// Check if table exists
$tableExists = q("SELECT name FROM sqlite_master WHERE type='table' AND name='tblFormElements'");

if (empty($tableExists)) {
    $db->exec("CREATE TABLE tblFormElements (
        pkID INTEGER PRIMARY KEY AUTOINCREMENT,
        fldTextInput TEXT,
        fldEmail TEXT,
        fldPhone TEXT,
        fldUrl TEXT,
        fldNumber INTEGER,
        fldDecimal REAL,
        fldDate TEXT,
        fldDateTime TEXT,
        fldTime TEXT,
        fldColor TEXT,
        fldStatus TEXT,
        fldPriority TEXT,
        fldRating INTEGER,
        fldActive INTEGER,
        fldPublished INTEGER,
        fldNotes TEXT,
        fldCreatedAt TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert sample data
    qr("INSERT INTO tblFormElements
        (fldTextInput, fldEmail, fldPhone, fldUrl, fldNumber, fldDecimal, fldDate, fldDateTime, fldTime, fldColor, fldStatus, fldPriority, fldRating, fldActive, fldPublished, fldNotes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'Sample Text',
        'user@example.com',
        '555-123-4567',
        'https://example.com',
        42,
        99.95,
        '2025-06-15',
        '2025-06-15T14:30',
        '14:30',
        '#3498db',
        'active',
        'high',
        75,
        1,
        0,
        'This is a sample note with multiple lines of text.'
    ]);

    qr("INSERT INTO tblFormElements
        (fldTextInput, fldEmail, fldPhone, fldUrl, fldNumber, fldDecimal, fldDate, fldDateTime, fldTime, fldColor, fldStatus, fldPriority, fldRating, fldActive, fldPublished, fldNotes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'Another Example',
        'admin@demo.org',
        '555-987-6543',
        'https://demo.org',
        100,
        249.99,
        '2025-12-25',
        '2025-12-25T09:00',
        '09:00',
        '#e74c3c',
        'pending',
        'medium',
        50,
        1,
        1,
        'Secondary example record.'
    ]);
}

// Create CRUD instance
$tbl = new ajaxCRUD("Form Element", "tblFormElements", "pkID", "../");

// ============================================
// Configuration - Show all element types
// ============================================
$tbl->omitPrimaryKey();
$tbl->disallowEdit("fldCreatedAt");

// Display names
$tbl->displayAs("fldTextInput", "Text Input");
$tbl->displayAs("fldEmail", "Email");
$tbl->displayAs("fldPhone", "Phone");
$tbl->displayAs("fldUrl", "URL");
$tbl->displayAs("fldNumber", "Number");
$tbl->displayAs("fldDecimal", "Price");
$tbl->displayAs("fldDate", "Date");
$tbl->displayAs("fldDateTime", "Date/Time");
$tbl->displayAs("fldTime", "Time");
$tbl->displayAs("fldColor", "Color");
$tbl->displayAs("fldStatus", "Status");
$tbl->displayAs("fldPriority", "Priority");
$tbl->displayAs("fldRating", "Rating");
$tbl->displayAs("fldActive", "Active");
$tbl->displayAs("fldPublished", "Published");
$tbl->displayAs("fldNotes", "Notes");
$tbl->displayAs("fldCreatedAt", "Created");

// HTML5 input types
$tbl->modifyFieldWithClass("fldEmail", "email");
$tbl->modifyFieldWithClass("fldPhone", "tel");
$tbl->modifyFieldWithClass("fldUrl", "url");
$tbl->modifyFieldWithClass("fldNumber", "number");
$tbl->modifyFieldWithClass("fldDecimal", "decimal");
$tbl->modifyFieldWithClass("fldDate", "date");
$tbl->modifyFieldWithClass("fldDateTime", "datetime-local");
$tbl->modifyFieldWithClass("fldTime", "time");
$tbl->modifyFieldWithClass("fldColor", "color");

// Dropdown for status
$tbl->defineAllowableValues("fldStatus", [
    ['active', 'Active'],
    ['pending', 'Pending'],
    ['completed', 'Completed'],
    ['cancelled', 'Cancelled']
]);

// Dropdown for priority
$tbl->defineAllowableValues("fldPriority", [
    ['low', 'Low'],
    ['medium', 'Medium'],
    ['high', 'High'],
    ['critical', 'Critical']
]);

// Range slider for rating (0-100)
$tbl->defineRange("fldRating", 0, 100, 5, true);

// Toggle switches for boolean fields
$tbl->defineToggle("fldActive", "1", "0");
$tbl->defineToggle("fldPublished", "1", "0");

// Textarea for notes
$tbl->setTextareaHeight("fldNotes", 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ajaxCRUD v7.0 - Form Elements Showcase</title>
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="../css/default.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e;
            min-height: 100vh;
            padding: 30px 20px;
            color: #333;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(to right, #2c3e50, #34495e);
            padding: 30px 40px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border-left: 5px solid #9b59b6;
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

        .legend {
            background: #ffffff;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .legend h3 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 13px;
        }

        .legend-item .type-badge {
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .legend-item .type-badge.html5 { background: #e74c3c; }
        .legend-item .type-badge.dropdown { background: #27ae60; }
        .legend-item .type-badge.toggle { background: #9b59b6; }
        .legend-item .type-badge.range { background: #f39c12; }
        .legend-item .type-badge.textarea { background: #1abc9c; }

        .crud-wrapper {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            overflow-x: auto;
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

        /* Make table use full width */
        .crud-wrapper .ajaxCRUD {
            width: 100%;
        }

        /* Enhanced saving indicator */
        .savingAjaxWithBackground {
            background: linear-gradient(to right, #f39c12, #e67e22) !important;
            color: white !important;
            padding: 4px 12px !important;
            border-radius: 4px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            animation: savePulse 0.8s infinite !important;
        }

        @keyframes savePulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Editable cell hover */
        .ajaxCRUD td .editable:hover {
            background-color: #fff3cd !important;
            box-shadow: 0 0 0 2px #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Form Elements Showcase</h1>
            <p>All form element types with AJAX auto-save - Click any field to edit</p>
        </header>

        <div class="legend">
            <h3>Element Types Demonstrated</h3>
            <div class="legend-grid">
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Text Input</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Email</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Phone (tel)</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>URL</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Number</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Decimal/Price</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Date Picker</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>DateTime</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Time</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge html5">HTML5</span>
                    <span>Color Picker</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge dropdown">Dropdown</span>
                    <span>Status</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge dropdown">Dropdown</span>
                    <span>Priority</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge range">Range</span>
                    <span>Rating (0-100)</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge toggle">Toggle</span>
                    <span>Active</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge toggle">Toggle</span>
                    <span>Published</span>
                </div>
                <div class="legend-item">
                    <span class="type-badge textarea">Textarea</span>
                    <span>Notes</span>
                </div>
            </div>
        </div>

        <div class="crud-wrapper">
            <?php $tbl->showTable(); ?>
        </div>

        <footer>
            <p>ajaxCRUD v7.0 - All changes auto-save to SQLite database</p>
        </footer>
    </div>
</body>
</html>
