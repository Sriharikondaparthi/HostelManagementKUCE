<?php
require_once '../includes/config.inc.php';

if (isset($_GET['hostel_id'])) {
    $hostel_id = (int)$_GET['hostel_id'];
    
    // Fetch rooms with their current occupancy counts
    $sql = "SELECT r.Room_id, r.Room_No, 
            (SELECT COUNT(*) FROM Student s WHERE s.Room_id = r.Room_id) as occupancy 
            FROM Room r 
            WHERE r.Hostel_id = ? 
            ORDER BY r.Room_No ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($rooms);
}
?>
