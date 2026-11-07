<?php 
require 'admin_header.php'; 
?>

<div class="glass-module shadow-lg animated fadeIn">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-file-invoice-dollar mr-2 text-info"></i> Final Bills & Attendance Mandates</h2>
        <span class="badge badge-info p-2 px-3 shadow-sm">Monthly Registry</span>
    </div>
    
    <div class="section-body p-4">
        <!-- Filters Area -->
        <form method="GET" action="admin_monthly_records.php" class="form-row mb-5 p-4 rounded shadow-sm" style="background: rgba(0,0,0,0.03); border: 1px solid #eee;">
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="font-weight-bold small text-muted text-uppercase">Specify Hostel</label>
                <select name="hostel_id" class="form-control form-control-lg custom-select">
                    <option value="">-- View All Hostels --</option>
                    <?php
                    $h_sql = "SELECT Hostel_id, Hostel_name FROM Hostel";
                    $h_res = $conn->query($h_sql);
                    $selected_hostel = $_GET['hostel_id'] ?? '';
                    while ($h_row = $h_res->fetch_assoc()) {
                        $sel = ($h_row['Hostel_id'] == $selected_hostel) ? 'selected' : '';
                        echo "<option value='".$h_row['Hostel_id']."' $sel>".htmlspecialchars($h_row['Hostel_name'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4 mb-3 mb-md-0">
                <label class="font-weight-bold small text-muted text-uppercase">Billing Month</label>
                <select name="month_filter" class="form-control form-control-lg custom-select">
                    <?php
                    $m_query = "SELECT DISTINCT DATE_FORMAT(date, '%M %Y') as myear FROM attendance ORDER BY MAX(date) DESC";
                    $mres = $conn->query($m_query);
                    $months = [];
                    while($mrow = $mres->fetch_assoc()) { $months[] = $mrow['myear']; }
                    if(empty($months)) { $months[] = date('F Y'); }
                    $selected_month = $_GET['month_filter'] ?? $months[0];
                    foreach($months as $m) {
                        $sel = ($m === $selected_month) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($m)."' $sel>".htmlspecialchars($m)."</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-info btn-lg btn-block font-weight-bold shadow-sm">
                    <i class="fas fa-filter mr-2"></i> FILTER RECORDS
                </button>
            </div>
        </form>

        <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2">Final Summaries for <?php echo htmlspecialchars($selected_month); ?></h5>
        
        <div class="table-responsive border rounded overflow-hidden shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="py-3 px-4">Hall Ticket</th>
                        <th class="py-3 px-4">Student Name</th>
                        <th class="py-3 px-4">Hostel</th>
                        <th class="py-3 px-4 text-center">Days Present</th>
                        <th class="py-3 px-4 text-right">Bills Generated (Total)</th>
                    </tr>
                </thead>
                <tbody style="background:#fff;">
                <?php
                if ($selected_month) {
                    $hostel_condition = ($selected_hostel != '') ? "AND s.Hostel_id = " . (int)$selected_hostel : "";
                    
                    $sql = "SELECT s.Hostel_id, s.Student_id, s.Fname, s.Lname, h.Hostel_name,
                            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days 
                            FROM Student s 
                            LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                            LEFT JOIN attendance a ON s.Student_id = a.Student_id AND DATE_FORMAT(a.date, '%M %Y') = ?
                            WHERE s.Hostel_id IS NOT NULL $hostel_condition
                            GROUP BY s.Student_id
                            ORDER BY s.Hostel_id, s.Student_id";
                            
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("s", $selected_month);
                        $stmt->execute();
                        $res = $stmt->get_result();

                        if ($res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $sid = $row['Student_id'];
                                $sname = $row['Fname'] . " " . $row['Lname'];
                                $pdays = $row['present_days'] ?? 0;

                                // Fetch total bills for this student this month
                                $tot_sql = "SELECT SUM(amount) as total FROM Payment WHERE Student_id = ? AND payment_date LIKE ?";
                                $m_start = date('Y-m', strtotime($selected_month)) . '%';
                                $stmt_tot = $conn->prepare($tot_sql);
                                $stmt_tot->bind_param("ss", $sid, $m_start);
                                $stmt_tot->execute();
                                $tot_res = $stmt_tot->get_result();
                                $total_billed = ($tot_row = $tot_res->fetch_assoc()) ? ($tot_row['total'] ?? 0) : 0;
                                
                                echo "<tr>";
                                echo "<td class='font-weight-bold px-4'>".htmlspecialchars($sid)."</td>";
                                echo "<td class='px-4'>".htmlspecialchars($sname)."</td>";
                                echo "<td class='px-4'><span class='small text-muted'>".htmlspecialchars($row['Hostel_name'])."</span></td>";
                                echo "<td class='text-center px-4'><span class='badge badge-pill badge-info px-4 py-1'>{$pdays} Days</span></td>";
                                echo "<td class='text-right px-4 font-weight-bold text-dark'>N" . number_format($total_billed, 2) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No entries found.</td></tr>";
                        }
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require 'admin_footer.php'; 
?>
