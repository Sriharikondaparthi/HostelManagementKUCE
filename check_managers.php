<?php
require 'includes/config.inc.php';

$res = $conn->query("SELECT Hostel_man_id, Username, Fname FROM Hostel_Manager");
if($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query failed";
}
?>
