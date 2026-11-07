<?php
require 'includes/config.inc.php';
$res = $conn->query("SELECT Hostel_id, Hostel_name FROM Hostel");
while($row = $res->fetch_assoc()) {
    echo $row['Hostel_id'] . " | " . $row['Hostel_name'] . "\n";
}
?>
