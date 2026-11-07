<?php
$conn = new mysqli('localhost', 'root', '', 'hostel_management_system');
$res = $conn->query("DESCRIBE firms");
echo "FIRMS:\n";
while($row = $res->fetch_assoc()) { echo $row['Field']." - ".$row['Type']."\n"; }

$res2 = $conn->query("DESCRIBE firm_payments");
echo "\nFIRM_PAYMENTS:\n";
if($res2) {
    while($row = $res2->fetch_assoc()) { echo $row['Field']." - ".$row['Type']."\n"; }
} else {
    echo "firm_payments does not exist or error";
}
?>
