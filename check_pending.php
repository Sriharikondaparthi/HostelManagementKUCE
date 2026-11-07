<?php
require 'includes/config.inc.php';
$res = $conn->query("SELECT a.Student_id, a.Hostel_id, h.Hostel_name, s.Fname, s.Lname FROM Application a JOIN Hostel h ON a.Hostel_id = h.Hostel_id JOIN Student s ON a.Student_id = s.Student_id WHERE a.Application_status = '1'");
echo "PENDING APPLICATIONS:\n";
if ($res->num_rows == 0) {
    echo "None.\n";
} else {
    while($row = $res->fetch_assoc()) {
        echo "- " . $row['Fname'] . " " . $row['Lname'] . " (" . $row['Student_id'] . ") | Hostel: " . $row['Hostel_name'] . "\n";
    }
}
?>
