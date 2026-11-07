<?php 
require 'admin_header.php'; 

$message = "";
$message_type = "";

// 1. Setup Selection Hub Context
$cur_yr = (int)date('Y');
$available_years = range($cur_yr - 5, $cur_yr + 5);
$db_yrs_q = "SELECT DISTINCT billing_year as yr FROM firms";
$db_res = $conn->query($db_yrs_q);
while ($yrow = $db_res->fetch_assoc()) {
    $y = (int)$yrow['yr'];
    if (!in_array($y, $available_years)) $available_years[] = $y;
}
sort($available_years);
$available_years = array_reverse($available_years);

$months_list = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$selected_month = $_GET['month'] ?? date('F');
$selected_year = $_GET['year'] ?? (string)$cur_yr;

$sectors_list = ["All", "Rice", "Kirana", "Curd", "Vegetables", "Chicken", "Eggs", "Gas", "Milk", "Misc"];
$selected_sector = $_GET['sector'] ?? "All";

// 2. Handle High-Detail 7-Column Transaction Sync
if (isset($_POST['upload_firms'])) {
    if (isset($_FILES['firms_csv']) && $_FILES['firms_csv']['error'] == 0) {
        set_time_limit(0);
        $filename = $_FILES['firms_csv']['tmp_name'];
        $up_month = $_POST['target_month'];
        $up_year = $_POST['target_year'];
        $up_sector = $_POST['target_sector'];
        
        // AUTO-CLEAR: Remove previous entries for this context to prevent duplicate files
        $clear_sql = "DELETE FROM firms WHERE billing_month = ? AND billing_year = ? AND sector = ?";
        $clear_stmt = $conn->prepare($clear_sql);
        $clear_stmt->bind_param("sis", $up_month, $up_year, $up_sector);
        $clear_stmt->execute();
        $clear_stmt->close();

        $file = fopen($filename, "r");
        fgetcsv($file); // Skip Header: Bill No, Firm Name, Date, Item, Qty, Rate, Total Amount
        $count = 0;
        
        $stmt = $conn->prepare("INSERT INTO firms (bill_no, firm_name, amount, billing_month, billing_year, sector) VALUES (?, ?, ?, ?, ?, ?)");

        while (($column = fgetcsv($file)) !== FALSE) {
            // Support 7-column format as shown in user ledger
            if (count($column) < 7) continue; 
            
            $bill_no = trim($column[0]);
            $firm_name = trim($column[1]);
            $amount = floatval(trim($column[6])); // Column 7 (Index 6) is Total Amount (Rs)
            
            if ($amount <= 0) continue; 
            
            $stmt->bind_param("ssdsis", $bill_no, $firm_name, $amount, $up_month, $up_year, $up_sector);
            $stmt->execute();
            $count++;
        }
        $stmt->close();
        fclose($file);
        $message = "Successfully synchronized $count transaction line-items for $up_sector ($up_month $up_year).";
        $message_type = "success";
    }
}

// 2b. Handle Manual Entry
if (isset($_POST['manual_entry'])) {
    $bill_no = trim($_POST['bill_no']);
    $firm_name = trim($_POST['firm_name']);
    $amount = floatval($_POST['amount']);
    $m_month = $_POST['target_month'];
    $m_year = $_POST['target_year'];
    $m_sector = $_POST['target_sector'];
    
    if ($amount > 0 && !empty($firm_name)) {
        $stmt_man = $conn->prepare("INSERT INTO firms (bill_no, firm_name, amount, billing_month, billing_year, sector) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_man->bind_param("ssdsis", $bill_no, $firm_name, $amount, $m_month, $m_year, $m_sector);
        if ($stmt_man->execute()) {
            $message = "Manual entry for '$firm_name' (Rs $amount) successfully recorded into $m_sector ($m_month $m_year).";
            $message_type = "success";
        } else {
            $message = "Failed to record manual entry. Database error.";
            $message_type = "danger";
        }
        $stmt_man->close();
    }
}

if (isset($_POST['clear_firms'])) {
    $up_month = $_POST['target_month'];
    $up_year = $_POST['target_year'];
    $conn->query("DELETE FROM firms WHERE billing_month = '$up_month' AND billing_year = $up_year");
    $message = "All records for $up_month $up_year across ALL sectors have been cleared.";
    $message_type = "warning";
}

if (isset($_POST['delete_individual'])) {
    $target_id = $_POST['firm_id'];
    $conn->query("DELETE FROM firms WHERE id = $target_id");
    $message = "Transaction record deleted.";
    $message_type = "info";
}
?>

<div class="glass-module shadow-lg animated fadeInDown mb-4">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-filter mr-2 text-info"></i> Firms Selection Hub</h2>
        <span class="badge badge-info p-2 px-3 shadow-sm">Audit Context</span>
    </div>
    <div class="p-4">
        <form method="GET" action="upload_firms.php" class="row">
            <div class="col-md-3">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">View Month</label>
                <select name="month" class="form-control form-control-sm">
                    <?php foreach($months_list as $m): ?>
                        <option value="<?php echo $m; ?>" <?php if($m == $selected_month) echo 'selected'; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">View Year</label>
                <select name="year" class="form-control form-control-sm">
                    <?php foreach($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php if($y == $selected_year) echo 'selected'; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="small font-weight-bold text-muted text-uppercase mb-2">View Sector</label>
                <select name="sector" class="form-control form-control-sm">
                    <?php foreach($sectors_list as $s): ?>
                        <option value="<?php echo $s; ?>" <?php if($s == $selected_sector) echo 'selected'; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-info btn-sm btn-block font-weight-bold shadow-sm">VIEW</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-5">
    <div class="col-lg-7">
        <div class="glass-module shadow-lg h-100 p-4" style="border: 2px dashed #00bcd4;">
            <h5 class="h6 font-weight-bold text-dark mb-2">Sync Transaction Ledger</h5>
            <p class="small text-muted mb-4 text-uppercase">Sector: <span class="text-info"><?php echo $selected_sector; ?></span></p>
            
            <?php if ($message != ""): ?>
                <div class="alert alert-<?php echo $message_type; ?> shadow-sm"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form action="upload_firms.php?sector=<?php echo urlencode($selected_sector); ?>" method="post" enctype="multipart/form-data">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted mb-1 text-uppercase">Target Sector</label>
                        <select name="target_sector" class="form-control form-control-sm border-info">
                            <?php foreach($sectors_list as $s): if($s=="All") continue; ?>
                                <option value="<?php echo $s; ?>" <?php if($s == $selected_sector) echo 'selected'; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted mb-1 text-uppercase">Target Month</label>
                        <select name="target_month" class="form-control form-control-sm border-info">
                            <?php foreach($months_list as $m): ?>
                                <option value="<?php echo $m; ?>" <?php if($m == $selected_month) echo 'selected'; ?>><?php echo $m; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted mb-1 text-uppercase">Target Year</label>
                        <select name="target_year" class="form-control form-control-sm border-info">
                            <?php foreach($available_years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php if($y == $selected_year) echo 'selected'; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="custom-file mb-4">
                    <input type="file" class="custom-file-input" name="firms_csv" accept=".csv" required id="csvIn">
                    <label class="custom-file-label" for="csvIn">Select 7-column CSV...</label>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" name="upload_firms" class="btn btn-info btn-lg px-5 font-weight-bold shadow">
                        <i class="fas fa-cloud-upload-alt mr-2"></i> START sync
                    </button>
                    <button type="submit" name="clear_firms" class="btn btn-outline-danger btn-sm" onclick="return confirm('WARNING: This will permanently DELETE ALL data (All Sectors) for the selected period. Proceed?')">
                        <i class="fas fa-trash-alt mr-1"></i> FLUSH MONTH
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="glass-module p-4 h-100 bg-white shadow-sm border" style="border-radius: 12px;">
            <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-keyboard mr-2 text-info"></i> Manual Single Entry</h6>
            <form action="upload_firms.php?sector=<?php echo urlencode($selected_sector); ?>" method="POST">
                <div class="row mb-2">
                    <div class="col-6">
                        <label class="small text-muted font-weight-bold mb-1">Bill No</label>
                        <input type="text" name="bill_no" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted font-weight-bold mb-1">Firm Name</label>
                        <input type="text" name="firm_name" class="form-control form-control-sm" placeholder="e.g. Ramesh Kirana" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="small text-muted font-weight-bold mb-1">Sector</label>
                        <select name="target_sector" class="form-control form-control-sm border-info" required>
                            <?php foreach($sectors_list as $s): if($s=="All") continue; ?>
                                <option value="<?php echo $s; ?>" <?php if($s == $selected_sector) echo 'selected'; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted font-weight-bold mb-1">Target Month</label>
                        <select name="target_month" class="form-control form-control-sm border-info" required>
                            <?php foreach($months_list as $m): ?>
                                <option value="<?php echo $m; ?>" <?php if($m == $selected_month) echo 'selected'; ?>><?php echo $m; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="small text-muted font-weight-bold mb-1">Target Year</label>
                        <select name="target_year" class="form-control form-control-sm border-info" required>
                            <?php foreach($available_years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php if($y == $selected_year) echo 'selected'; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted font-weight-bold mb-1">Amount (Rs)</label>
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm border-info" required>
                    </div>
                </div>
                <button type="submit" name="manual_entry" class="btn btn-outline-info btn-sm btn-block font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> RECORD TRANSACTION
                </button>
            </form>
            
            <div class="small text-muted mt-3 pt-3 border-top" style="line-height: 1.4;">
                <i class="fas fa-info-circle mr-1 text-info"></i> Need bulk? Use the 7-col CSV importer on the left.
            </div>
        </div>
    </div>
</div>

<div class="glass-module shadow-lg animated fadeInUp border-info" style="border-top: 4px solid #00bcd4;">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center">
        <h5 class="h6 mb-0 text-dark font-weight-bold"><i class="fas fa-file-invoice-dollar text-info mr-2"></i> Firms Registry and Billed Status</h5>
        <span class="badge badge-pill badge-info px-3"><?php echo "$selected_sector - $selected_month"; ?></span>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3" style="width: 80px;">S.No</th>
                        <th class="px-4 py-3">Bill No</th>
                        <?php if($selected_sector == "All"): ?>
                            <th class="px-4 py-3 text-info">Sector</th>
                        <?php endif; ?>
                        <th class="px-4 py-3">Firm Name</th>
                        <th class="px-4 py-3 text-right">Total Amount(Rs)</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody style="background: #fff;">
                    <?php 
                    if ($selected_sector == "All") {
                        $sql_reg = "SELECT * FROM firms WHERE billing_month = ? AND billing_year = ? AND amount > 0 ORDER BY sector, uploaded_at ASC";
                        $stmt_reg = $conn->prepare($sql_reg);
                        $stmt_reg->bind_param("si", $selected_month, $selected_year);
                    } else {
                        $sql_reg = "SELECT * FROM firms WHERE billing_month = ? AND billing_year = ? AND sector = ? AND amount > 0 ORDER BY uploaded_at ASC";
                        $stmt_reg = $conn->prepare($sql_reg);
                        $stmt_reg->bind_param("sis", $selected_month, $selected_year, $selected_sector);
                    }
                    $stmt_reg->execute();
                    $res_reg = $stmt_reg->get_result();

                    if ($res_reg->num_rows > 0) {
                        $sno = 1;
                        $sector_total = 0;
                        while($row = $res_reg->fetch_assoc()) {
                            $sector_total += $row['amount'];
                            echo "<tr>
                                    <td class='px-4 py-3 text-muted small'>{$sno}</td>
                                    <td class='px-4 py-3 font-weight-bold text-info'>".htmlspecialchars($row['bill_no'])."</td>";
                            
                            if($selected_sector == "All") {
                                echo "<td class='px-4 py-3 small text-muted'><span class='badge badge-light border text-uppercase'>".htmlspecialchars($row['sector'])."</span></td>";
                            }
                            
                            echo "<td class='px-4 py-3 text-uppercase'>".htmlspecialchars($row['firm_name'])."</td>
                                    <td class='px-4 py-3 text-right font-weight-bold text-dark'>Rs ".number_format($row['amount'], 2)."</td>
                                    <td class='px-4 py-3 text-center'>
                                        <form method='POST' onsubmit='return confirm(\"Delete this specific bill?\")' class='m-0'>
                                            <input type='hidden' name='firm_id' value='{$row['id']}'>
                                            <button type='submit' name='delete_individual' class='btn btn-outline-danger btn-sm p-1' style='width:30px;'>
                                                <i class='fas fa-times'></i>
                                            </button>
                                        </form>
                                    </td>
                                  </tr>";
                            $sno++;
                        }
                        echo "<tr style='background: rgba(0,188,212,0.1); border-top: 2px solid #00bcd4;'>
                                <td colspan='".($selected_sector == "All" ? "5" : "4")."' class='px-4 py-3 text-right font-weight-bold uppercase small text-info'>Total for ".($selected_sector == "All" ? "Selected Period" : $selected_sector).":</td>
                                <td class='px-4 py-3 text-right font-weight-bold' style='font-size: 1.1rem;'>Rs ".number_format($sector_total, 2)."</td>
                                <td></td>
                              </tr>";
                    } else {
                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No transaction line-items found for this sector context.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$sql_overall = "SELECT SUM(amount) as total_exp FROM firms WHERE billing_month = ? AND billing_year = ?";
$stmt_all = $conn->prepare($sql_overall);
$stmt_all->bind_param("si", $selected_month, $selected_year);
$stmt_all->execute();
$overall_total = $stmt_all->get_result()->fetch_assoc()['total_exp'] ?? 0;
?>

<div class="glass-module shadow-lg mt-5 bg-white overflow-hidden" style="border: 2px solid #00bcd4; border-radius: 12px;">
    <div class="row align-items-center no-gutters">
        <div class="col-md-8 p-4">
            <h3 class="h5 font-weight-bold text-dark mb-1">Monthly Expenditure Summary</h3>
            <p class="small text-muted mb-0">Total combined budget across all sectors for <b><?php echo "$selected_month $selected_year"; ?></b>.</p>
        </div>
        <div class="col-md-4 p-4 text-center bg-info text-white shadow-lg">
            <span class="small text-uppercase font-weight-bold opacity-8 mb-2 d-block">Overall Expense</span>
            <h2 class="h3 font-weight-bold mb-0">Rs <?php echo number_format($overall_total, 2); ?></h2>
        </div>
    </div>
</div>

<script>
    document.getElementById('csvIn').onchange = function() {
        this.nextElementSibling.innerHTML = this.value.split('\\').pop();
    };
</script>

<?php require 'admin_footer.php'; ?>
