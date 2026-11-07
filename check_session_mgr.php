<?php
session_start();
require 'includes/config.inc.php';
$mid = $_SESSION['hostel_man_id'] ?? null;
if ($mid) {
    $res = $conn->query("SELECT * FROM Hostel_Manager WHERE Hostel_man_id = '$mid'");
    print_r($res->fetch_assoc());
    echo "\nSESSION DATA:\n";
    print_r($_SESSION);
} else {
    echo "No manager logged in session.";
}
?>
