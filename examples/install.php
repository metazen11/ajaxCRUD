<?php
/**
 * ajaxCRUD Demo Installation Script
 * Creates demo tables and sample data
 */

require_once('../preheader.php');

echo "<h2>ajaxCRUD v7.0 - Demo Installation</h2>\n";

try {
    $db = getDB();

    // Create tblDemo
    $db->exec("CREATE TABLE IF NOT EXISTS tblDemo(
        pkID INT PRIMARY KEY AUTO_INCREMENT,
        fldField1 VARCHAR(45),
        fldField2 VARCHAR(45),
        fldCertainFields VARCHAR(40),
        fldLongField TEXT
    )");
    echo "<p>✓ Table <b>tblDemo</b> created/verified</p>\n";

    // Create tblDemo2
    $db->exec("CREATE TABLE IF NOT EXISTS tblDemo2(
        pkID INT PRIMARY KEY AUTO_INCREMENT,
        fldField1 VARCHAR(45),
        fldField2 VARCHAR(45),
        fldCertainFields VARCHAR(40),
        fldLongField TEXT
    )");
    echo "<p>✓ Table <b>tblDemo2</b> created/verified</p>\n";

    // Create tblFriend
    $db->exec("CREATE TABLE IF NOT EXISTS tblFriend (
        pkFriendID INT PRIMARY KEY AUTO_INCREMENT,
        fldName VARCHAR(25),
        fldAddress VARCHAR(30),
        fldCity VARCHAR(20),
        fldState CHAR(2),
        fldZip VARCHAR(5),
        fldPhone VARCHAR(15),
        fldEmail VARCHAR(35),
        fldBestFriend CHAR(1),
        fldDateMet DATE,
        fldFriendRating CHAR(1),
        fldOwes DECIMAL(6,2),
        fldPicture VARCHAR(30)
    )");
    echo "<p>✓ Table <b>tblFriend</b> created/verified</p>\n";

    // Insert sample data (only if tables are empty)
    $count = q1("SELECT COUNT(*) FROM tblDemo");
    if ($count == 0) {
        qr("INSERT INTO tblDemo (fldField1, fldField2, fldCertainFields, fldLongField) VALUES (?, ?, ?, ?)",
            ['Testing', 'Testing2', 'CRUD', 'First ajaxCRUD Test']);
        echo "<p>✓ Sample data added to <b>tblDemo</b></p>\n";
    }

    $count = q1("SELECT COUNT(*) FROM tblDemo2");
    if ($count == 0) {
        qr("INSERT INTO tblDemo2 (fldField1, fldField2, fldCertainFields, fldLongField) VALUES (?, ?, ?, ?)",
            ['Testing', 'Testing2', 'CRUD', 'Second ajaxCRUD Test']);
        echo "<p>✓ Sample data added to <b>tblDemo2</b></p>\n";
    }

    $count = q1("SELECT COUNT(*) FROM tblFriend");
    if ($count == 0) {
        // Insert multiple sample friends
        $friends = [
            ['Sean Dempsey', '13 Back River Road', 'Dover', 'NH', '03820', '(603) 978-8841', 'sean@loudcanvas.com', 'N', '2011-10-27', '5', 122.01, ''],
            ['Justin Rigby', '22 Farmington Rd', 'Rochester', 'VT', '05401', '(802) 661-4051', 'sean@seandempsey.com', '', '2011-10-19', '1', 22.00, ''],
            ['Ryan Dempsey', '', '', 'VT', '', '', 'ryan@dempsey.com', '', '2011-10-20', '', 0.00, ''],
        ];

        $stmt = $db->prepare("INSERT INTO tblFriend (fldName, fldAddress, fldCity, fldState, fldZip, fldPhone, fldEmail, fldBestFriend, fldDateMet, fldFriendRating, fldOwes, fldPicture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($friends as $friend) {
            $stmt->execute($friend);
        }
        echo "<p>✓ Sample data added to <b>tblFriend</b></p>\n";
    }

    echo "<hr>\n";
    echo "<h3>Installation Complete!</h3>\n";
    echo "<p><a href='example.php'>Try out a basic demo</a></p>\n";
    echo "<p><a href='example2.php'>Try out a demo with two ajaxCRUD tables</a></p>\n";
    echo "<p><a href='example3.php'>Try out a demo with validation, masking, file upload, and CSV export</a></p>\n";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database configuration in preheader.php</p>\n";
}
