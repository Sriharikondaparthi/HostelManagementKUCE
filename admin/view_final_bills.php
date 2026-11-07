<?php 
require 'admin_header.php'; 

// 1. Setup Period Selection
$cur_yr = (int)date('Y');
$available_years = array_reverse(range($cur_yr - 5, $cur_yr + 5));
$months_list = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

$selected_month = $_GET['month'] ?? date('F');
$selected_year = $_GET['year'] ?? (string)$cur_yr;

$bill_cat = "Mess Bill - $selected_month $selected_year";
?>

<div class="glass-module shadow-lg animated fadeInDown mb-4">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-history mr-2 text-info"></i> Final Bills & Notification Hub</h2>
        <span class="badge badge-info p-2 px-3 shadow-sm">Monthly Registry</span>
    </div>
    <div class="p-4">
        <form method="GET" action="view_final_bills.php" class="row">
            <div class="col-md-5">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Audit Month</label>
                <select name="month" class="form-control form-control-lg custom-select">
                    <?php foreach($months_list as $m): ?>
                        <option value="<?php echo $m; ?>" <?php if($m == $selected_month) echo 'selected'; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Audit Year</label>
                <select name="year" class="form-control form-control-lg custom-select">
                    <?php foreach($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php if($y == $selected_year) echo 'selected'; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-info btn-lg btn-block font-weight-bold shadow-sm">VIEW ARCHIVE</button>
            </div>
        </form>
    </div>
</div>

<div class="glass-module shadow-lg animated fadeInUp">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h5 class="mb-0 font-weight-bold text-dark d-inline-block">Finalized Bills Summary</h5>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark ml-3 shadow-sm d-print-none"><i class="fas fa-print mr-1"></i> Print Report</button>
            <p class="small text-muted mb-0 d-print-none">Monthly audit and student notifications for <b><?php echo "$selected_month $selected_year"; ?></b>.</p>
        </div>
        <span class="badge badge-pill badge-info px-4 py-2 shadow-sm font-weight-bold"><?php echo $bill_cat; ?></span>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3" style="width: 70px;">S.No</th>
                        <th class="px-4 py-3">Student Name</th>
                        <th class="px-4 py-3">Hall Ticket Number</th>
                        <th class="px-4 py-3 text-center">Days Present</th>
                        <th class="px-4 py-3 text-right">Individual Bill (Rs)</th>
                        <th class="px-4 py-3 text-right">Pending Months & Dues</th>
                        <th class="px-4 py-3 text-right text-info">Total Bill (Rs)</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sql = "SELECT p.Student_id, s.Fname, s.Lname, s.Mob_no, m.days_present, m.student_name as mandate_name, p.amount 
                            FROM payment p
                            LEFT JOIN student s ON p.Student_id = s.Student_id
                            JOIN mandates m ON p.Student_id = m.hall_ticket 
                            WHERE p.category = ? 
                            AND m.billing_month = ? AND m.billing_year = ?
                            ORDER BY s.internal_id ASC, m.id ASC";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $bill_cat, $selected_month, $selected_year);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $sno = 1;
                        while($row = $result->fetch_assoc()) {
                            // Use Name from mandates if student profile doesn't exist yet
                            $full_name = (!empty($row['Fname'])) ? ($row['Fname'] . " " . $row['Lname']) : $row['mandate_name'];
                            $phone = $row['Mob_no'] ?? '';
                            $days = $row['days_present'] > 0 ? $row['days_present'] : 1;
                            $daily_rate = $row['amount'] / $days;

                            $wa_link = "#";
                            $can_send = false;

                            // Calculate Previous Dues with specific months
                            $q_dues = "SELECT category, amount FROM Payment WHERE Student_id = ? AND status = 'Pending' AND category != ? AND transaction_type = 'Charge'";
                            $st_dues = $conn->prepare($q_dues);
                            $st_dues->bind_param("ss", $row['Student_id'], $bill_cat);
                            $st_dues->execute();
                            $res_dues = $st_dues->get_result();
                            
                            $previous_dues = 0;
                            $pending_months = [];
                            while($due_row = $res_dues->fetch_assoc()) {
                                $previous_dues += $due_row['amount'];
                                $month_name = str_replace("Mess Bill - ", "", $due_row['category']);
                                $pending_months[] = "<span class='text-danger'>$month_name (Rs " . number_format($due_row['amount'], 2) . ")</span>";
                            }
                            
                            $total_payable = $row['amount'] + $previous_dues;
                            $pending_display = empty($pending_months) ? "<span class='text-muted small'>None</span>" : implode("<br>", $pending_months);

                            if (!empty($phone)) {
                                // Clean phone number for WhatsApp
                                $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                                if (strlen($clean_phone) == 10) $clean_phone = "91" . $clean_phone; 

                                // Encode Message
                                $msg_pending_list = empty($pending_months) ? "None" : str_replace("<span class='text-danger'>", "", str_replace("</span>", "", implode(", ", $pending_months)));
                                $msg = "Hello $full_name, this is from Hostel Admin. Your mess bill for $selected_month $selected_year is ready. \nIndividual Bill: Rs " . number_format($row['amount'], 2) . "\nPending Months: $msg_pending_list \nTOTAL BALANCE DUE: Rs " . number_format($total_payable, 2) . "\nPlease clear your dues. Thank you.";
                                $wa_link = "https://api.whatsapp.com/send?phone=" . $clean_phone . "&text=" . urlencode($msg);
                                $can_send = true;
                            }

                            $action_btn = "";
                            if ($can_send) {
                                $action_btn = "<a href='$wa_link' target='_blank' class='btn btn-success btn-sm px-3 shadow-sm'>
                                                    <i class='fab fa-whatsapp mr-1'></i> SEND
                                                </a>";
                            } else {
                                $action_btn = "<button class='btn btn-light btn-sm text-muted border' title='No mobile number found in profile' disabled>
                                                    <i class='fas fa-phone-slash mr-1'></i> NO PHONE
                                                </button>";
                            }

                            echo "<tr>
                                    <td class='px-4 py-3 text-muted small'>$sno</td>
                                    <td class='px-4 py-3 font-weight-bold'>$full_name</td>
                                    <td class='px-4 py-3 font-weight-bold text-info'>{$row['Student_id']}</td>
                                    <td class='px-4 py-3 text-center'><span class='badge badge-light border px-3 py-2'>{$row['days_present']} days</span></td>
                                    <td class='px-4 py-3 text-right font-weight-bold text-dark'>".number_format($row['amount'], 2)."</td>
                                    <td class='px-4 py-3 text-right font-weight-bold small'>$pending_display</td>
                                    <td class='px-4 py-3 text-right font-weight-bold text-info' style='font-size: 1.1em;'>Rs ".number_format($total_payable, 2)."</td>
                                    <td class='px-4 py-3 text-center d-print-none'>$action_btn</td>
                                  </tr>";
                            $sno++;
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center py-5 text-muted'>No finalized bills found for this period. Please generate bills first.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4 alert alert-light border shadow-sm small">
    <i class="fas fa-info-circle text-info mr-2"></i> <b>Billing Note:</b> The data above is a combined projection of the finalized Mess Charges and the uploaded Attendance Records for the selected audit period.
</div>

<?php require 'admin_footer.php'; ?>
