<?php
$conn = new mysqli('localhost', 'root', '', 'hostel_management_system');
$res = $conn->query('SHOW TABLES');
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
