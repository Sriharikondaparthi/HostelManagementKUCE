<?php
require 'includes/config.inc.php';
$res = $conn->query("SELECT * FROM Hostel");
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= $row['Hostel_id'] . " | " . $row['Hostel_name'] . "\n";
}
file_put_contents('hostel_data.txt', $out);
?>
