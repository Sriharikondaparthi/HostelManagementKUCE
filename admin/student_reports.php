<?php
require 'admin_header.php'; 

// Fetch distinct values for filters
$branches_res = $conn->query("SELECT DISTINCT Dept FROM Student WHERE Dept IS NOT NULL AND Dept != ''");
$branches = [];
while ($b = $branches_res->fetch_assoc()) $branches[] = $b['Dept'];

$semesters_res = $conn->query("SELECT DISTINCT Semester FROM Student WHERE Semester IS NOT NULL AND Semester != ''");
$semesters = [];
while ($s = $semesters_res->fetch_assoc()) $semesters[] = $s['Semester'];

$acad_years_res = $conn->query("SELECT DISTINCT Academic_Year FROM Student WHERE Academic_Year IS NOT NULL AND Academic_Year != ''");
$acad_years = [];
while ($ay = $acad_years_res->fetch_assoc()) $acad_years[] = $ay['Academic_Year'];

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$cur_yr = (int)date('Y');
$years = range($cur_yr - 5, $cur_yr + 5);

// User selections
$sel_student = $_GET['student_id'] ?? '';
$sel_branch = $_GET['branch'] ?? '';
$sel_semester = $_GET['semester'] ?? '';
$sel_acad_year = $_GET['acad_year'] ?? '';
$sel_month = $_GET['month'] ?? '';
$sel_year = $_GET['year'] ?? '';

// Base conditions for aggregated queries
$where_clauses = ["p.transaction_type = 'Charge'"];
$params = [];
$types = "";

if ($sel_student != '') {
    $where_clauses[] = "p.Student_id = ?";
    $params[] = $sel_student;
    $types .= "s";
}
if ($sel_branch != '') {
    $where_clauses[] = "s.Dept = ?";
    $params[] = $sel_branch;
    $types .= "s";
}
if ($sel_semester != '') {
    $where_clauses[] = "s.Semester = ?";
    $params[] = $sel_semester;
    $types .= "s";
}
if ($sel_acad_year != '') {
    $where_clauses[] = "s.Academic_Year = ?";
    $params[] = $sel_acad_year;
    $types .= "s";
}
if ($sel_month != '') {
    $where_clauses[] = "DATE_FORMAT(p.payment_date, '%M') = ?";
    $params[] = $sel_month;
    $types .= "s";
}
if ($sel_year != '') {
    $where_clauses[] = "DATE_FORMAT(p.payment_date, '%Y') = ?";
    $params[] = $sel_year;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Chart 1: Dues Status (Pie Chart)
$pie_labels = ['Cleared / Paid', 'Pending Dues'];
$pie_data = [0, 0];
$pie_sql = "SELECT p.status, SUM(p.amount) as total 
            FROM Payment p 
            JOIN Student s ON p.Student_id = s.Student_id 
            WHERE $where_sql 
            GROUP BY p.status";
$stmt_pie = $conn->prepare($pie_sql);
if ($types != "") {
    $stmt_pie->bind_param($types, ...$params);
}
$stmt_pie->execute();
$pie_res = $stmt_pie->get_result();
while($row = $pie_res->fetch_assoc()) {
    if (strtolower($row['status']) == 'paid') {
        $pie_data[0] += $row['total'];
    } else {
        $pie_data[1] += $row['total'];
    }
}

// Chart 2: Monthly Pending Histogram (Histogram config)
$hist_labels = [];
$hist_data = [];
$hist_sql = "SELECT DATE_FORMAT(p.payment_date, '%Y-%m') as ym_sort, DATE_FORMAT(p.payment_date, '%M %Y') as month_year, SUM(p.amount) as total 
             FROM Payment p JOIN Student s ON p.Student_id = s.Student_id 
             WHERE p.status='Pending' AND $where_sql 
             GROUP BY month_year, ym_sort ORDER BY ym_sort ASC LIMIT 12";
$stmt_hist = $conn->prepare($hist_sql);
if ($types != "") {
    $stmt_hist->bind_param($types, ...$params);
}
$stmt_hist->execute();
$hist_res = $stmt_hist->get_result();
while($row = $hist_res->fetch_assoc()) {
    $hist_labels[] = $row['month_year'];
    $hist_data[] = $row['total'];
}

// Chart 3: Comparative Breakdown (Bar Chart) 
// - If branch selected, break down by Semester
// - If Academic Year selected, break down by Branch
// - Otherwise break down by Branch (default)
$compare_labels = [];
$compare_data = [];
$groupby = "s.Dept";
$label_prefix = "";
$compare_title = "Branch-wise Pending Dues Comparison";

if ($sel_branch != '' && $sel_semester == '') {
    $groupby = "s.Semester";
    $label_prefix = "Sem ";
    $compare_title = "Semester-wise Dues for " . htmlspecialchars($sel_branch);
} elseif ($sel_semester != '' && $sel_branch == '') {
    $groupby = "s.Dept";
    $compare_title = "Branch-wise Dues for Semester " . htmlspecialchars($sel_semester);
} elseif ($sel_acad_year != '') {
    $groupby = "s.Semester"; // Year wise breakdown across sem
    $label_prefix = "Sem ";
    $compare_title = "Semester-wise Dues for Academic Year " . htmlspecialchars($sel_acad_year);
}

// Always do comparison for Pending only
$comp_where = $where_sql . " AND p.status = 'Pending'";
$comp_sql = "SELECT $groupby as grouping_val, SUM(p.amount) as pending_amount 
             FROM Payment p 
             JOIN Student s ON p.Student_id = s.Student_id 
             WHERE $comp_where 
             GROUP BY $groupby HAVING pending_amount > 0 ORDER BY pending_amount DESC";

$stmt_comp = $conn->prepare($comp_sql);
if ($types != "") $stmt_comp->bind_param($types, ...$params);
$stmt_comp->execute();
$comp_res = $stmt_comp->get_result();
while($row = $comp_res->fetch_assoc()) {
    $compare_labels[] = $label_prefix . ($row['grouping_val'] ?: 'Unassigned');
    $compare_data[] = $row['pending_amount'];
}

// Detailed Data Grid (Top Debtors matching filters)
$debt_where = $where_sql . " AND p.status = 'Pending'";
$debtors_sql = "SELECT s.Student_id, s.Fname, s.Lname, s.Dept, s.Semester, s.Academic_Year, SUM(p.amount) as pending_dues 
                FROM Payment p JOIN Student s ON p.Student_id = s.Student_id 
                WHERE $debt_where 
                GROUP BY s.Student_id HAVING pending_dues > 0 ORDER BY pending_dues DESC LIMIT 15";
$stmt_debt = $conn->prepare($debtors_sql);
if ($types != "") $stmt_debt->bind_param($types, ...$params);
$stmt_debt->execute();
$debtors_res = $stmt_debt->get_result();

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="glass-module shadow-lg animated fadeInDown mb-5">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-chart-pie mr-2 text-info"></i> Comprehensive Student Financial Report</h2>
    </div>
    
    <!-- Filters -->
    <div class="p-4 bg-white">
        <form method="GET">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase mb-2">Student ID</label>
                    <input type="text" name="student_id" class="form-control" placeholder="Individual ID" value="<?php echo htmlspecialchars($sel_student); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase mb-2">Branch</label>
                    <select name="branch" class="form-control custom-select bg-light">
                        <option value="">All Branches</option>
                        <?php foreach($branches as $b): ?>
                            <option value="<?php echo htmlspecialchars($b); ?>" <?php if($b == $sel_branch) echo 'selected'; ?>><?php echo htmlspecialchars($b); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase mb-2">Semester</label>
                    <select name="semester" class="form-control custom-select bg-light">
                        <option value="">All Sems</option>
                        <?php foreach($semesters as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php if($s == $sel_semester) echo 'selected'; ?>>Sem <?php echo htmlspecialchars($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase mb-2">Academic Year</label>
                    <select name="acad_year" class="form-control custom-select bg-light">
                        <option value="">All Years</option>
                        <?php foreach($acad_years as $ay): ?>
                            <option value="<?php echo htmlspecialchars($ay); ?>" <?php if($ay == $sel_acad_year) echo 'selected'; ?>><?php echo htmlspecialchars($ay); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase mb-2">Bill Month</label>
                    <select name="month" class="form-control custom-select bg-light">
                        <option value="">All Months</option>
                        <?php foreach($months as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" <?php if($m == $sel_month) echo 'selected'; ?>><?php echo htmlspecialchars($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase mb-2">Bill Year</label>
                    <select name="year" class="form-control custom-select bg-light">
                        <option value="">All Years</option>
                        <?php foreach($years as $y): ?>
                            <option value="<?php echo htmlspecialchars($y); ?>" <?php if((string)$y === (string)$sel_year) echo 'selected'; ?>><?php echo htmlspecialchars($y); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-info px-5 font-weight-bold shadow-sm"><i class="fas fa-sync-alt mr-2"></i> RENDER ALL CHARTS</button>
                    <a href="student_reports.php" class="btn btn-outline-secondary ml-2 font-weight-bold">CLEAR</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row mb-5">
    <!-- Pie Chart -->
    <div class="col-lg-4 mb-4">
        <div class="glass-module p-4 h-100 shadow border-top-0 bg-white" style="border-top: 4px solid #00bcd4 !important;">
            <h5 class="font-weight-bold text-dark text-center mb-4">Fee Clearance Proportion (Pie Chart)</h5>
            <?php if (array_sum($pie_data) == 0): ?>
                <div class="text-center text-muted py-5 border rounded bg-light"><i class="fas fa-info-circle fa-2x mb-3 opacity-2"></i><br>No exact matches for these filters.</div>
            <?php else: ?>
                <canvas id="pieChart"></canvas>
                <div class="text-center mt-4">
                    <h5 class="text-danger font-weight-bold mb-0">Rs <?php echo number_format($pie_data[1], 2); ?></h5>
                    <small class="text-muted font-weight-bold text-uppercase">Total Evaluated Pending Debt</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bar and Histogram Charts -->
    <div class="col-lg-8 mb-4 d-flex flex-column">
        <!-- Histogram -->
        <div class="glass-module p-4 shadow flex-fill mb-4 bg-white">
            <h5 class="font-weight-bold text-dark mb-4">Monthly Dues Distribution (Histogram)</h5>
            <?php if(empty($hist_data)): ?>
                <div class="text-center text-muted py-4 border rounded bg-light">Empty trajectory path. Try relaxing month filters.</div>
            <?php else: ?>
                <canvas id="histChart" style="max-height: 250px;"></canvas>
            <?php endif; ?>
        </div>
        
        <!-- Bar Chart -->
        <div class="glass-module p-4 shadow flex-fill bg-white">
            <h5 class="font-weight-bold text-dark mb-4"><?php echo $compare_title; ?> (Bar Chart)</h5>
            <?php if(empty($compare_data)): ?>
                <div class="text-center text-muted py-4 border rounded bg-light">No comparative data found.</div>
            <?php else: ?>
                <canvas id="compareBar" style="max-height: 250px;"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="glass-module shadow-lg mb-5 border-0">
    <div class="px-4 py-3 bg-dark text-white rounded-top shadow-sm d-flex justify-content-between align-items-center">
        <h6 class="mb-0 font-weight-bold"><i class="fas fa-list-ol mr-2 text-info"></i> Individual Student Ledgers</h6>
        <span class="badge badge-info">Matches Filter Criteria</span>
    </div>
    <div class="table-responsive bg-white">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-4">Student Name</th>
                    <th>Hall Ticket (Individual)</th>
                    <th>Branch</th>
                    <th class="text-center">Year & Sem</th>
                    <th class="text-right px-4">Calculated Impact (Dues)</th>
                </tr>
            </thead>
            <tbody>
                <?php while($debtor = $debtors_res->fetch_assoc()): ?>
                <tr>
                    <td class="px-4 font-weight-bold"><?php echo htmlspecialchars($debtor['Fname'] . ' ' . $debtor['Lname']); ?></td>
                    <td><?php echo htmlspecialchars($debtor['Student_id']); ?></td>
                    <td><span class="badge badge-light border border-secondary"><?php echo htmlspecialchars($debtor['Dept']); ?></span></td>
                    <td class="text-center"><?php echo htmlspecialchars($debtor['Academic_Year'] ?: 'N/A'); ?> / Sem <?php echo htmlspecialchars($debtor['Semester']); ?></td>
                    <td class="text-right px-4 font-weight-bold text-danger">Rs <?php echo number_format($debtor['pending_dues'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if($debtors_res->num_rows == 0): ?>
                    <tr><td colspan="5" class="text-center py-5 text-success font-weight-bold"><i class="fas fa-check-circle mr-2"></i> Phenomenal! All ledgers are perfectly squared for these selections.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    Chart.defaults.font.family = "'Open Sans', sans-serif";
    Chart.defaults.color = '#666';

    <?php if (array_sum($pie_data) > 0): ?>
    // 1. Pie Chart
    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($pie_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($pie_data); ?>,
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    <?php endif; ?>

    <?php if(!empty($hist_data)): ?>
    // 2. Histogram (Bar with thick pillars)
    new Chart(document.getElementById('histChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($hist_labels); ?>,
            datasets: [{
                label: 'Volume of Pending Dues (Rs)',
                data: <?php echo json_encode($hist_data); ?>,
                backgroundColor: '#17a2b8',
                borderColor: '#117a8b',
                borderWidth: 1,
                barPercentage: 1.0,
                categoryPercentage: 1.0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($compare_data)): ?>
    // 3. Bar Chart (Comparative)
    new Chart(document.getElementById('compareBar'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($compare_labels); ?>,
            datasets: [{
                label: 'Owed Amount (Rs)',
                data: <?php echo json_encode($compare_data); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y', // Makes it Horizontal Bar Chart for variety
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                y: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
</script>

<?php require 'admin_footer.php'; ?>
