<?php
require 'includes/config.inc.php';
$res = $conn->query("SHOW COLUMNS FROM Message");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
