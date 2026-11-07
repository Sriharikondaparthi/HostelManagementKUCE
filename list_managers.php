<?php
require 'includes/config.inc.php';
$res = $conn->query("SELECT Hostel_man_id, Hostel_id, managed_hostels FROM Hostel_Manager");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
