<?php

	require_once('../preheader.php');

    $success = q1("CREATE TABLE tblDemo(pkID INT PRIMARY KEY AUTO_INCREMENT,fldField1 VARCHAR(45),fldField2 VARCHAR(45),fldCertainFields VARCHAR(40),fldLongField TEXT);");

    if ($success){
        echo "TABLE <b>tblDemo</b> CREATED <br /><br />\n";
    }

	$success = q1("CREATE TABLE tblDemo2(pkID INT PRIMARY KEY AUTO_INCREMENT,fldField1 VARCHAR(45),fldField2 VARCHAR(45),fldCertainFields VARCHAR(40),fldLongField TEXT);");
    if ($success){
        echo "TABLE <b>tblDemo2</b> CREATED <br /><br />\n";
    }

	$success = q1("CREATE TABLE tblFriend (pkFriendID int(11) PRIMARY KEY AUTO_INCREMENT, fldName varchar(25),fldAddress varchar(30),fldCity varchar(20),fldState char(2),fldZip varchar(5),fldPhone varchar(15),fldEmail varchar(35),fldBestFriend char(1),fldDateMet date,fldFriendRating char(1),fldOwes double(6,2),fldPicture varchar(30));");
    if ($success){
        echo "TABLE <b>tblFriend</b> CREATED <br /><br />\n";
    }

    $success = qr("INSERT INTO tblDemo (fldField1, fldField2, fldCertainFields, fldLongField) VALUES (\"Testing\", \"Testing2\", \"CRUD\", \"First ajaxCRUD Test\")");
    $success = qr("INSERT INTO tblDemo2 (fldField1, fldField2, fldCertainFields, fldLongField) VALUES (\"Testing\", \"Testing2\", \"CRUD\", \"Second ajaxCRUD Test\")");
    $success = qr("INSERT INTO `tblFriend` (`pkFriendID`, `fldName`, `fldAddress`, `fldCity`, `fldState`, `fldZip`, `fldPhone`, `fldEmail`, `fldBestFriend`, `fldDateMet`, `fldFriendRating`, `fldOwes`, `fldPicture`) VALUES(1, 'Sean Dempsey', '13 Back River Road', 'Dover', 'NH', '03820', '(603) 978-8841', 'sean@loudcanvas.com', 'N', '2011-10-27', '5', 122.01, ''),(2, 'Justin Rigby', '22 Farmington Rd', 'Rochester', 'VT', '05401', '(802) 661-4051', 'sean@seandempsey.com', '', '2011-10-19', '1', 22.00, ''),(3, 'Ryan Dempsey', '', '', 'VT', '', '', 'ryan@dempsey.com', '', '2011-10-20', '', 0.00, '');");

    if ($success){
        echo "Example rows entered into <b>tblDemo</b> and <b>tblDemo2</b><br /><br />\n";
    }

    echo "<p><a href='example.php'>Try out a basic demo</a></p>\n";
    echo "<p><a href='example2.php'>Try out a demo with two ajaxCRUD tables.</a></p>\n";
    echo "<p><a href='example3.php'>Try out a demo with validation, masking, file upload, and csv export.</a></p>\n";

?>