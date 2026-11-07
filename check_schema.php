<?php
require 'includes/config.inc.php';

function print_table($conn, $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

print_table($conn, 'Hostel_Manager');
print_table($conn, 'Hostel');
print_table($conn, 'Room');
print_table($conn, 'Student');
?>
