<?php
require 'includes/config.inc.php';

// Add the Admin account back so manager_profile.php doesn't crash
$pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
$q = $conn->prepare("INSERT INTO Hostel_Manager (Hostel_man_id, Username, Fname, Lname, Mob_no, Email, Pwd, isadmin, managed_hostels, Hostel_id) VALUES (1, 'admin', 'System', 'Admin', '0000000000', 'admin@htrack.com', ?, 1, '', 1)");
$q->bind_param("s", $pass_admin);
$q->execute();

echo "Admin account restored.\n";
?>
