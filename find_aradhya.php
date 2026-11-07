<?php
require 'includes/config.inc.php';
$search = "%aradhya%";
$stmt = $conn->prepare("SELECT Student_id, Fname, Lname, Hostel_id, Room_id FROM Student WHERE Fname LIKE ? OR Lname LIKE ?");
$stmt->bind_param("ss", $search, $search);
$stmt->execute();
$res = $stmt->get_result();
$out = "STUDENT DATA:\n";
while($row = $res->fetch_assoc()) {
    $out .= json_encode($row) . "\n";
    $sid = $row['Student_id'];
    $app_q = mysqli_query($conn, "SELECT * FROM Application WHERE Student_id = '$sid'");
    $out .= "APPLICATIONS:\n";
    while($arow = mysqli_fetch_assoc($app_q)) {
        $out .= json_encode($arow) . "\n";
    }
}
file_put_contents('aradhya_debug.txt', $out);
?>
