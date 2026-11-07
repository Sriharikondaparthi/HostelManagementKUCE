<?php
require 'includes/config.inc.php';

$sql1 = "CREATE TABLE IF NOT EXISTS `Attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Student_id` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present', 'Absent') NOT NULL DEFAULT 'Present',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Student_id`) REFERENCES `Student`(`Student_id`) ON DELETE CASCADE
)";

$sql2 = "CREATE TABLE IF NOT EXISTS `Payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Student_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Student_id`) REFERENCES `Student`(`Student_id`) ON DELETE CASCADE
)";

$sql3 = "CREATE TABLE IF NOT EXISTS `Visitor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(255) NOT NULL,
  `Student_id` varchar(255) NOT NULL,
  `visit_date` date NOT NULL,
  `charge` decimal(10,2) NOT NULL DEFAULT '100.00', -- Example 1 day charge
  `status` enum('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`Student_id`) REFERENCES `Student`(`Student_id`) ON DELETE CASCADE
)";

if ($conn->query($sql1) === TRUE) {
    echo "Table Attendance created successfully\n";
} else {
    echo "Error creating table Attendance: " . $conn->error . "\n";
}

if ($conn->query($sql2) === TRUE) {
    echo "Table Payment created successfully\n";
} else {
    echo "Error creating table Payment: " . $conn->error . "\n";
}

if ($conn->query($sql3) === TRUE) {
    echo "Table Visitor created successfully\n";
} else {
    echo "Error creating table Visitor: " . $conn->error . "\n";
}

$conn->close();
?>
