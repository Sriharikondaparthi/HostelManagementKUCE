<?php
require_once __DIR__ . '/../includes/config.inc.php';

$query = "CREATE TABLE IF NOT EXISTS firm_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_date DATE NOT NULL,
    firm_name VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_mode VARCHAR(100),
    reference_no VARCHAR(100),
    sector VARCHAR(100),
    billing_month VARCHAR(50),
    billing_year INT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($query)) {
    echo "Table 'firm_payments' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
