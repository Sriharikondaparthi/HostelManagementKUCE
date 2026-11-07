<?php
require 'includes/config.inc.php';

$sql = [
    "ALTER TABLE Student ADD COLUMN Photo varchar(255) NULL AFTER Pwd",
    "ALTER TABLE Student ADD COLUMN Signature varchar(255) NULL AFTER Photo",
    "ALTER TABLE Student ADD COLUMN Address text NULL AFTER Mother_name",
    "ALTER TABLE Student ADD COLUMN Declaration_agreed tinyint(1) default 0 AFTER Address"
];

foreach ($sql as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Successfully executed: $query\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
$conn->close();
?>
