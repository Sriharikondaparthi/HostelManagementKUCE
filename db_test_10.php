<?php
$c = new mysqli('127.0.0.1', 'root', '', 'hostel_management_system');
if ($c->connect_error) die("Connection failed: " . $c->connect_error);

// 1. Give reasonable names to mandates if they are missing
$c->query("UPDATE mandates SET student_name = CONCAT('Student ', hall_ticket) WHERE student_name LIKE '%==%' OR student_name LIKE '%=%' OR LENGTH(student_name) > 30");

// 2. Give reasonable names to Student table if they got corrupted from mandates
$c->query("UPDATE Student SET Fname = CONCAT('Student ', Student_id), Lname='' WHERE Fname LIKE '%==%' OR Fname LIKE '%=%'");

echo "Done replacing mandates names with clean fallbacks based on hall ticket.\n";
?>
