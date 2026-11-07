<?php 
require 'includes/config.inc.php'; 
date_default_timezone_set('Asia/Kolkata'); 

if (!isset($_SESSION['hall_ticket'])) {
    header("Location: index.php");
    exit();
}

// Handle multiple payment checkout forms
$amount = $_POST['amount'] ?? null;
$base_amount = $_POST['base_amount'] ?? null;
$currency_code = $_POST['currency_code'] ?? 'INR';
$payment_mode = $_POST['payment_mode'] ?? 'Credit / Debit Card';

if(!$amount || !$base_amount){
    header("Location: student_payment.php");
    exit();
}

$amount = floatval($amount);
$base_amount = floatval($base_amount);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    
    // Insert a Ledger Credit for the payment using the BASE AMOUNT so the ledger math stays stable!
    $query = "INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Paid', 'Payment', ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $date = date('Y-m-d');
        $cat = 'Payment via ' . $payment_mode;
        $stmt->bind_param("ssss", $_SESSION['hall_ticket'], $base_amount, $date, $cat);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: student_payment.php?success=paid");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>H-Track - Secure Checkout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
    <link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
    <style>
        .payment-box {
            max-width: 500px;
            margin: 50px auto;
            border: 1px solid #ccc;
            padding: 30px;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }
        .form-group label {
            font-weight: bold;
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
                        <li class="nav-item"><a class="nav-link" href="student_payment.php">Payments</a></li>
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

<div class="container">
    <div class="payment-box">
        <h3 class="text-center mb-4">Secure Checkout: <?php echo htmlspecialchars($payment_mode); ?></h3>
        <p class="text-center">You are making a payment of <strong><?php echo htmlspecialchars($amount) . ' ' . htmlspecialchars($currency_code); ?></strong> against your account.</p>
        <hr>
        <form action="process_payment.php" method="POST">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            <input type="hidden" name="base_amount" value="<?php echo htmlspecialchars($base_amount); ?>">
            <input type="hidden" name="currency_code" value="<?php echo htmlspecialchars($currency_code); ?>">
            <input type="hidden" name="payment_mode" value="<?php echo htmlspecialchars($payment_mode); ?>">
            
            <?php if ($payment_mode == 'Credit / Debit Card') { ?>
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" class="form-control" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" class="form-control" placeholder="1234 5678 9101 1121" required pattern="\d{16}" title="16-digit card number">
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Expiry Date</label>
                        <input type="text" class="form-control" placeholder="MM/YY" required pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>CVV</label>
                        <input type="password" class="form-control" placeholder="123" required pattern="\d{3}">
                    </div>
                </div>
            <?php } else if ($payment_mode == 'UPI Wallet') { ?>
                <div class="text-center mb-4">
                    <i class="fas fa-qrcode fa-5x text-secondary"></i>
                    <p class="mt-3 text-muted">Scan the QR code with your UPI App or enter your UPI ID below.</p>
                </div>
                <div class="form-group">
                    <label>Your UPI ID / VPA</label>
                    <input type="text" class="form-control" placeholder="student@okaxis" required>
                </div>
            <?php } else { ?>
                <div class="form-group">
                    <label>Transaction Reference ID</label>
                    <p class="text-muted small">Transfer funds to standard Hostel Bank Account and provide the transaction reference ID below.</p>
                    <input type="text" class="form-control" placeholder="TXN-123456789" required>
                </div>
            <?php } ?>
            
            <button type="submit" name="submit_payment" class="btn btn-primary btn-block mt-4">Confirm Payment of <?php echo htmlspecialchars($amount) . ' ' . htmlspecialchars($currency_code); ?></button>
            <a href="student_payment.php" class="btn btn-secondary btn-block mt-2">Cancel</a>
        </form>
    </div>
</div>

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
</body>
</html>
