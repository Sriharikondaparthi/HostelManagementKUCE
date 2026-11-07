<?php
$c = new mysqli('127.0.0.1', 'root', '', 'hostel_management_system');
$res = $c->query("SHOW CREATE TABLE Room");
print_r($res->fetch_assoc());
$res = $c->query("SHOW CREATE TABLE Application");
print_r($res->fetch_assoc());
$res = $c->query("SHOW CREATE TABLE Student");
print_r($res->fetch_assoc());
?>
