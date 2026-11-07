<?php
require 'admin_header.php'; 

// Pre-fetch dimension filters
$firms_res = $conn->query("SELECT DISTINCT firm_name FROM firms WHERE firm_name IS NOT NULL AND firm_name != ''");
$all_firms = [];
while ($f = $firms_res->fetch_assoc()) $all_firms[] = $f['firm_name'];

$months_res = $conn->query("SELECT DISTINCT billing_month FROM firms WHERE billing_month IS NOT NULL AND billing_month != ''");
$all_months = [];
while ($m = $months_res->fetch_assoc()) $all_months[] = $m['billing_month'];

$years_res = $conn->query("SELECT DISTINCT billing_year FROM firms WHERE billing_year IS NOT NULL AND billing_year != '' ORDER BY billing_year DESC");
$all_years = [];
while ($y = $years_res->fetch_assoc()) $all_years[] = $y['billing_year'];

// Selection Values
$sel_firm = $_GET['firm_name'] ?? '';
$sel_month = $_GET['month'] ?? '';
$sel_year = $_GET['year'] ?? date('Y');

// Base conditions for aggregated queries
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($sel_firm != '') {
    $where_clauses[] = "f.firm_name = ?";
    $params[] = $sel_firm;
    $types .= "s";
}
if ($sel_month != '') {
    $where_clauses[] = "f.billing_month = ?";
    $params[] = $sel_month;
    $types .= "s";
}
if ($sel_year != '') {
    $where_clauses[] = "f.billing_year = ?";
    $params[] = $sel_year;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Chart 1: Sector-wise Expense Distribution (Pie Chart)
$sector_labels = [];
$sector_data = [];
$sector_sql = "SELECT f.sector, SUM(f.amount) as total FROM firms f WHERE $where_sql GROUP BY f.sector HAVING total > 0";
$stmt_sec = $conn->prepare($sector_sql);
if ($types != '') $stmt_sec->bind_param($types, ...$params);
$stmt_sec->execute();
$sec_res = $stmt_sec->get_result();
while ($row = $sec_res->fetch_assoc()) {
    $sector_labels[] = $row['sector'] ?: 'Uncategorized';
    $sector_data[] = $row['total'];
}

// Chart 2: Periodic Monthly Trend (Histogram / Bar)
$hist_labels = [];
$hist_data = [];
// For trend, if a specific month is selected, this chart might just be one bar. 
// So let's ignore the month filter for the trend chart if we want to see the year's trend.
$trend_where = ["1=1"];
$t_params = [];
$t_types = "";

if ($sel_firm != '') {
    $trend_where[] = "f.firm_name = ?";
    $t_params[] = $sel_firm;
    $t_types .= "s";
}
if ($sel_year != '') {
    $trend_where[] = "f.billing_year = ?";
    $t_params[] = $sel_year;
    $t_types .= "s";
}
$t_where_sql = implode(" AND ", $trend_where);

$trend_sql = "SELECT f.billing_month, SUM(f.amount) as total FROM firms f WHERE $t_where_sql GROUP BY f.billing_month ORDER BY FIELD(f.billing_month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')";
$stmt_trend = $conn->prepare($trend_sql);
if ($t_types != '') $stmt_trend->bind_param($t_types, ...$t_params);
$stmt_trend->execute();
$trend_res = $stmt_trend->get_result();
while ($row = $trend_res->fetch_assoc()) {
    $hist_labels[] = $row['billing_month'];
    $hist_data[] = $row['total'];
}

// Chart 3: Firm-wise distribution (if bulk is selected)
$firm_bar_labels = [];
$firm_bar_data = [];
if ($sel_firm == '') {
    $fb_sql = "SELECT f.firm_name, SUM(f.amount) as total FROM firms f WHERE $where_sql GROUP BY f.firm_name HAVING total > 0 ORDER BY total DESC LIMIT 15";
    $stmt_fb = $conn->prepare($fb_sql);
    if ($types != '') $stmt_fb->bind_param($types, ...$params);
    $stmt_fb->execute();
    $fb_res = $stmt_fb->get_result();
    while ($row = $fb_res->fetch_assoc()) {
        $firm_bar_labels[] = substr($row['firm_name'], 0, 15) . '...';
        $firm_bar_data[] = $row['total'];
    }
}

// Detailed Data Table
$dt_sql = "SELECT firm_name, sector, billing_month, billing_year, SUM(amount) as sum_amt FROM firms f WHERE $where_sql GROUP BY firm_name, sector, billing_month, billing_year ORDER BY sum_amt DESC LIMIT 50";
$stmt_dt = $conn->prepare($dt_sql);
if ($types != '') $stmt_dt->bind_param($types, ...$params);
$stmt_dt->execute();
$dt_res = $stmt_dt->get_result();

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="glass-module shadow-lg animated fadeInDown mb-5">
    <div class="px-4 py-3 bg-dark d-flex justify-content-between align-items-center rounded-top shadow-sm">
        <h2 class="h5 mb-0 text-white font-weight-bold"><i class="fas fa-building mr-2 text-warning"></i> Firm Expenditure Registry</h2>
    </div>
    
    <div class="p-4 bg-white" style="border-bottom: 2px solid #ddd;">
        <form method="GET" class="row">
            <div class="col-md-3 mb-3">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Firm Profile (Individual / Bulk)</label>
                <select name="firm_name" class="form-control custom-select">
                    <option value="">Bulk (All Firms)</option>
                    <?php foreach($all_firms as $f): ?>
                        <option value="<?php echo htmlspecialchars($f); ?>" <?php if($f == $sel_firm) echo 'selected'; ?>><?php echo htmlspecialchars($f); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Billing Month</label>
                <select name="month" class="form-control custom-select">
                    <option value="">All Months (Yearly View)</option>
                    <?php foreach($all_months as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>" <?php if($m == $sel_month) echo 'selected'; ?>><?php echo htmlspecialchars($m); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Billing Year</label>
                <select name="year" class="form-control custom-select">
                    <option value="">All Years</option>
                    <?php foreach($all_years as $y): ?>
                        <option value="<?php echo htmlspecialchars($y); ?>" <?php if((string)$y === (string)$sel_year) echo 'selected'; ?>><?php echo htmlspecialchars($y); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end mb-3">
                <button type="submit" class="btn btn-warning btn-block font-weight-bold text-dark shadow-sm"><i class="fas fa-filter mr-2"></i> RENDER CHARTS</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-5">
    <!-- Pie Chart: Sectors -->
    <div class="col-lg-4 mb-4">
        <div class="glass-module p-4 h-100 shadow bg-white border-top-0" style="border-top: 4px solid #ffc107 !important;">
            <h5 class="font-weight-bold text-dark text-center mb-4">Category Breakdown</h5>
            <?php if(empty($sector_data)): ?>
                <div class="text-center text-muted py-5"><i class="fas fa-folder-open fa-2x mb-3 opacity-2"></i><br>No firm expenditures logged.</div>
            <?php else: ?>
                <canvas id="sectorPie"></canvas>
                <div class="text-center mt-4 pt-3 border-top">
                    <h5 class="text-warning font-weight-bold mb-0 text-dark">Rs <?php echo number_format(array_sum($sector_data), 2); ?></h5>
                    <small class="text-muted font-weight-bold text-uppercase">Total Processed Billed Amount</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-8 mb-4 d-flex flex-column">
        <!-- Histogram: Periodic Trend -->
        <div class="glass-module p-4 shadow flex-fill bg-white mb-4">
            <h5 class="font-weight-bold text-dark mb-4">Firm Expense Trajectory (<?php echo htmlspecialchars($sel_year ?: 'All Time'); ?>)</h5>
            <?php if(empty($hist_data)): ?>
                <div class="text-center text-muted py-4">Not enough data to calculate trajectory.</div>
            <?php else: ?>
                <canvas id="trendHist" style="max-height: 250px;"></canvas>
            <?php endif; ?>
        </div>
        
        <!-- Individual Bulk Distribution -->
        <?php if($sel_firm == '' && !empty($firm_bar_data)): ?>
        <div class="glass-module p-4 shadow flex-fill bg-white">
            <h5 class="font-weight-bold text-dark mb-4">Top Expendable Firms</h5>
            <canvas id="firmBar" style="max-height: 250px;"></canvas>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Raw Data Logs -->
<div class="glass-module shadow-lg mb-5 border-0">
    <div class="px-4 py-3 bg-warning rounded-top shadow-sm text-dark d-flex justify-content-between align-items-center">
        <h6 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice-dollar mr-2"></i> Expenditure Details Logging</h6>
        <span class="badge badge-dark">Matches applied filters</span>
    </div>
    <div class="table-responsive bg-white">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-4">Firm Name</th>
                    <th>Sector / Category</th>
                    <th class="text-center">Billing Month</th>
                    <th class="text-center">Billing Year</th>
                    <th class="text-right px-4">Amount Logged</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $dt_res->fetch_assoc()): ?>
                <tr>
                    <td class="px-4 font-weight-bold text-dark"><?php echo htmlspecialchars($row['firm_name']); ?></td>
                    <td><span class="badge badge-light border border-secondary text-muted"><?php echo htmlspecialchars($row['sector']); ?></span></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['billing_month']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['billing_year']); ?></td>
                    <td class="text-right px-4 font-weight-bold text-warning" style="text-shadow: 0 0 1px rgba(0,0,0,0.1);">Rs <?php echo number_format($row['sum_amt'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if($dt_res->num_rows == 0): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No firm data meets your selected parameters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    Chart.defaults.font.family = "'Open Sans', sans-serif";
    Chart.defaults.color = '#555';

    <?php if(!empty($sector_data)): ?>
    new Chart(document.getElementById('sectorPie'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($sector_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($sector_data); ?>,
                backgroundColor: ['#ffc107', '#17a2b8', '#dc3545', '#28a745', '#6c757d', '#6610f2'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            }
        }
    });
    <?php endif; ?>

    <?php if(!empty($hist_data)): ?>
    new Chart(document.getElementById('trendHist'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($hist_labels); ?>,
            datasets: [{
                label: 'Monthly Expense Flow (Rs)',
                data: <?php echo json_encode($hist_data); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                borderColor: '#ffc107',
                borderWidth: 1,
                barPercentage: 1.0,
                categoryPercentage: 1.0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
    <?php endif; ?>

    <?php if($sel_firm == '' && !empty($firm_bar_data)): ?>
    new Chart(document.getElementById('firmBar'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($firm_bar_labels); ?>,
            datasets: [{
                label: 'Gross Firm Payouts (Rs)',
                data: <?php echo json_encode($firm_bar_data); ?>,
                backgroundColor: '#17a2b8',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    <?php endif; ?>
</script>

<?php require 'admin_footer.php'; ?>
