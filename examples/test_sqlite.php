<?php
/**
 * SQLite Test Example
 * Demonstrates using ajaxCRUD with SQLite instead of MySQL
 */

// Set the driver BEFORE including preheader
$DB_DRIVER = 'sqlite';

// Override the config to use a test database in this directory
$DB_CONFIG = [
    'sqlite' => [
        'path' => __DIR__ . '/test_database.sqlite',
    ],
];

require_once('../preheader.php');

echo "<h2>ajaxCRUD v7.0 - SQLite Test</h2>\n";
echo "<p>Using database: " . realpath(__DIR__) . "/test_database.sqlite</p>\n";

try {
    $db = getDB();

    // Create test table with SQLite-compatible syntax
    $db->exec("CREATE TABLE IF NOT EXISTS tblTest (
        pkID INTEGER PRIMARY KEY AUTOINCREMENT,
        fldName TEXT,
        fldDescription TEXT,
        fldStatus TEXT,
        fldCreatedAt TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✓ Table <b>tblTest</b> created/verified</p>\n";

    // Insert sample data if empty
    $count = q1("SELECT COUNT(*) FROM tblTest");
    if ($count == 0) {
        qr("INSERT INTO tblTest (fldName, fldDescription, fldStatus) VALUES (?, ?, ?)",
            ['Test Item 1', 'This is a test item created with SQLite', 'Active']);
        qr("INSERT INTO tblTest (fldName, fldDescription, fldStatus) VALUES (?, ?, ?)",
            ['Test Item 2', 'Another test item', 'Pending']);
        qr("INSERT INTO tblTest (fldName, fldDescription, fldStatus) VALUES (?, ?, ?)",
            ['Test Item 3', 'Third test item for demonstration', 'Active']);
        echo "<p>✓ Sample data inserted</p>\n";
    }

    echo "<hr>\n";
    echo "<h3>Test Data:</h3>\n";

    // Display test data
    $rows = q("SELECT * FROM tblTest ORDER BY pkID");
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Status</th><th>Created</th></tr>\n";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['pkID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fldName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fldDescription']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fldStatus']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fldCreatedAt']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<hr>\n";

    // Now use the ajaxCRUD class with SQLite
    include('../ajaxCRUD.class.php');

    $tblTest = new ajaxCRUD("Test Item", "tblTest", "pkID", "../");

    $tblTest->omitPrimaryKey();
    $tblTest->displayAs("fldName", "Name");
    $tblTest->displayAs("fldDescription", "Description");
    $tblTest->displayAs("fldStatus", "Status");
    $tblTest->displayAs("fldCreatedAt", "Created At");

    // Define allowable values for status
    $statuses = ["Active", "Pending", "Completed", "Cancelled"];
    $tblTest->defineAllowableValues("fldStatus", $statuses);

    // Make created date not editable
    $tblTest->disallowEdit('fldCreatedAt');

    echo "<h3>ajaxCRUD Table (SQLite Backend):</h3>\n";
    $tblTest->showTable();

    echo "<hr>\n";
    echo "<p><strong>Database Driver:</strong> " . getDBDriver() . "</p>\n";
    echo "<p><a href='example.php'>Back to MySQL Example</a></p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
