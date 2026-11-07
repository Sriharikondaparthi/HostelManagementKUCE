<?php
require 'admin_header.php';
require '../includes/sms_gateway.php';

$message = "";
$message_type = "";

if (isset($_POST['approve_request'])) {
    $req_id = $_POST['req_id'];
    $assigned_ht = strtoupper(trim($_POST['assigned_ht']));
    
    // Fetch request details
    $req_q = $conn->prepare("SELECT ssc_hallticket, mob_no FROM Signup_Requests WHERE request_id = ? AND status = 'Pending'");
    $req_q->bind_param("i", $req_id);
    $req_q->execute();
    $req_res = $req_q->get_result();
    
    if($req_res->num_rows > 0) {
        $row = $req_res->fetch_assoc();
        $mob_no = $row['mob_no'];
        
        // Ensure student doesn't exist already (Hall Ticket MUST be unique)
        $stu_chk = $conn->prepare("SELECT Student_id FROM Student WHERE Student_id = ?");
        $stu_chk->bind_param("s", $assigned_ht);
        $stu_chk->execute();
        
        if($stu_chk->get_result()->num_rows > 0) {
            $message = "Approval failed: A student account with this Hall Ticket Number already exists.";
            $message_type = "danger";
        } else {
            $conn->begin_transaction();
            try {
                // Insert Student Stub (profile_completed = 0)
                $hashed_pwd = password_hash($assigned_ht, PASSWORD_DEFAULT);
                $ins_stu = $conn->prepare("INSERT INTO Student (Student_id, Mob_no, Pwd, profile_completed) VALUES (?, ?, ?, 0)");
                $ins_stu->bind_param("sss", $assigned_ht, $mob_no, $hashed_pwd);
                $ins_stu->execute();
                
                // Update Request Status
                $upd_req = $conn->prepare("UPDATE Signup_Requests SET status = 'Approved', assigned_hallticket = ? WHERE request_id = ?");
                $upd_req->bind_param("si", $assigned_ht, $req_id);
                $upd_req->execute();
                
                $conn->commit();
                
                // Send SMS 
                if (function_exists('send_student_sms')) {
                    $sms_text = "Your H-Track portal request is Approved. Login using Hall Ticket: $assigned_ht as password.";
                    send_student_sms($mob_no, $sms_text);
                }
                
                $message = "Request approved successfully! Hall Ticket $assigned_ht assigned. SMS dispatched.";
                $message_type = "success";
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Database error occurred during approval.";
                $message_type = "danger";
            }
        }
    }
}

if (isset($_POST['reject_request'])) {
    $req_id = $_POST['req_id'];
    $upd_req = $conn->prepare("UPDATE Signup_Requests SET status = 'Rejected' WHERE request_id = ?");
    $upd_req->bind_param("i", $req_id);
    if($upd_req->execute()){
         $message = "Request rejected.";
         $message_type = "info";
    }
}

if (isset($_POST['clear_history'])) {
    $del_q = "DELETE FROM Signup_Requests WHERE status != 'Pending'";
    if(mysqli_query($conn, $del_q)){
        $message = "All processed requests (Approved/Rejected) have been cleared from the history.";
        $message_type = "success";
    }
}

?>

<div class="glass-module shadow-lg animated fadeIn">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #ff9800;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-user-clock mr-2 text-warning"></i> Pending Access Requests</h2>
    </div>
    
    <div class="section-body p-4">
        <?php if ($message != ""): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4 shadow-sm border-<?php echo $message_type; ?>">
                <i class="fas fa-info-circle mr-2"></i> <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="table-responsive border rounded shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Req ID</th>
                        <th>SSC Hall Ticket</th>
                        <th>Mobile Number</th>
                        <th>Requested Hostel</th>
                        <th>Request Date</th>
                        <th>Action (Assign Hall Ticket)</th>
                    </tr>
                </thead>
                <tbody style="background:#fff;">
                    <?php
                    $sql_query = "SELECT sr.*, h.Hostel_name FROM Signup_Requests sr LEFT JOIN Hostel h ON sr.requested_hostel_id = h.Hostel_id WHERE sr.status = 'Pending' ORDER BY sr.request_date ASC";
                    $res = mysqli_query($conn, $sql_query);
                    
                    if (mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $r_id = $row['request_id'];
                            $base_name = $row['Hostel_name'];
                            $hostel_desc = [
                                'A' => 'Old Boys Hostel',   'B' => 'New Boys Hostel',
                                'C' => 'Girls Hostel',      'D' => 'Degree Boys Hostel',
                                'E' => 'Degree Girls Hostel','F' => 'PG Hostel'
                            ];
                            
                            if ($base_name) {
                                $hname = strlen($base_name) <= 2 ? "Hostel " . $base_name : $base_name;
                                if (isset($hostel_desc[$base_name])) {
                                    $hname .= " (" . $hostel_desc[$base_name] . ")";
                                }
                            } else {
                                $hname = 'Unspecified';
                            }
                            
                            echo "<tr>
                                    <td class='font-weight-bold'>{$r_id}</td>
                                    <td class='text-primary font-weight-bold'>{$row['ssc_hallticket']}</td>
                                    <td>{$row['mob_no']}</td>
                                    <td><span class='badge badge-info px-2 py-1'>{$hname}</span></td>
                                    <td>{$row['request_date']}</td>
                                    <td>
                                        <form method='POST' action='pending_signups.php' class='d-flex align-items-center mb-0'>
                                            <input type='hidden' name='req_id' value='{$r_id}'>
                                            <input type='text' name='assigned_ht' class='form-control form-control-sm mr-2 text-uppercase' placeholder='Official HM Hall Ticket' required style='min-width:200px;'>
                                            <button type='submit' name='approve_request' class='btn btn-success btn-sm font-weight-bold mr-2 shadow-sm text-nowrap'><i class='fas fa-check'></i> Approve & Send</button>
                                            <button type='submit' name='reject_request' class='btn btn-danger btn-sm font-weight-bold shadow-sm text-nowrap' formnovalidate onclick='return confirm(\"Are you sure you want to reject this request?\");'><i class='fas fa-times'></i> Reject</button>
                                        </form>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No pending requests found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h5 class="mb-0 font-weight-bold"><i class="fas fa-history text-muted mr-2"></i> Recent Decisions</h5>
            <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete all approved and rejected records from history? Pending requests will not be deleted.');">
                <button type="submit" name="clear_history" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-trash-alt mr-1"></i> Clear History
                </button>
            </form>
        </div>
        <div class="table-responsive border rounded shadow-sm" style="max-height: 250px;">
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light text-secondary small">
                    <tr><th>Req ID</th><th>SSC Hall Ticket</th><th>Status</th><th>Assigned Hall Ticket</th></tr>
                </thead>
                <tbody>
                    <?php
                    $hist_q = "SELECT * FROM Signup_Requests WHERE status != 'Pending' ORDER BY request_date DESC LIMIT 20";
                    $hist_res = mysqli_query($conn, $hist_q);
                    while($hr = mysqli_fetch_assoc($hist_res)){
                        $badge = $hr['status'] == 'Approved' ? 'badge-success' : 'badge-danger';
                        $ht = $hr['assigned_hallticket'] ? $hr['assigned_hallticket'] : '-';
                        echo "<tr>
                                <td>{$hr['request_id']}</td>
                                <td class='text-muted'>{$hr['ssc_hallticket']}</td>
                                <td><span class='badge {$badge}'>{$hr['status']}</span></td>
                                <td class='font-weight-bold'>{$ht}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (isset($_POST['approve_request']) && $message_type == "success"): ?>
<style>
    #whatsapp-snackbar {
        visibility: hidden;
        min-width: 350px;
        background-color: #25D366; /* WhatsApp Green */
        color: #fff;
        text-align: left;
        border-radius: 50px;
        padding: 15px 25px;
        position: fixed;
        z-index: 10001;
        left: 50%;
        transform: translateX(-50%);
        bottom: 50px;
        font-size: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    #whatsapp-snackbar.show {
        visibility: visible;
        animation: fadein 0.5s;
    }
    @keyframes fadein { from { bottom: 0; opacity: 0; } to { bottom: 50px; opacity: 1; } }
</style>

<div id="whatsapp-snackbar" class="show">
    <span style="display:flex; align-items:center;">
        <i class="fab fa-whatsapp fa-2x mr-3"></i> 
        <span>
            <strong>Approved!</strong><br>
            <small>Send credentials to <?php echo $mob_no; ?></small>
        </span>
    </span>
    <a href="https://wa.me/91<?php echo $mob_no; ?>?text=Your H-Track portal request is Approved. Login using Hall Ticket: <?php echo $assigned_ht; ?> as password." 
       target="_blank"
       class="btn btn-light btn-sm ml-3 font-weight-bold" style="color: #075E54; border-radius: 20px; padding: 5px 15px;">
       <i class="fas fa-paper-plane mr-1"></i> SEND WHATSAPP
    </a>
    <button onclick="document.getElementById('whatsapp-snackbar').style.display='none'" class="btn btn-link text-white ml-2 p-0" style="text-decoration:none; font-size:20px;">&times;</button>
</div>
<?php endif; ?>

<?php require 'admin_footer.php'; ?>
