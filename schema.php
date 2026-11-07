<?php
$conn = new mysqli('localhost', 'root', '', 'hostel_management_system');
$res = $conn->query('SHOW TABLES');
while($row = $res->fetch_array()) {
    $table = $row[0];
    echo "TABLE: $table\n";
    $cres = $conn->query("DESCRIBE `$table`");
    while($crow = $cres->fetch_assoc()) {
        echo "  - {$crow['Field']} ({$crow['Type']})\n";
    }
}
?>
