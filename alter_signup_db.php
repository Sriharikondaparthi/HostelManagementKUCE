<?php
require 'includes/config.inc.php';

$sql = "ALTER TABLE Signup_Requests ADD COLUMN requested_hostel_id INT(11) AFTER mob_no";

if ($conn->query($sql) === TRUE) {
    echo "Column requested_hostel_id added successfully.\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}
$conn->close();
?>
