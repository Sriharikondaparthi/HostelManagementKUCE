<?php
require 'includes/config.inc.php';
$res = $conn->query("SELECT * FROM Hostel");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
