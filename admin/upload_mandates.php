<?php 
require 'admin_header.php'; 

$message = "";
$message_type = "";

// 1. Setup Year/Month Filter (Context for both Upload and Report)
$cur_yr = (int)date('Y');
$available_years = range($cur_yr - 5, $cur_yr + 5);
$db_yrs_q = "SELECT DISTINCT billing_year as yr FROM mandates";
$db_res = $conn->query($db_yrs_q);
while ($yrow = $db_res->fetch_assoc()) {
    $y = (int)$yrow['yr'];
    if (!in_array($y, $available_years)) $available_years[] = $y;
}
sort($available_years);
$available_years = array_reverse($available_years);

$months_list = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$selected_month = $_GET['month'] ?? date('F');
$selected_year = $_GET['year'] ?? $available_years[0];

// 2. Handle Mandate Uploads (Hall Ticket Number, Name, Course, Days Present)
if (isset($_POST['upload_mandates'])) {
    $selected_month = $_POST['target_month'];
    $selected_year = $_POST['target_year'];
    if (isset($_FILES['mandates_csv']) && $_FILES['mandates_csv']['error'] == 0) {
        set_time_limit(0);
        $filename = $_FILES['mandates_csv']['tmp_name'];
        $up_month = $_POST['target_month'];
        $up_year = $_POST['target_year'];
        
        $file = fopen($filename, "r");
        fgetcsv($file); // Skip header
        $count = 0;
        
        $stmt = $conn->prepare("INSERT INTO mandates (hall_ticket, student_name, course, days_present, billing_month, billing_year) 
                                VALUES (?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE 
                                student_name=VALUES(student_name), 
                                course=VALUES(course),
                                days_present=VALUES(days_present)");

        $current_course = "Unknown";
        while (($column = fgetcsv($file)) !== FALSE) {
            if (count($column) < 1) continue;
            
            $col0 = trim($column[0] ?? '');
            $col1 = trim($column[1] ?? '');
            $col2 = trim($column[2] ?? '');
            $col3 = trim($column[3] ?? '');

            // Detect Course Section Header (e.g., "II/IV ECE")
            if (!empty($col0) && empty($col1) && empty($col2)) {
                $current_course = $col0;
                continue;
            }

            // Skip CSV Header/Label rows
            if (strtoupper($col0) === 'S.NO' || stripos($col0, 'HALL TICKET') !== false || strtoupper($col1) === 'HT NO') {
                continue;
            }

            $hall_ticket = '';
            $student_name = '';
            $course = '';
            $days_present = 0;

            // Pattern 1: CSV format is S.No, Hall Ticket, Name, Days Present
            if (preg_match('/^[a-zA-Z0-9.-]{4,30}$/', $col1) && !empty($col2)) {
                $hall_ticket = strtoupper($col1);
                $student_name = $col2;
                $course = $current_course;
                $days_present = intval(preg_replace('/[^0-9]/', '', $col3));
            } 
            // Pattern 2: CSV format is Hall Ticket, Name, Course, Days (Standard)
            else if (preg_match('/^[a-zA-Z0-9.-]{4,30}$/', $col0) && !empty($col1)) {
                $hall_ticket = strtoupper($col0);
                $student_name = $col1;
                $course = !empty($col2) ? $col2 : $current_course;
                
                // If col3 has the days, use it. But in some bad exports, it might be in col2 if course is missing
                $val3 = preg_replace('/[^0-9]/', '', $col3);
                $val2 = preg_replace('/[^0-9]/', '', $col2);
                $days_present = ($val3 !== '') ? intval($val3) : (($val2 !== '') ? intval($val2) : 0);
            } else {
                continue; // Skip unrecognized formats or blank space
            }
            
            if (!empty($hall_ticket)) {
                $stmt->bind_param("sssisi", $hall_ticket, $student_name, $course, $days_present, $up_month, $up_year);
                $stmt->execute();
                $count++;
            }
        }
        $stmt->close();
        fclose($file);
        $message = "Successfully synchronized $count student records for $up_month $up_year.";
        $message_type = "success";
    }
}

if (isset($_POST['clear_mandates'])) {
    $up_month = $_POST['target_month'];
    $up_year = $_POST['target_year'];
    $conn->query("DELETE FROM mandates WHERE billing_month = '$up_month' AND billing_year = $up_year");
    $message = "All mandate records for $up_month $up_year have been permanently deleted from the database."; 
    $message_type = "warning";
}
?>

<!-- 1. Selection Header -->
<div class="glass-module shadow-lg animated fadeInDown mb-4">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-calendar-alt mr-2 text-info"></i> Administrative Selection Portal</h2>
        <span class="badge badge-info p-2 px-3 shadow-sm">Audit Hub</span>
    </div>
    <div class="p-4">
        <form method="GET" action="upload_mandates.php" class="row">
            <div class="col-md-2 mb-3 mb-md-0">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Month</label>
                <select name="month" class="form-control custom-select">
                    <?php foreach($months_list as $m): ?>
                        <option value="<?php echo $m; ?>" <?php if($m == $selected_month) echo 'selected'; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-3 mb-md-0">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Year</label>
                <select name="year" class="form-control custom-select">
                    <?php foreach($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php if($y == $selected_year) echo 'selected'; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-3 mb-md-0">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Hall Ticket</label>
                <input type="text" name="search_ht" class="form-control" placeholder="e.g. 210..." value="<?php echo htmlspecialchars($_GET['search_ht'] ?? ''); ?>">
            </div>
            <div class="col-md-2 mb-3 mb-md-0">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Branch</label>
                <select class="form-control custom-select" name="search_branch">
                    <option value="">All</option>
                    <?php
                    $b_sql = "SELECT DISTINCT Dept FROM Student WHERE Dept IS NOT NULL AND Dept != '' ORDER BY Dept";
                    $b_res = mysqli_query($conn, $b_sql);
                    while($b_row = mysqli_fetch_assoc($b_res)) {
                        $sel = (isset($_GET['search_branch']) && $_GET['search_branch'] == $b_row['Dept']) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($b_row['Dept'])."' $sel>".htmlspecialchars($b_row['Dept'])."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2 mb-3 mb-md-0">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">Grade/Yr</label>
                <select class="form-control custom-select" name="search_year">
                    <option value="">All</option>
                    <?php
                    $y_sql = "SELECT DISTINCT Academic_Year FROM Student WHERE Academic_Year IS NOT NULL AND Academic_Year != '' AND Academic_Year != '-' ORDER BY Academic_Year";
                    $y_res = mysqli_query($conn, $y_sql);
                    while($y_row = mysqli_fetch_assoc($y_res)) {
                        $sel = (isset($_GET['search_year']) && $_GET['search_year'] == $y_row['Academic_Year']) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($y_row['Academic_Year'])."' $sel>".htmlspecialchars($y_row['Academic_Year'])."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-info btn-block font-weight-bold shadow-sm">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Mandate Synchronization Tool (Contextual) -->
<div class="row mb-5">
    <div class="col-lg-7">
        <div class="glass-module shadow-lg h-100 p-4" style="border: 2px dashed #00bcd4;">
            <h5 class="h6 font-weight-bold text-dark mb-3">
                <i class="fas fa-file-upload text-info mr-2"></i> Sync Student Data for <span class="text-info"><?php echo "$selected_month $selected_year"; ?></span>
            </h5>
            <p class="small text-muted mb-4">Finalized CSV Format: <b>Hall Ticket Number., Student Name, Course, Days Present</b></p>
            
            <?php if ($message != ""): ?>
                <div class="alert alert-<?php echo $message_type; ?> shadow-sm"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form action="upload_mandates.php?month=<?php echo urlencode($selected_month); ?>&year=<?php echo urlencode($selected_year); ?>" method="post" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="small text-muted font-weight-bold">Target Billing Month</label>
                        <select name="target_month" class="form-control font-weight-bold" required>
                            <?php foreach($months_list as $m) {
                                $sel = ($m == $selected_month) ? 'selected' : '';
                                echo "<option value='$m' $sel>$m</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted font-weight-bold">Target Billing Year</label>
                        <input type="number" name="target_year" class="form-control font-weight-bold" value="<?php echo $selected_year; ?>" required>
                    </div>
                </div>
                
                <div class="custom-file mb-4 mt-2">
                    <input type="file" class="custom-file-input" name="mandates_csv" accept=".csv" required id="csvIn">
                    <label class="custom-file-label" for="csvIn">Choose Ledger File...</label>
                </div>
                <div class="d-block">
                    <button type="submit" name="upload_mandates" class="btn btn-info btn-lg px-5 font-weight-bold shadow w-100">
                        <i class="fas fa-cloud-upload-alt mr-2"></i> IMPORT RECORDS TO DATABASE
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="glass-module p-4 h-100 bg-light shadow-sm border">
            <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">Reporting Note</h6>
            <p class="small text-muted mb-0">The registry below now acts as a per-month ledger. All details including names and courses are sourced directly from your CSV upload to ensure historical accuracy for the selected billing cycle.</p>
        </div>
    </div>
</div>

<!-- 3. Mandate Registry (Uploaded Details View) -->
<div class="glass-module shadow-lg animated fadeInUp border-info" style="border-top: 4px solid #00bcd4;">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center">
        <h5 class="h6 mb-0 text-dark font-weight-bold"><i class="fas fa-list-ul text-info mr-2"></i> Mandate Registry for <?php echo htmlspecialchars($selected_month . " " . $selected_year); ?></h5>
        
        <div class="d-flex align-items-center">
            <span class="badge badge-pill badge-info px-3 mr-3">Monthly Audit View</span>
            <form action="upload_mandates.php?month=<?php echo urlencode($selected_month); ?>&year=<?php echo urlencode($selected_year); ?>" method="post" class="m-0 p-0">
                <input type="hidden" name="target_month" value="<?php echo $selected_month; ?>">
                <input type="hidden" name="target_year" value="<?php echo $selected_year; ?>">
                <button type="submit" name="clear_mandates" class="btn btn-sm btn-danger font-weight-bold shadow-sm" onclick="return confirm('PERMANENT DELETION: Are you sure you want to delete ALL attendance data for <?php echo $selected_month . ' ' . $selected_year; ?> from the database? This cannot be undone.');">
                    <i class="fas fa-trash-alt mr-1"></i> DELETE THIS PERIOD
                </button>
            </form>
        </div>
    </div>
    <div class="p-0">
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">S.No</th>
                        <th class="px-4 py-3">Hall Ticket</th>
                        <th class="px-4 py-3">Student Name</th>
                        <th class="px-4 py-3 text-center">Year</th>
                        <th class="px-4 py-3">Branch</th>
                        <th class="px-4 py-3 text-center">Days Present (<?php echo $selected_month; ?>)</th>
                    </tr>
                </thead>
                <tbody style="background: #fff;">
                    <?php 
                    $where_conds = ["m.billing_month = ?", "m.billing_year = ?"];
                    $params = [$selected_month, $selected_year];
                    $types = "si";

                    if (!empty($_GET['search_ht'])) {
                        $where_conds[] = "m.hall_ticket LIKE ?";
                        $params[] = "%" . trim($_GET['search_ht']) . "%";
                        $types .= "s";
                    }
                    if (!empty($_GET['search_branch'])) {
                        $where_conds[] = "s.Dept = ?";
                        $params[] = trim($_GET['search_branch']);
                        $types .= "s";
                    }
                    if (!empty($_GET['search_year'])) {
                        $where_conds[] = "s.Academic_Year = ?";
                        $params[] = trim($_GET['search_year']);
                        $types .= "s";
                    }

                    $sql_details = "SELECT m.*, COALESCE(s.Fname, m.student_name) AS display_name FROM mandates m LEFT JOIN Student s ON m.hall_ticket = s.Student_id WHERE " . implode(" AND ", $where_conds) . " ORDER BY m.id ASC";
                    $stmt_det = $conn->prepare($sql_details);
                    $stmt_det->bind_param($types, ...$params);
                    $stmt_det->execute();
                    $res_det = $stmt_det->get_result();

                    $total_days = 0;
                    $student_count = 0;
                    $has_records = ($res_det->num_rows > 0);

                    if ($has_records) {
                        while($row_det = $res_det->fetch_assoc()) {
                            $total_days += (int)$row_det['days_present'];
                            $student_count++;
                            
                            $course_raw = $row_det['course'] ?? '';
                            $yr = '-';
                            $val_branch = '';
                            
                            if (preg_match('/^([IVX]+(?:\/[IVX]+)?)(?:\s+|-)*(.*)$/i', trim($course_raw), $matches)) {
                                $yr_fraction = $matches[1];
                                $yr = explode('/', $yr_fraction)[0];
                                $val_branch = trim($matches[2]);
                            } else {
                                $val_branch = trim($course_raw);
                            }
                            
                            if (empty($val_branch)) {
                                $val_branch = 'UNKNOWN';
                            }
                            
                            echo "<tr>
                                    <td class='px-4 py-3 font-weight-bold text-muted border-right'>{$student_count}</td>
                                    <td class='px-4 py-3 font-weight-bold text-info'>".htmlspecialchars($row_det['hall_ticket'])."</td>
                                    <td class='px-4 py-3 uppercase'>".htmlspecialchars($row_det['display_name'])."</td>
                                    <td class='px-4 py-3 text-center font-weight-bold'>".htmlspecialchars($yr)."</td>
                                    <td class='px-4 py-3 small font-weight-bold text-secondary'>".htmlspecialchars($val_branch)."</td>
                                    <td class='px-4 py-3 text-center'><span class='badge badge-pill badge-info border px-4 font-weight-bold'>{$row_det['days_present']} Days</span></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No uploaded records found for this period.</td></tr>";
                    }
                    ?>
                </tbody>
                <?php if ($has_records): ?>
                <tfoot>
                    <tr style="background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);">
                        <td colspan="3" class="px-4 py-3 font-weight-bold text-white" style="font-size: 1rem; letter-spacing: 0.5px;">
                            <i class="fas fa-calculator mr-2"></i>
                            Total Days Present &mdash;
                            <span class="badge badge-light ml-2" style="color: #00838f; font-size: 0.85rem;">
                                <?php echo $student_count; ?> Student<?php echo $student_count !== 1 ? 's' : ''; ?>
                            </span>
                            &mdash; <?php echo htmlspecialchars($selected_month . ' ' . $selected_year); ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-light px-4 py-2 font-weight-bold" style="font-size: 1.1rem; color: #00838f; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                <i class="fas fa-calendar-check mr-1"></i> <?php echo $total_days; ?> Days
                            </span>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('csvIn').onchange = function() {
        this.nextElementSibling.innerHTML = this.value.split('\\').pop();
    };
</script>

<?php 
require 'admin_footer.php'; 
?>
