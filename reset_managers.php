<?php
require 'includes/config.inc.php';

// 1. Clear existing managers
$conn->query("DELETE FROM Hostel_Manager");

// 2. Prepare passwords (lowercase as requested)
$pass_boys = password_hash('boys', PASSWORD_DEFAULT);
$pass_girls = password_hash('girls', PASSWORD_DEFAULT);

// 3. Insert Boys Manager (Username=B, Password=boys, managed_hostels=1,2,4)
$q1 = $conn->prepare("INSERT INTO Hostel_Manager (Hostel_man_id, Username, Fname, Lname, Mob_no, Email, Pwd, isadmin, managed_hostels, Hostel_id) VALUES (10, 'B', 'Boys', 'Manager', '0000000000', 'boys@htrack.com', ?, 0, '1,2,4', 1)");
$q1->bind_param("s", $pass_boys);
$q1->execute();

// 4. Insert Girls Manager (Username=G, Password=girls, managed_hostels=3,5,6)
$q2 = $conn->prepare("INSERT INTO Hostel_Manager (Hostel_man_id, Username, Fname, Lname, Mob_no, Email, Pwd, isadmin, managed_hostels, Hostel_id) VALUES (11, 'G', 'Girls', 'Manager', '1111111111', 'girls@htrack.com', ?, 0, '3,5,6', 3)");
$q2->bind_param("s", $pass_girls);
$q2->execute();

echo "Managers structure updated successfully.\n";
echo "Boys Manager Login: Username='B' / Password='boys'\n";
echo "Girls Manager Login: Username='G' / Password='girls'\n";
?>
