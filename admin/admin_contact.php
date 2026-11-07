<?php 
require 'admin_header.php'; 

// Admin -> Student/Manager handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject']) && isset($_POST['message'])) {
    $sender_type = 'admin';
    $sender_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? '0'; 
    $recipient_type = $_POST['recipient_type'] ?? ''; 
    $recipient_manager = trim($_POST['recipient_manager_id'] ?? ''); 
    $recipient_roll = trim($_POST['recipient_roll'] ?? ''); 
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (!empty($recipient_type) && !empty($subject) && !empty($message)) {
        $today_date = date("Y-m-d"); $time = date("H:i:s");
        $ins = $conn->prepare("INSERT INTO Message (sender_type, sender_id, receiver_type, receiver_id, subject_h, message, msg_date, msg_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($recipient_type === 'student') {
            $chk = $conn->prepare("SELECT Student_id FROM student WHERE Student_id = ? LIMIT 1");
            $chk->bind_param("s", $recipient_roll); $chk->execute(); $res = $chk->get_result();
            if ($res->num_rows > 0) {
                $recipient_id = (string)$res->fetch_assoc()['Student_id'];
                $ins->bind_param("ssssssss", $sender_type, $sender_id, $recipient_type, $recipient_id, $subject, $message, $today_date, $time);
                if ($ins->execute()) echo "<script>alert('Communication Dispatched Successfully.');</script>";
            } else echo "<script>alert('Error: Specified Student ID was not found.');</script>";
        } elseif ($recipient_type === 'manager') {
            $recipient_id = $recipient_manager;
            $ins->bind_param("ssssssss", $sender_type, $sender_id, $recipient_type, $recipient_id, $subject, $message, $today_date, $time);
            if ($ins->execute()) echo "<script>alert('Communication Dispatched Successfully.');</script>";
        }
    }
}
?>

<div class="glass-card shadow-lg animated fadeIn">
    <div class="section-header px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h4 mb-0 text-dark font-weight-bold"><i class="fas fa-paper-plane mr-2 text-info"></i> Dispatch Official Notice</h2>
        <span class="badge badge-info p-2 px-3">Direct Messenger</span>
    </div>
    <div class="section-body p-4">
        <form action="" method="post">
            <div class="row">
                <!-- Select Recipient Area -->
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="p-4 rounded border h-100 bg-light" style="border: 1px solid #ddd !important;">
                        <h6 class="font-weight-bold text-muted text-uppercase small mb-4">Step 1: Identify Recipient</h6>
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold small text-muted text-uppercase">Sender Group</label>
                            <div class="h6 font-weight-bold text-info"><i class="fas fa-shield-alt mr-2"></i> Central Administration</div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold small text-muted text-uppercase">Target Audience Type</label>
                            <select name="recipient_type" id="recipient_type_toggle" class="form-control form-control-lg custom-select font-weight-bold" required>
                                <option value="">-- Choose Target --</option>
                                <option value="student">Individual Student</option>
                                <option value="manager">Hostel Manager Office</option>
                            </select>
                        </div>

                        <div id="target_student" style="display:none;" class="form-group slide-in mb-4 pb-2 border-bottom">
                            <label class="font-weight-bold small text-muted text-uppercase">Provide Student ID</label>
                            <input type="text" name="recipient_roll" class="form-control" placeholder="Search Hall Ticket Number...">
                        </div>

                        <div id="target_manager" style="display:none;" class="form-group slide-in">
                            <label class="font-weight-bold small text-muted text-uppercase">Select Active Manager</label>
                            <select name="recipient_manager_id" class="form-control custom-select">
                                <option value="">-- Select Office --</option>
                                <?php
                                $mgrs = $conn->query("SELECT hm.Hostel_man_id, h.Hostel_name, hm.Fname, hm.Lname FROM Hostel_Manager hm JOIN Hostel h ON hm.Hostel_id = h.Hostel_id");
                                while($m = $mgrs->fetch_assoc()) echo "<option value='{$m['Hostel_man_id']}'>{$m['Hostel_name']} - Mgr. {$m['Fname']}</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Compose Message Area -->
                <div class="col-lg-7">
                    <div class="p-1">
                        <h6 class="font-weight-bold text-muted text-uppercase small mb-4">Step 2: Compose Dispatch</h6>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold small text-muted text-uppercase">Communication Subject</label>
                            <input type="text" name="subject" class="form-control form-control-lg font-weight-bold" placeholder="Enter clear, concise subject line" required>
                        </div>
                        <div class="form-group mb-4">
                            <label class="font-weight-bold small text-muted text-uppercase">Formal Message Content</label>
                            <textarea name="message" class="form-control" rows="12" placeholder="Write your message here. Please maintain a formal tone for all administrative communications..." required style="resize:none; background: #fff; border-color: #ddd;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-info btn-block btn-lg py-3 font-weight-bold shadow-sm">
                            <i class="fas fa-paper-plane mr-2"></i> AUTHORIZE AND DISPATCH NOTICE
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('recipient_type_toggle').addEventListener('change', function() {
    const s = document.getElementById('target_student');
    const m = document.getElementById('target_manager');
    s.style.display = (this.value === 'student') ? 'block' : 'none';
    m.style.display = (this.value === 'manager') ? 'block' : 'none';
});
</script>

<?php 
require 'admin_footer.php'; 
?>
