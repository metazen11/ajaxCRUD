<?php
/**
 * ajaxCRUD v7.0 - Functional CRUD Demo
 *
 * This demo shows actual CRUD operations with AJAX auto-save.
 * Click any cell to edit, changes save automatically to the database.
 */

// Use SQLite for portable testing
$DB_DRIVER = 'sqlite';
$DB_CONFIG = [
    'sqlite' => [
        'path' => __DIR__ . '/demo_crud.sqlite',
    ],
];

require_once(__DIR__ . '/../preheader.php');

// Initialize demo table (only if it doesn't exist or is empty)
$db = getDB();

// Check if table exists
$tableExists = q("SELECT name FROM sqlite_master WHERE type='table' AND name='tblContacts'");

if (empty($tableExists)) {
    // Create the table
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

    // Insert sample data
    $samples = [
        ['Alice Johnson', 'alice@example.com', '555-0101', 'active', 'high', 85, 1, 'VIP customer'],
        ['Bob Smith', 'bob@test.org', '555-0102', 'pending', 'medium', 60, 1, 'Awaiting callback'],
        ['Carol White', 'carol@demo.net', '555-0103', 'completed', 'low', 95, 0, 'Project finished'],
    ];

    foreach ($samples as $row) {
        qr("INSERT INTO tblContacts (fldName, fldEmail, fldPhone, fldStatus, fldPriority, fldRating, fldActive, fldNotes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $row);
    }
}

require_once(__DIR__ . '/../ajaxCRUD.class.php');

// Create CRUD instance
$tbl = new ajaxCRUD("Contact", "tblContacts", "pkID", "../");

// ============================================
// Configuration
// ============================================
$tbl->omitPrimaryKey();
$tbl->disallowEdit("fldCreatedAt");

// Display names
$tbl->displayAs("fldName", "Name");
$tbl->displayAs("fldEmail", "Email");
$tbl->displayAs("fldPhone", "Phone");
$tbl->displayAs("fldStatus", "Status");
$tbl->displayAs("fldPriority", "Priority");
$tbl->displayAs("fldRating", "Rating");
$tbl->displayAs("fldActive", "Active");
$tbl->displayAs("fldNotes", "Notes");
$tbl->displayAs("fldCreatedAt", "Created");

// Form element types
$tbl->modifyFieldWithClass("fldEmail", "email");
$tbl->modifyFieldWithClass("fldPhone", "tel");

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

// Range slider for rating
$tbl->defineRange("fldRating", 0, 100, 5, true);

// Toggle for active
$tbl->defineToggle("fldActive", "1", "0");

// Set text area height for notes
$tbl->setTextareaHeight("fldNotes", 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ajaxCRUD v7.0 - Functional CRUD Demo</title>
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
            border-left: 5px solid #27ae60;
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

        .instructions {
            background: #ffffff;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .instructions h3 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instructions ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 10px;
        }

        .instructions li {
            font-size: 13px;
            color: #555;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }

        .instructions li strong {
            color: #2c3e50;
        }

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

        /* Enhanced saving indicator */
        .savingAjaxWithBackground {
            background: linear-gradient(to right, #f39c12, #e67e22) !important;
            color: white !important;
            padding: 4px 12px !important;
            border-radius: 4px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            animation: savePulse 0.8s infinite !important;
            display: inline-block;
        }

        @keyframes savePulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.02); }
        }

        /* Highlight editable cells more obviously */
        .ajaxCRUD td .editable {
            padding: 6px 10px;
            border-radius: 4px;
            transition: all 0.2s;
            display: inline-block;
            min-width: 60px;
        }

        .ajaxCRUD td .editable:hover {
            background-color: #fff3cd !important;
            box-shadow: 0 0 0 2px #ffc107;
            cursor: pointer;
        }

        /* Success feedback after save */
        .saved-flash {
            animation: savedFlash 0.5s ease-out;
        }

        @keyframes savedFlash {
            0% { background-color: #d4edda; }
            100% { background-color: transparent; }
        }

        /* Make the table wider */
        .crud-wrapper .ajaxCRUD {
            width: 100%;
        }

        /* Style the Add button */
        .crud-wrapper input[type="button"][value^="Add"] {
            background: linear-gradient(to bottom, #27ae60, #1e8449) !important;
            font-size: 14px !important;
            padding: 10px 24px !important;
        }

        .crud-wrapper input[type="button"][value^="Add"]:hover {
            background: linear-gradient(to bottom, #2ecc71, #27ae60) !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>ajaxCRUD v7.0 - Functional CRUD Demo</h1>
            <p>Full Create, Read, Update, Delete operations with AJAX auto-save</p>
        </header>

        <div class="instructions">
            <h3>How to Use This Demo</h3>
            <ul>
                <li><strong>Edit:</strong> Click any cell to edit inline - changes save automatically</li>
                <li><strong>Save Indicator:</strong> "Saving..." appears while updating the database</li>
                <li><strong>Add:</strong> Click "Add Contact" button to create new records</li>
                <li><strong>Delete:</strong> Click the X button on any row to remove it</li>
                <li><strong>Dropdowns:</strong> Status and Priority use select dropdowns</li>
                <li><strong>Range Slider:</strong> Rating field uses a slider (0-100)</li>
                <li><strong>Toggle:</strong> Active field uses a toggle switch</li>
                <li><strong>Validation:</strong> Email field validates email format</li>
            </ul>
        </div>

        <div class="crud-wrapper">
            <?php $tbl->showTable(); ?>
        </div>

        <footer>
            <p>ajaxCRUD v7.0 - Database: SQLite | All changes persist to: demo_crud.sqlite</p>
        </footer>
    </div>

    <script>
    // Add visual feedback after save completes
    const originalHandleUpdateResponse = window.handleUpdateResponse;
    window.handleUpdateResponse = function(returnString) {
        originalHandleUpdateResponse(returnString);

        // Flash green on the saved element
        if (returnString.substring(0, 5) !== 'error') {
            const brokenString = returnString.split('|');
            const id = brokenString[0];
            const showElement = document.getElementById(id + '_show');
            if (showElement) {
                showElement.classList.add('saved-flash');
                setTimeout(() => showElement.classList.remove('saved-flash'), 500);
            }
        }
    };
    </script>
</body>
</html>
