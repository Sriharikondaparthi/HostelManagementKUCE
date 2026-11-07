<?php 
require 'admin_header.php'; 

$message = "";
$message_type = "";

if (isset($_POST['update_challan'])) {
    $hall_ticket = trim($_POST['hall_ticket']);
    $reference_no = trim($_POST['reference_no']);
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $date = $_POST['date'] ? $_POST['date'] : null;
    
    // Check if payment exists for this student, amount, and category
    // We update Reference_Number and Date as requested
    $stmt = $conn->prepare("UPDATE Payment SET status = 'Paid', Reference_Number = ?, Transaction_Date = ? WHERE Student_id = ? AND amount = ? AND category = ? AND status = 'Pending' LIMIT 1");
    $stmt->bind_param("sssds", $reference_no, $date, $hall_ticket, $amount, $category);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Payment record for $hall_ticket [$category] successfully matched. Ref: $reference_no updated."; 
        $message_type = "success";
    } else {
        $message = "Error: No matching 'Pending' record found for this Hall Ticket, Amount, and Category combination."; 
        $message_type = "danger";
    }
}

if (isset($_POST['bulk_update_challan'])) {
    if (isset($_FILES['challans_csv']) && $_FILES['challans_csv']['error'] == 0) {
        set_time_limit(0);
        $filename = $_FILES['challans_csv']['tmp_name'];
        $file = fopen($filename, "r");
        fgetcsv($file); // Skip Header
        
        $success_count = 0;
        $fail_count = 0;
        
        $stmt = $conn->prepare("UPDATE Payment SET status = 'Paid', Reference_Number = ?, Transaction_Date = ? WHERE Student_id = ? AND amount = ? AND category = ? AND status = 'Pending' LIMIT 1");

        while (($column = fgetcsv($file)) !== FALSE) {
            if (count($column) < 5) continue; 
            
            $hall_ticket = trim($column[0]);
            $ref_no = trim($column[1]);
            $date = trim($column[2]);
            $category = trim($column[3]);
            $amount = floatval(trim($column[4]));
            
            if (empty($date)) $date = date('Y-m-d');
            
            $stmt->bind_param("sssds", $ref_no, $date, $hall_ticket, $amount, $category);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        $stmt->close();
        fclose($file);
        
        if ($success_count > 0) {
            $message = "Successfully bulk cleared $success_count payment records. ($fail_count unmatched skip rows).";
            $message_type = "success";
        } else {
            $message = "Error: 0 records matched. Check your CSV format specifically: Hall Ticket, Reference, Date, Category, Amount, (Year).";
            $message_type = "danger";
        }
    }
}

if (isset($_POST['send_reminder'])) {
    $target_ht = $_POST['target_ht'];
    $amt = $_POST['amt'];
    $cat = $_POST['cat'];
    
    $sender_type = 'admin';
    $sender_id = $_SESSION['admin_id'] ?? 'Admin';
    $receiver_type = 'student';
    $subject = "Official Reminder: Pending Dues ($cat)";
    $msg = "Dear Student, this is an official reminder that you have UNPAID dues of Rs " . number_format((float)$amt, 2) . " for $cat. Please submit your fee challan details to the Administration Office as soon as possible to avoid penalties.";
    $today_date = date("Y-m-d"); $time = date("H:i:s");
    
    $ins = $conn->prepare("INSERT INTO Message (sender_type, sender_id, receiver_type, receiver_id, subject_h, message, msg_date, msg_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $ins->bind_param("ssssssss", $sender_type, $sender_id, $receiver_type, $target_ht, $subject, $msg, $today_date, $time);
    
    if ($ins->execute()) {
        $message = "Due reminder successfully posted to $target_ht's inbox.";
        $message_type = "success";
    }
}

if (isset($_POST['send_bulk_reminder'])) {
    $sender_type = 'admin';
    $sender_id = $_SESSION['admin_id'] ?? 'Admin';
    $receiver_type = 'student';
    $today_date = date("Y-m-d"); $time = date("H:i:s");
    
    $pending_res = $conn->query("SELECT Student_id, amount, category FROM Payment WHERE status = 'Pending'");
    $ins = $conn->prepare("INSERT INTO Message (sender_type, sender_id, receiver_type, receiver_id, subject_h, message, msg_date, msg_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $count = 0;
    while($p = $pending_res->fetch_assoc()) {
        $target_ht = $p['Student_id'];
        $amt = $p['amount'];
        $cat = $p['category'];
        $subject = "Official Reminder: Pending Dues ($cat)";
        $msg = "Dear Student, this is an official reminder that you have UNPAID dues of Rs " . number_format((float)$amt, 2) . " for $cat. Please submit your fee challan details to the Administration Office as soon as possible to avoid penalties.";
        
        $ins->bind_param("ssssssss", $sender_type, $sender_id, $receiver_type, $target_ht, $subject, $msg, $today_date, $time);
        $ins->execute();
        $count++;
    }
    $message = "Broadcasted $count due reminders directly into student inboxes.";
    $message_type = "success";
}
?>

<div class="glass-module shadow-lg animated fadeIn">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-edit mr-2 text-info"></i> Update Student Payment</h2>
        <span class="badge badge-info p-2 px-3 shadow-sm">Payment Reconciliation</span>
    </div>
    
    <div class="section-body p-4">
        <?php if ($message != ""): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4 shadow-sm border-<?php echo $message_type; ?>">
                <i class="fas fa-info-circle mr-2"></i> <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="p-4 rounded border h-100" style="background: rgba(0,0,0,0.02);">
                    <h6 class="font-weight-bold text-muted text-uppercase small mb-4">Verification Form</h6>
                    <form action="" method="post">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted text-uppercase">Student Hall Ticket</label>
                            <input type="text" name="hall_ticket" class="form-control form-control-lg" placeholder="Enter Hall Ticket Number" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted text-uppercase">Reference Number</label>
                            <input type="text" name="reference_no" class="form-control form-control-lg" placeholder="Official Reference ID / Trace #" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted text-uppercase">Date</label>
                            <input type="date" name="date" class="form-control form-control-lg">
                        </div>
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted text-uppercase">Category</label>
                            <select name="category" class="form-control form-control-lg" required>
                                <option value="">-- Select Category --</option>
                                <?php 
                                $cat_res = $conn->query("SELECT DISTINCT category FROM Payment WHERE category IS NOT NULL AND status = 'Pending'");
                                if ($cat_res && $cat_res->num_rows > 0) {
                                    while($c = $cat_res->fetch_assoc()) {
                                        echo "<option value='".htmlspecialchars($c['category'])."'>".htmlspecialchars($c['category'])."</option>";
                                    }
                                }
                                ?>
                                <option value="Mess Bill">Mess Bill</option>
                                <option value="Hostel Fee">Hostel Fee</option>
                                <option value="Miscellaneous">Miscellaneous</option>
                            </select>
                        </div>
                        <div class="form-group mb-4">
                            <label class="small font-weight-bold text-muted text-uppercase">Payment Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text bg-white">Rs</span></div>
                                <input type="number" step="0.01" name="amount" class="form-control form-control-lg" placeholder="0.00" required>
                            </div>
                        </div>
                        <button type="submit" name="update_challan" class="btn btn-info btn-block btn-lg font-weight-bold shadow-sm">
                            <i class="fas fa-check-double mr-2"></i> VERIFY AND UPDATE STATUS
                        </button>
                    </form>

                    <hr class="my-4 border-info">
                    <h6 class="font-weight-bold text-muted text-uppercase small mb-3">Bulk Verification (CSV Upload)</h6>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" name="challans_csv" accept=".csv" required id="csvUpload">
                            <label class="custom-file-label text-muted" for="csvUpload">Select Challans CSV...</label>
                        </div>
                        <div class="alert alert-light border small text-muted mb-3 py-2" style="font-size: 0.8rem; line-height: 1.5;">
                            <b>CSV Column Order Required:</b><br>
                            <code>Hall Ticket Number, Reference Number, Date, Category, Amount, Year</code>
                        </div>
                        <button type="submit" name="bulk_update_challan" onclick="return confirm('Process bulk CSV payment verifications? This mathematically lowers student dues.');" class="btn btn-outline-info btn-block font-weight-bold shadow-sm">
                            <i class="fas fa-file-csv mr-2"></i> PROCESS BULK CSV
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card border-info h-100 shadow-sm" style="background: rgba(0,188,212,0.03);">
                    <div class="card-body">
                        <h6 class="font-weight-bold text-info border-bottom pb-2 mb-3 small"><i class="fas fa-search-dollar mr-1"></i> Recent Pending Payments</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 small" style="font-size: 0.9rem;">
                                <thead>
                                    <tr class="bg-light"><th>Student Details</th><th>Amount</th><th>Category</th><th class="text-center">Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $query = "SELECT p.Student_id, p.amount, p.category, s.Fname, s.Lname, s.Dept, s.Academic_Year 
                                              FROM Payment p 
                                              LEFT JOIN Student s ON p.Student_id = s.Student_id 
                                              WHERE p.status = 'Pending' 
                                              ORDER BY p.payment_date DESC LIMIT 15";
                                    $res = $conn->query($query);
                                    if ($res && $res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $name = !empty($row['Fname']) ? trim($row['Fname'] . " " . $row['Lname']) : "";
                                            $info = !empty($row['Dept']) ? ($row['Dept'] . " " . $row['Academic_Year']) : "Unknown Registration";
                                            echo "<tr>
                                            <td class='align-middle border-top'>
                                                <div class='font-weight-bold text-info'>{$row['Student_id']}</div>
                                                <div class='text-muted' style='font-size: 0.8rem;'>{$name} <br> {$info}</div>
                                            </td>
                                            <td class='text-danger font-weight-bold align-middle border-top'>Rs ".number_format($row['amount'], 2)."</td>
                                            <td class='align-middle border-top'><small class='text-muted font-weight-bold text-uppercase'>{$row['category']}</small></td>
                                            <td class='align-middle border-top text-center'>
                                                <form method='POST' class='m-0 p-0'>
                                                    <input type='hidden' name='target_ht' value='{$row['Student_id']}'>
                                                    <input type='hidden' name='amt' value='{$row['amount']}'>
                                                    <input type='hidden' name='cat' value='{$row['category']}'>
                                                    <button type='submit' name='send_reminder' class='btn btn-warning btn-sm font-weight-bold shadow-sm px-3' style='font-size: 0.75rem;' title='Post Due Reminder to Student Inbox'>
                                                        POST MSG
                                                    </button>
                                                </form>
                                            </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-4 text-muted'>No pending records found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (isset($res) && $res->num_rows > 0): ?>
                        <div class="mt-3 pt-3 border-top text-right">
                            <form method="POST" class="m-0">
                                <button type="submit" name="send_bulk_reminder" onclick="return confirm('Send official due reminders to ALL pending students in the database?');" class="btn btn-outline-danger btn-sm font-weight-bold shadow-sm">
                                    <i class="fas fa-bullhorn mr-1"></i> BULK POST ALL REMINDERS
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-light border mt-4 small text-muted">
            <i class="fas fa-question-circle mr-1"></i> <strong>How it works:</strong> Match the bank teller details with the student's pending charge. Selecting the correct <b>Category</b> and <b>Amount</b> is essential for accurate ledger reconciliation. The <b>Reference Number</b> and <b>Date</b> will be recorded for future audit.
        </div>
    </div>
</div>

<script>
    if (document.getElementById('csvUpload')) {
        document.getElementById('csvUpload').onchange = function() {
            this.nextElementSibling.innerHTML = this.value.split('\\').pop();
        };
    }
</script>

<?php 
require 'admin_footer.php'; 
?>
