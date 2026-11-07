<?php 
require 'admin_header.php'; 

$message = "";
$message_type = "";

// 1. Setup Selection Hub Context
$cur_yr = (int)date('Y');
$available_years = range($cur_yr - 5, $cur_yr + 5);
$db_yrs_q = "SELECT DISTINCT billing_year as yr FROM firm_payments";
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

// 2. Handle Firm Bill Payment
if (isset($_POST['pay_firm_bill'])) {
    $firm_id = intval($_POST['firm_id']);
    // Extract date from datetime-local for payment_date compat
    $datetime_val = trim($_POST['p_date']);
    $p_date = substr($datetime_val, 0, 10);
    if (empty($p_date)) $p_date = date('Y-m-d');
    
    $firm_name = trim($_POST['firm_name']);
    $amount = floatval($_POST['amount']);
    $mode = trim($_POST['mode']);
    $ref_no = trim($_POST['ref_no']);
    $sector = trim($_POST['sector']);
    $b_month = trim($_POST['b_month']);
    $b_year = intval($_POST['b_year']);
    $remarks = "Paid via system";
    
    if ($firm_id > 0 && $amount > 0 && !empty($firm_name)) {
        // Update Firms table
        $conn->query("UPDATE firms SET status = 'Paid' WHERE id = $firm_id");
        
        // Insert into firm_payments
        $stmt = $conn->prepare("INSERT INTO firm_payments (payment_date, firm_name, amount, payment_mode, reference_no, remarks, billing_month, billing_year, sector) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssssis", $p_date, $firm_name, $amount, $mode, $ref_no, $remarks, $b_month, $b_year, $sector);
        if ($stmt->execute()) {
            $message = "Payment of Rs " . number_format($amount, 2) . " to '$firm_name' successfully recorded as Paid.";
            $message_type = "success";
        } else {
            $message = "Error recording payout to database.";
            $message_type = "danger";
        }
        $stmt->close();
    }
}

if (isset($_POST['clear_payments'])) {
    $up_month = $_POST['target_month'];
    $up_year = $_POST['target_year'];
    $conn->query("DELETE FROM firm_payments WHERE billing_month = '$up_month' AND billing_year = $up_year");
    $message = "All payout records for $up_month $up_year across ALL sectors have been cleared.";
    $message_type = "warning";
}

if (isset($_POST['delete_individual'])) {
    $target_id = $_POST['payment_id'];
    $conn->query("DELETE FROM firm_payments WHERE id = $target_id");
    $message = "Payout record deleted.";
    $message_type = "info";
}
?>

<div class="glass-module shadow-lg animated fadeInDown mb-4">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #e91e63;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-money-bill-wave mr-2 text-danger"></i> Firms Payments Hub</h2>
        <span class="badge badge-danger p-2 px-3 shadow-sm">Payout Registry</span>
    </div>
    <div class="p-4">
        <form method="GET" action="firm_payments.php" class="row">
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
                <button type="submit" class="btn btn-danger btn-sm btn-block font-weight-bold shadow-sm">VIEW</button>
            </div>
        </form>
    </div>
</div>

<?php if ($message != ""): ?>
    <div class="alert alert-<?php echo $message_type; ?> shadow-sm mb-4"><?php echo $message; ?></div>
<?php endif; ?>

<div class="glass-module shadow-lg animated fadeInUp mb-5" style="border-top: 4px solid #00bcd4;">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center">
        <h5 class="h6 mb-0 text-dark font-weight-bold"><i class="fas fa-file-invoice-dollar text-info mr-2"></i> Pending Bills Overview</h5>
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
                        <th class="px-4 py-3">Firm / Vendor</th>
                        <th class="px-4 py-3 text-right">Amount to Pay</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody style="background: #fff;">
                    <?php 
                    if ($selected_sector == "All") {
                        $sql_bills = "SELECT * FROM firms WHERE billing_month = ? AND billing_year = ? AND status = 'Pending' ORDER BY sector, uploaded_at ASC";
                        $stmt_bills = $conn->prepare($sql_bills);
                        $stmt_bills->bind_param("si", $selected_month, $selected_year);
                    } else {
                        $sql_bills = "SELECT * FROM firms WHERE billing_month = ? AND billing_year = ? AND sector = ? AND status = 'Pending' ORDER BY uploaded_at ASC";
                        $stmt_bills = $conn->prepare($sql_bills);
                        $stmt_bills->bind_param("sis", $selected_month, $selected_year, $selected_sector);
                    }
                    $stmt_bills->execute();
                    $res_bills = $stmt_bills->get_result();

                    if ($res_bills->num_rows > 0) {
                        $sno = 1;
                        $total_pending = 0;
                        while($row = $res_bills->fetch_assoc()) {
                            $is_pending = ($row['status'] == 'Pending');
                            if($is_pending) {
                                $total_pending += $row['amount'];
                            }
                            $f_id = $row['id'];
                            $f_name = htmlspecialchars($row['firm_name']);
                            $f_sec = htmlspecialchars($row['sector']);
                            $f_amt = $row['amount'];
                            
                            echo "<tr>
                                    <td class='px-4 py-3 text-muted small'>{$sno}</td>
                                    <td class='px-4 py-3 font-weight-bold text-info'>".htmlspecialchars($row['bill_no'])."</td>";
                            
                            if($selected_sector == "All") {
                                echo "<td class='px-4 py-3 small text-muted'><span class='badge badge-light border text-uppercase'>{$f_sec}</span></td>";
                            }
                            
                            echo "<td class='px-4 py-3 text-uppercase font-weight-bold'>{$f_name}</td>
                                    <td class='px-4 py-3 text-right font-weight-bold'>Rs ".number_format($f_amt, 2)."</td>
                                    <td class='px-4 py-3 text-center'>";
                                    
                            if($is_pending) {
                                echo "<span class='badge badge-warning text-dark px-2 py-1'>Pending</span>";
                            } else {
                                echo "<span class='badge badge-success px-2 py-1'>Paid</span>";
                            }
                            
                            echo "</td>
                                    <td class='px-4 py-3 text-center'>";
                            if($is_pending) {
                                $data_name = htmlspecialchars($row['firm_name'], ENT_QUOTES, 'UTF-8');
                                $data_sec = htmlspecialchars($row['sector'], ENT_QUOTES, 'UTF-8');
                                echo "<button type='button' class='btn btn-sm btn-info font-weight-bold shadow-sm pay-btn-trigger' data-id='{$f_id}' data-name='{$data_name}' data-sec='{$data_sec}' data-amt='{$f_amt}' data-month='{$selected_month}' data-year='{$selected_year}'>PAY</button>";
                            } else {
                                echo "<span class='text-muted small'><i class='fas fa-check'></i> Settled</span>";
                            }
                            echo "</td></tr>";
                            $sno++;
                        }
                    } else {
                        echo "<tr><td colspan='".($selected_sector == "All" ? "7" : "6")."' class='text-center py-5 text-muted'>No bills found for this context.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="glass-module shadow-lg animated fadeInUp border-danger" style="border-top: 4px solid #e91e63;">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center">
        <h5 class="h6 mb-0 text-dark font-weight-bold"><i class="fas fa-history text-danger mr-2"></i> Firms Payout Ledger</h5>
        <span class="badge badge-pill badge-danger px-3"><?php echo "$selected_sector - $selected_month"; ?></span>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3" style="width: 80px;">S.No</th>
                        <th class="px-4 py-3">Date</th>
                        <?php if($selected_sector == "All"): ?>
                            <th class="px-4 py-3 text-danger">Sector</th>
                        <?php endif; ?>
                        <th class="px-4 py-3">Firm / Vendor</th>
                        <th class="px-4 py-3">Pay Mode</th>
                        <th class="px-4 py-3">Ref No</th>
                        <th class="px-4 py-3 text-right">Amount Paid</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody style="background: #fff;">
                    <?php 
                    if ($selected_sector == "All") {
                        $sql_reg = "SELECT * FROM firm_payments WHERE billing_month = ? AND billing_year = ? ORDER BY sector, payment_date ASC";
                        $stmt_reg = $conn->prepare($sql_reg);
                        $stmt_reg->bind_param("si", $selected_month, $selected_year);
                    } else {
                        $sql_reg = "SELECT * FROM firm_payments WHERE billing_month = ? AND billing_year = ? AND sector = ? ORDER BY payment_date ASC";
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
                                    <td class='px-4 py-3'>".htmlspecialchars($row['payment_date'])."</td>";
                            
                            if($selected_sector == "All") {
                                echo "<td class='px-4 py-3 small text-muted'><span class='badge badge-light border text-uppercase'>".htmlspecialchars($row['sector'])."</span></td>";
                            }
                            
                            echo "<td class='px-4 py-3 text-uppercase font-weight-bold'>".htmlspecialchars($row['firm_name'])."</td>
                                    <td class='px-4 py-3 text-muted'>".htmlspecialchars($row['payment_mode'])."</td>
                                    <td class='px-4 py-3 font-weight-bold'>".(($row['reference_no'] != "") ? htmlspecialchars($row['reference_no']) : "-")."</td>
                                    <td class='px-4 py-3 text-right font-weight-bold text-success'>Rs ".number_format($row['amount'], 2)."</td>
                                    <td class='px-4 py-3 text-center'>
                                        <form method='POST' onsubmit='return confirm(\"Delete this payout record?\")' class='m-0'>
                                            <input type='hidden' name='payment_id' value='{$row['id']}'>
                                            <button type='submit' name='delete_individual' class='btn btn-outline-danger btn-sm p-1' style='width:30px;'>
                                                <i class='fas fa-times'></i>
                                            </button>
                                        </form>
                                    </td>
                                  </tr>";
                            $sno++;
                        }
                        echo "<tr style='background: rgba(233,30,99,0.05); border-top: 2px solid #e91e63;'>
                                <td colspan='".($selected_sector == "All" ? "6" : "5")."' class='px-4 py-3 text-right font-weight-bold uppercase small text-danger'>Total Paid for ".($selected_sector == "All" ? "Period" : $selected_sector).":</td>
                                <td class='px-4 py-3 text-right font-weight-bold' style='font-size: 1.1rem;'>Rs ".number_format($sector_total, 2)."</td>
                                <td></td>
                              </tr>";
                    } else {
                        echo "<tr><td colspan='8' class='text-center py-5 text-muted'>No payout records found for this context.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Payment Modal -->
<div class="modal fade" id="payBillModal" tabindex="-1" role="dialog" aria-labelledby="payBillModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title font-weight-bold" id="payBillModalLabel"><i class="fas fa-wallet mr-2"></i> Process Bill Payment</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="firm_payments.php?sector=<?php echo urlencode($selected_sector); ?>&month=<?php echo urlencode($selected_month); ?>&year=<?php echo $selected_year; ?>" method="POST">
          <div class="modal-body p-4 bg-light">
              <input type="hidden" name="firm_id" id="modal_firm_id">
              <input type="hidden" name="b_month" id="modal_b_month">
              <input type="hidden" name="b_year" id="modal_b_year">
              
              <div class="row mb-3">
                  <div class="col-6">
                      <label class="small text-muted font-weight-bold mb-1">Vendor/Firm</label>
                      <input type="text" name="firm_name" id="modal_firm_name" class="form-control font-weight-bold text-dark" required>
                  </div>
                  <div class="col-6">
                      <label class="small text-muted font-weight-bold mb-1">Sector</label>
                      <input type="text" name="sector" id="modal_sector" class="form-control font-weight-bold text-dark" required>
                  </div>
              </div>
              <div class="row mb-3">
                  <div class="col-12">
                      <label class="small text-muted font-weight-bold mb-1">Amount to Pay (Rs)</label>
                      <input type="number" step="0.01" name="amount" id="modal_amount" class="form-control font-weight-bold text-success" required style="font-size: 1.1rem;">
                  </div>
              </div>
              <div class='border-top my-3'></div>
              <div class="row mb-3">
                  <div class="col-12 mb-3">
                      <label class="small text-muted font-weight-bold mb-1">Payment Date & Time</label>
                      <input type="datetime-local" name="p_date" id="modal_p_date" class="form-control border-info" required>
                  </div>
                  <div class="col-6">
                      <label class="small text-info font-weight-bold mb-1">Reference / UTR No.</label>
                      <input type="text" name="ref_no" class="form-control border-info" placeholder="Required for audits" required>
                  </div>
                  <div class="col-6">
                      <label class="small text-info font-weight-bold mb-1">Payment Mode</label>
                      <select name="mode" class="form-control border-info">
                          <option value="e-pay">e-pay</option>
                          <option value="UPI">UPI</option>
                          <option value="Bank Transfer">Bank Transfer</option>
                          <option value="Cash">Cash</option>
                          <option value="Cheque">Cheque</option>
                      </select>
                  </div>
              </div>
          </div>
          <div class="modal-footer bg-white border-top-0">
            <button type="button" class="btn btn-light shadow-sm" data-dismiss="modal">Cancel</button>
            <button type="submit" name="pay_firm_bill" class="btn btn-info shadow font-weight-bold px-4">SUBMIT</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
window.addEventListener('load', function() {
    // Append modal to body to overcome parent overflow/z-index issues (which make it unclickable)
    // jQuery is absolutely available here because window.load runs after admin_footer.php loads it
    $('#payBillModal').appendTo("body");
    
    // Bind click event to trigger generic data parsing without escaping issues
    $('.pay-btn-trigger').on('click', function() {
        let id = $(this).attr('data-id');
        let name = $(this).attr('data-name');
        let sector = $(this).attr('data-sec');
        let amount = $(this).attr('data-amt');
        let bMonth = $(this).attr('data-month');
        let bYear = $(this).attr('data-year');
        openPayModal(id, name, sector, amount, bMonth, bYear);
    });
});

function openPayModal(id, name, sector, amount, bMonth, bYear) {
    document.getElementById('modal_firm_id').value = id;
    document.getElementById('modal_firm_name').value = name;
    document.getElementById('modal_sector').value = sector;
    document.getElementById('modal_amount').value = amount;
    document.getElementById('modal_b_month').value = bMonth;
    document.getElementById('modal_b_year').value = bYear;
    
    let now = new Date();
    // Adjust for local timezone accurately to prefill datetime-local
    let local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    document.getElementById('modal_p_date').value = local.toISOString().slice(0,16);
    
    $('#payBillModal').modal('show');
}
</script>

<?php require 'admin_footer.php'; ?>
