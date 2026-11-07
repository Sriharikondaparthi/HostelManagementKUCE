<?php
require 'includes/config.inc.php';
$res = $conn->query("SELECT Hostel_man_id, Username, Fname, Lname, managed_hostels FROM Hostel_Manager");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['Hostel_man_id'] . " | User: " . $row['Username'] . " | Name: " . $row['Fname'] . " " . $row['Lname'] . " | Hostels: " . $row['managed_hostels'] . "\n";
}
?>
