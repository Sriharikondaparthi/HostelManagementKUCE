<?php 
require 'admin_header.php'; 

// Admin session value
$admin_id = $_SESSION['admin_id'] ?? '1'; 

// Fetch messages
$stmt = $conn->prepare("
    SELECT msg_id, sender_type, sender_id, receiver_type, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, created_at
    FROM Message
    WHERE (receiver_type = 'admin' AND receiver_id = ?) OR receiver_type = 'all_admin'
    ORDER BY created_at DESC, msg_date DESC, msg_time DESC
");
if ($stmt) {
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = false;
}
?>

<div class="glass-card shadow-lg animated fadeIn">
    <div class="section-header px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h4 mb-0 text-dark font-weight-bold"><i class="fas fa-inbox mr-2 text-info"></i> Official Communications</h2>
        <span class="badge badge-pill badge-info px-4 py-1 mt-1"><?php echo ($res ? $res->num_rows : 0); ?> Messages</span>
    </div>
    
    <div class="section-body p-4">
        <?php
        if ($res === false) {
            echo '<div class="alert alert-danger shadow-sm border-danger"><i class="fas fa-exclamation-triangle mr-2"></i> Error: Unable to retrieve communications log.</div>';
        } else {
            if ($res->num_rows === 0) {
                echo '<div class="text-center py-5">
                        <i class="fas fa-envelope-open-text fa-4x mb-3 text-muted opacity-2"></i>
                        <h5 class="text-muted font-weight-bold">Your inbox is empty</h5>
                        <p class="text-muted small">All messages directed to the administration will appear here.</p>
                      </div>';
            } else {
                while ($row = $res->fetch_assoc()) {
                    $subject = htmlspecialchars($row['subject_h'] ?? '(No Subject)');
                    $msg_txt = nl2br(htmlspecialchars($row['message'] ?? ''));
                    $s_type = strtoupper(htmlspecialchars($row['sender_type'] ?? 'UNKNOWN'));
                    $s_id = htmlspecialchars($row['sender_id'] ?? '-');
                    $date = htmlspecialchars($row['msg_date'] ?? '');
                    $time = htmlspecialchars($row['msg_time'] ?? '');
                    
                    $sender_color = ($s_type == 'STUDENT') ? 'text-info' : 'text-warning';
                    
                    $hostel_info = '';
                    if (!empty($row['hostel_id'])) {
                        $h_stmt = $conn->prepare("SELECT Hostel_name FROM Hostel WHERE Hostel_id = ? LIMIT 1");
                        $h_stmt->bind_param("s", $row['hostel_id']);
                        $h_stmt->execute();
                        $h_res = $h_stmt->get_result();
                        if ($h_res && $h_row = $h_res->fetch_assoc()) {
                            $hostel_info = ' | <span class="bg-light px-2 border rounded">Hostel: ' . htmlspecialchars($h_row['Hostel_name']) . '</span>';
                        }
                    }

                    echo '<div class="mb-5 shadow-sm rounded border-left" style="border-left: 6px solid #00bcd4 !important; background:#fff; overflow:hidden;">';
                    echo "  <div class=\"px-4 py-3 d-flex justify-content-between align-items-center\" style=\"background: rgba(0,188,212,0.02); border-bottom: 1px solid #f0f0f0;\">
                                <h4 class=\"h6 font-weight-bold text-dark mb-0\">{$subject}</h4>
                                <span class=\"small text-muted font-italic\"><i class=\"far fa-clock mr-1\"></i> {$date} at {$time}</span>
                            </div>";
                    echo "  <div class=\"px-4 py-3\">
                                <div class=\"small mb-3 text-muted\">
                                    Sender: <span class=\"font-weight-bold {$sender_color}\">{$s_type}</span> (ID: <span class='text-dark'>{$s_id}</span>) {$hostel_info}
                                </div>
                                <div class=\"text-dark mb-2\" style=\"line-height:1.7; font-size: 0.95em;\">{$msg_txt}</div>
                            </div>";
                    echo '</div>';
                }
            }
        }
        ?>
    </div>
</div>

<?php 
require 'admin_footer.php'; 
?>
