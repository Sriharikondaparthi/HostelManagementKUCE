<?php
session_start();

if (isset($_POST['new_hostel_id']) && isset($_SESSION['managed_hostels'])) {
    $new_id = trim($_POST['new_hostel_id']);
    
    // Ensure the manager actually owns this hostel
    if (in_array($new_id, $_SESSION['managed_hostels'])) {
        $_SESSION['hostel_id'] = $new_id;
    }
}

// Redirect back to wherever they came from
$redirect = $_SERVER['HTTP_REFERER'] ?? '../home_manager.php';
header("Location: " . $redirect);
exit();
?>
