<?php
$conn = new mysqli('localhost', 'root', '', 'hostel_management_system');
$res = $conn->query('SELECT DISTINCT Dept FROM Student');
while($row = $res->fetch_assoc()) {
    echo $row['Dept'] . "\n";
}
?>
