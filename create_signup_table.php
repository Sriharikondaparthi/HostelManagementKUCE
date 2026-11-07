<?php
require 'includes/config.inc.php';

$sql = "CREATE TABLE IF NOT EXISTS Signup_Requests (
    request_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ssc_hallticket VARCHAR(255) NOT NULL,
    mob_no VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    assigned_hallticket VARCHAR(255) DEFAULT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Signup_Requests table created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
$conn->close();
?>
