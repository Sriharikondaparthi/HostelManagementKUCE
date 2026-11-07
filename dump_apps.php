<?php
require 'includes/config.inc.php';
$res = $conn->query("SELECT * FROM Application WHERE Hostel_id = 2");
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= $row['Student_id'] . " | " . $row['Application_status'] . "\n";
}
file_put_contents('app_debug.txt', $out);
?>
