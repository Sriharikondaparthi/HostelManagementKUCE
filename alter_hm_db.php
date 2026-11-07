<?php
require 'includes/config.inc.php';

if ($conn->query("ALTER TABLE Hostel_Manager ADD managed_hostels VARCHAR(255) NULL AFTER Hostel_id")) {
    echo "Added column.\n";
}

if ($conn->query("UPDATE Hostel_Manager SET managed_hostels = CAST(Hostel_id AS CHAR)")) {
    echo "Migrated existing values.\n";
}

if ($conn->query("UPDATE Hostel_Manager SET managed_hostels = '1,2,4', Hostel_id = 1 WHERE Username = 'm1'")) {
    echo "Updated m1 to 1,2,4 (Boys).\n";
}

if ($conn->query("UPDATE Hostel_Manager SET managed_hostels = '3,5,6', Hostel_id = 3 WHERE Username = 'm2'")) {
    echo "Updated m2 to 3,5,6 (Girls/PG).\n";
}

echo "Success.\n";
?>
