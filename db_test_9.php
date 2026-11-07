<?php
$c = new mysqli('127.0.0.1', 'root', '', 'hostel_management_system');

$res = $c->query("SELECT * FROM mandates LIMIT 3");
while($row = $res->fetch_assoc()) { print_r($row); }
echo "\n====\n";
$res = $c->query("SELECT * FROM Payment LIMIT 3");
if ($res) { while($row = $res->fetch_assoc()) { print_r($row); } }
?>
