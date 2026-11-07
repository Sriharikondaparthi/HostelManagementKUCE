<?php 
require 'admin_header.php'; 

$message = "";
$message_type = "";

// 1. Setup Period Selection
$cur_yr = (int)date('Y');
$available_years = range($cur_yr - 5, $cur_yr + 5);
$months_list = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

$cur_month = date('F');
$cur_year = (string)$cur_yr;

// Support both GET (selection) and POST (execution)
$selected_month = $_POST['month'] ?? $_GET['month'] ?? $cur_month;
$selected_year = $_POST['year'] ?? $_GET['year'] ?? $cur_year;

// 2. Aggregate Data for the Selected Period
// A. Monthly Expenditure (Firms)
$q_firms = "SELECT sector, SUM(amount) as total FROM firms WHERE billing_month = ? AND billing_year = ? GROUP BY sector";
$stmt_f = $conn->prepare($q_firms);
$stmt_f->bind_param("ss", $selected_month, $selected_year);
$stmt_f->execute();
$res_f = $stmt_f->get_result();

$expenditure_data = [];
$total_expenditure = 0;
while($row = $res_f->fetch_assoc()) {
    $expenditure_data[$row['sector']] = $row['total'];
    $total_expenditure += $row['total'];
}

// B. Total Mandates (Attendance Days) and Student Count
$q_std = "SELECT COUNT(DISTINCT hall_ticket) as std_count, SUM(days_present) as total_attendance FROM mandates WHERE billing_month = ? AND billing_year = ?";
$stmt_s = $conn->prepare($q_std);
$stmt_s->bind_param("si", $selected_month, $selected_year);
$stmt_s->execute();
$mandate_res = $stmt_s->get_result()->fetch_assoc();
$total_students = $mandate_res['std_count'] ?? 0;
$total_attendance_days = $mandate_res['total_attendance'] ?? 0;

// C. Check if already billed
$bill_cat = "Mess Bill - $selected_month $selected_year";
$q_check = "SELECT COUNT(*) as billed FROM Payment WHERE category = ?";
$stmt_c = $conn->prepare($q_check);
$stmt_c->bind_param("s", $bill_cat);
$stmt_c->execute();
$is_already_billed = ($stmt_c->get_result()->fetch_assoc()['billed'] > 0);

// 3. Calculation Variables
$individual_daily_rate = ($total_attendance_days > 0) ? ($total_expenditure / $total_attendance_days) : 0;

// 4. Execution: Post to Student Ledgers
if (isset($_POST['post_bills']) && !$is_already_billed) {
    if ($individual_daily_rate > 0) {
        $conn->begin_transaction();
        try {
            $billing_date = date('Y-m-d');
            $q_mandates = "SELECT hall_ticket, days_present, student_name FROM mandates WHERE billing_month = ? AND billing_year = ?";
            $st_m = $conn->prepare($q_mandates);
            $st_m->bind_param("si", $selected_month, $selected_year);
            $st_m->execute();
            $res_m = $st_m->get_result();

            $st_p = $conn->prepare("INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Pending', 'Charge', ?)");
            
            $posted_count = 0;
            while ($student = $res_m->fetch_assoc()) {
                $ht = $student['hall_ticket'];
                $student_bill = $student['days_present'] * $individual_daily_rate;

                // A. Check if student exists in main directory (Required by Foreign Key)
                $check_s = $conn->prepare("SELECT Student_id FROM student WHERE Student_id = ?");
                $check_s->bind_param("s", $ht);
                $check_s->execute();
                if ($check_s->get_result()->num_rows == 0) {
                    $ins_s = $conn->prepare("INSERT INTO student (Student_id, Fname, Lname, Mob_no, Dept, Semester, Pwd) VALUES (?, ?, 'User', '0000000000', 'Automatic', 'N/A', ?)");
                    $fname = $student['student_name'];
                    $def_pwd = $ht; 
                    $ins_s->bind_param("sss", $ht, $fname, $def_pwd);
                    $ins_s->execute();
                }

                // B. Insert Bill
                if ($student_bill > 0) {
                    $st_p->bind_param("sdss", $ht, $student_bill, $billing_date, $bill_cat);
                    $st_p->execute();
                    $posted_count++;
                }
            }
            $conn->commit();
            $message = "Successfully posted $posted_count bills to student ledgers for $selected_month $selected_year.";
            $message_type = "success";
            $is_already_billed = true;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error posting bills: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<div class="glass-module shadow-lg animated fadeIn mb-4">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-calculator mr-2 text-info"></i> Mess Bill Generation Hub</h2>
        <span class="badge badge-info p-2 px-3 shadow-sm"><?php echo "$selected_month $selected_year"; ?></span>
    </div>
    <div class="p-4">
        <form method="GET" action="generate_bills.php" class="row">
            <div class="col-md-5">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Billing Month</label>
                <select name="month" class="form-control form-control-lg custom-select">
                    <?php foreach($months_list as $m): ?>
                        <option value="<?php echo $m; ?>" <?php if($m == $selected_month) echo 'selected'; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Billing Year</label>
                <select name="year" class="form-control form-control-lg custom-select">
                    <?php foreach($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php if($y == $selected_year) echo 'selected'; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-info btn-lg btn-block font-weight-bold shadow-sm">LOAD PERIOD</button>
            </div>
        </form>
    </div>
</div>

<?php if ($message != ""): ?>
    <div class="alert alert-<?php echo $message_type; ?> shadow-sm mb-4 border-left-large"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Section 1: Expenditure Summary -->
    <div class="col-lg-6">
        <div class="glass-module shadow h-100">
            <div class="p-3 border-bottom bg-light">
                <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-chart-pie mr-2 text-info"></i> Expenditure Summary</h6>
            </div>
            <div class="p-4">
                <table class="table table-sm table-borderless mb-0">
                    <?php if (empty($expenditure_data)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">No firm data found for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach($expenditure_data as $sector => $amount): ?>
                            <tr>
                                <td class="py-2 text-muted"><?php echo $sector; ?></td>
                                <td class="py-2 text-right font-weight-bold">Rs <?php echo number_format($amount, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="border-top">
                            <td class="pt-3 font-weight-bold text-dark h5">Monthly Total</td>
                            <td class="pt-3 text-right font-weight-bold text-info h5">Rs <?php echo number_format($total_expenditure, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Calculation Panel -->
    <div class="col-lg-6">
        <div class="glass-module shadow h-100 border-info" style="background: #f8fdff;">
            <div class="p-3 border-bottom bg-info text-white">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-sliders-h mr-2"></i> Bill Configuration</h6>
            </div>
            <div class="p-4">
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="p-3 bg-white border rounded">
                            <small class="text-muted text-uppercase d-block mb-1">Total Students</small>
                            <span class="h4 font-weight-bold text-dark"><?php echo $total_students; ?></span>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="p-3 bg-white border rounded">
                            <small class="text-muted text-uppercase d-block mb-1">Total Mandate Days</small>
                            <span class="h4 font-weight-bold text-info"><?php echo $total_attendance_days; ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-4 bg-white border border-info rounded shadow-sm text-center">
                            <small class="text-info text-uppercase font-weight-bold d-block mb-2">Individual Daily Rate</small>
                            <div class="display-4 font-weight-bold text-dark">Rs <?php echo number_format($individual_daily_rate, 2); ?></div>
                            <p class="small text-muted mb-0">Calculated as Total Expense ÷ Total Mandate Days</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Billing Preview & Posting -->
<div class="glass-module shadow-lg mt-5 overflow-hidden">
    <div class="p-4 bg-light d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h5 class="mb-1 font-weight-bold text-dark">Student Billing Preview</h5>
            <p class="small text-muted mb-0">Projections for <b><?php echo "$selected_month $selected_year"; ?></b> based on attendance records.</p>
        </div>
        <?php if (!$is_already_billed && $total_students > 0 && $individual_daily_rate > 0): ?>
            <form method="POST">
                <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                <button type="submit" name="post_bills" class="btn btn-success btn-lg px-5 font-weight-bold shadow-lg animated pulse infinite" onclick="return confirm('Finalize and send all bills to student ledgers for <?php echo "$selected_month $selected_year"; ?>?')">
                    <i class="fas fa-check-circle mr-2"></i> POST ALL BILLS
                </button>
            </form>
        <?php elseif ($is_already_billed): ?>
            <span class="badge badge-pill badge-secondary p-3"><i class="fas fa-lock mr-2"></i> ALREADY BILLED FOR THIS PERIOD</span>
        <?php endif; ?>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">S.No</th>
                        <th class="px-4 py-3">Student Name</th>
                        <th class="px-4 py-3">Hall Ticket Number</th>
                        <th class="px-4 py-3 text-center">Days Present</th>
                        <th class="px-4 py-3 text-right">Individual Bill (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $q_mandates = "SELECT m.hall_ticket, COALESCE(s.Fname, m.student_name) as student_name, m.days_present FROM mandates m LEFT JOIN Student s ON m.hall_ticket = s.Student_id WHERE m.billing_month = ? AND m.billing_year = ? ORDER BY s.internal_id ASC, m.id ASC";
                    $st_m = $conn->prepare($q_mandates);
                    $st_m->bind_param("si", $selected_month, $selected_year);
                    $st_m->execute();
                    $res_m = $st_m->get_result();

                    if ($res_m->num_rows > 0) {
                        $sno = 1;
                        while($row = $res_m->fetch_assoc()) {
                            $student_bill = $row['days_present'] * $individual_daily_rate;
                            echo "<tr>
                                    <td class='px-4 py-3 text-muted small'>$sno</td>
                                    <td class='px-4 py-3 font-weight-bold'>".htmlspecialchars($row['student_name'])."</td>
                                    <td class='px-4 py-3'>".htmlspecialchars($row['hall_ticket'])."</td>
                                    <td class='px-4 py-3 text-center'><span class='badge badge-light p-2 px-3 border'>{$row['days_present']} days</span></td>
                                    <td class='px-4 py-3 text-right font-weight-bold text-info'>Rs ".number_format($student_bill, 2)."</td>
                                  </tr>";
                            $sno++;
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No attendance mandates found for this period.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require 'admin_footer.php'; ?>
