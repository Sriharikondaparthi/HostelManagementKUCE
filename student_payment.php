<?php require 'includes/config.inc.php'; 
 date_default_timezone_set('Asia/Kolkata'); 
 
$hall_ticket = $_SESSION['hall_ticket'] ?? '';

// Validate Manager Assignment
if (!isset($manager_assigned) || !$manager_assigned) {
    echo "<!DOCTYPE html><html lang='en'><head><title>Access Denied</title><link rel='stylesheet' href='web_home/css_home/bootstrap.css'><style>body{background:#f8f9fa;display:flex;align-items:center;justify-content:center;height:100vh;}</style></head><body>
    <div class='container text-center'><div class='card shadow p-5'><h1 class='text-danger mb-4'>Access Restricted</h1><p class='lead'>Your payment module has not been activated yet. Please wait until a manager is assigned to your hostel.</p><a href='home.php' class='btn btn-primary mt-3'>Return to Home</a></div></div>
    </body></html>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>H-Track - Hostel Bills</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
    <link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
    <style type="text/css">
        .card-header { padding: 15px; font-size: 24px; }
        .card-body { padding: 15px; }
        .card-footer { text-align: left; padding: 15px; }
        .payment-table { background: #fff; margin-top: 20px;}
        .payment-table th { background: #f8f9fa; }
        .dues-card {
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            border-left: 5px solid #ff4b4b;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .dues-card.paid {
            border-left: 5px solid #28a745;
        }
    </style>
</head>

<body>
<div class="inner-page-banner" id="home">
    <header>
        <div class="container agile-banner_nav">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <h1><a class="navbar-brand" href="home.php">H-Track</a></h1>
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="services.php">Hostels</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                        <li class="nav-item"><a class="nav-link" href="message_user.php">Message Received <?php if(isset($unread_msg_count) && $unread_msg_count > 0) { echo '<span class="badge badge-danger">'.$unread_msg_count.'</span>'; } ?></a></li>
                        <?php if (isset($manager_assigned) && $manager_assigned) { ?>
                        <li class="nav-item"><a class="nav-link" href="hostel_bills.php">Hostel Bills</a></li>
                        <li class="nav-item active"><a class="nav-link" href="student_payment.php">Payments</a></li>
                        <li class="nav-item"><a class="nav-link" href="attendance_user.php">Attendance</a></li>
                        <?php } ?>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['hall_ticket'] ?? ''); ?>
                                <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu agile_short_dropdown">
                                <li><a href="profile.php">My Profile</a></li>
                                <li><a href="includes/logout.inc.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
</div>

<br><br>
<div class="container">
    <h2 class="heading text-capitalize mb-sm-5 mb-4"> Payments </h2>
    
    <?php
    $total_dues = 0;
    if ($hall_ticket !== '') {
        // Calculate Total Dues for the checkout maximum
        $dues_query = "SELECT SUM(CASE WHEN transaction_type = 'Charge' THEN amount ELSE -amount END) as TotalDues FROM Payment WHERE Student_id = ?";
        $stmt_dues = $conn->prepare($dues_query);
        $stmt_dues->bind_param("s", $hall_ticket);
        $stmt_dues->execute();
        $dues_res = $stmt_dues->get_result();
        if ($row_d = $dues_res->fetch_assoc()) {
            $total_dues = (float)$row_d['TotalDues'];
        }
        $stmt_dues->close();
    }

    if (isset($_GET['success']) && $_GET['success'] == 'paid') {
        echo "<div class='alert alert-success'>Your payment was successfully debited! Remaining due: ₹ " . number_format($total_dues, 2) . "</div>";
    }
    
    if ($hall_ticket === '') {
        echo '<p>Please login to view your bills.</p>';
    } else {
        ?>
        
        <div class="row mb-5 justify-content-center">
            <div class="col-md-8">
                <div class="card p-4 shadow-sm border-0">
                    <h4 class="mb-3">Make a Payment</h4>
                    <?php if ($total_dues < 0) { ?>
                        <div class="alert alert-info border border-info">
                            <strong>Notice:</strong> Your current balance is <strong>₹ <?php echo number_format(abs($total_dues), 2); ?> in excess credit</strong>. You do not need to make any payments until your future bills exceed your credit amount.
                        </div>
                    <?php } else if ($total_dues == 0) { ?>
                        <div class="alert alert-success border border-success">
                            <strong>Notice:</strong> You are fully caught up! You have no pending dues.
                        </div>
                    <?php } ?>
                    <form action="process_payment.php" method="POST">
                        <input type="hidden" name="currency_code" id="current_currency" value="INR">
                        <input type="hidden" name="base_amount" id="base_amount_input" value="<?php echo $total_dues; ?>">
                        
                        <div class="form-group">
                            <label>Display Currency:</label>
                            <select id="currency_selector" class="form-control">
                                <option value="INR" data-sym="₹">INR (₹) - Indian Rupee</option>
                                <option value="USD" data-sym="$">USD ($) - US Dollar</option>
                                <option value="EUR" data-sym="€">EUR (€) - Euro</option>
                                <option value="GBP" data-sym="£">GBP (£) - British Pound</option>
                                <option value="NGN" data-sym="₦">NGN (₦) - Nigerian Naira</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Mode of Transaction:</label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="Credit / Debit Card">Credit / Debit Card</option>
                                <option value="UPI Wallet">UPI Wallet</option>
                                <option value="Bank Transfer">Direct Bank Transfer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Amount to Pay:</label>
                            <input type="number" name="amount" id="pay_amount_input" class="form-control" step="0.01" min="0.01" value="<?php echo $total_dues > 0 ? $total_dues : ''; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg btn-block" <?php if($total_dues <= 0) echo 'disabled'; ?>>Proceed to Secure Checkout</button>
                    </form>
                </div>
            </div>
        </div>

        <h4>Payment History</h4>
        <?php
        $query = "SELECT * FROM Payment WHERE Student_id = ? AND transaction_type IN ('Payment', 'Credit') ORDER BY payment_date DESC, id DESC";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $hall_ticket);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                ?>
                <table class="table table-bordered table-hover payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['payment_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                    echo "<td style='color:green; font-weight:bold;' class='dynamic-amount' data-base='" . htmlspecialchars($row['amount']) . "'>Student paid ₹" . htmlspecialchars($row['amount']) . " Rupees</td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p>No ledger history found.</p>";
            }
            $stmt->close();
        }
    }
    ?>
</div>
<br><br><br>

<footer class="py-5">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center"><a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT</a></div>
    </div>
</footer>

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let baseDues = <?php echo $total_dues; ?>;
    let currentRates = {'INR': 1}; // Default map
    
    function updateUI(curr, sym, rate) {
        let convertedDues = (baseDues * rate).toFixed(2);
        
        let headerAmount = document.getElementById('display_total_dues');
        if(headerAmount) headerAmount.innerText = sym + ' ' + convertedDues + ' ' + curr;
        
        let tableAmounts = document.querySelectorAll('.dynamic-amount');
        tableAmounts.forEach(function(el) {
            let val = parseFloat(el.getAttribute('data-base'));
            let prefix = el.style.color === 'green' ? '-' : '';
            el.innerText = prefix + sym + (val * rate).toFixed(2);
        });
        
        let payInput = document.getElementById('pay_amount_input');
        let hiddenInput = document.getElementById('base_amount_input');
        if(payInput) {
            payInput.value = convertedDues;
            payInput.max = convertedDues;
            if(hiddenInput) hiddenInput.value = baseDues;
            
            // Clean up old listeners to prevent stacking
            let newPayInput = payInput.cloneNode(true);
            payInput.parentNode.replaceChild(newPayInput, payInput);
            
            newPayInput.addEventListener('input', function() {
                 let val = parseFloat(this.value);
                 if(!isNaN(val) && val > 0){
                     if(hiddenInput) hiddenInput.value = (val / rate).toFixed(2);
                 }
            });
        }
        
        let currentCurrency = document.getElementById('current_currency');
        if(currentCurrency) currentCurrency.value = curr;
    }
    
    let currencySelector = document.getElementById('currency_selector');
    
    // Live ER-API Fetching
    fetch('https://open.er-api.com/v6/latest/INR')
      .then(response => response.json())
      .then(data => {
         if(data && data.rates) {
             currentRates = data.rates;
         }
      }).catch(e => { console.log('Exchange API Error'); });
      
    // Listen for manual user changes
    if (currencySelector) {
        currencySelector.addEventListener('change', function() {
            let curr = this.value;
            let sym = this.options[this.selectedIndex].getAttribute('data-sym');
            let rate = currentRates[curr] || 1;
            if(baseDues > 0) updateUI(curr, sym, rate);
        });
    }

    // Attach listener immediately for default state
    let initPayInput = document.getElementById('pay_amount_input');
    let hiddenInput = document.getElementById('base_amount_input');
    if (initPayInput && hiddenInput) {
        initPayInput.addEventListener('input', function() {
            let val = parseFloat(this.value);
            let rate = currentRates[document.getElementById('current_currency').value] || 1;
            if(!isNaN(val) && val > 0){
                hiddenInput.value = (val / rate).toFixed(2);
            }
        });
    }
});
</script>
</body>
</html>
