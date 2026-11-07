<?php require 'includes/config.inc.php'; 
 date_default_timezone_set('Asia/Kolkata'); 

$hall_ticket = $_SESSION['hall_ticket'] ?? '';

if ($hall_ticket === '') {
    header("Location: index.php");
    exit();
}
// Validate Manager Assignment
if (!isset($manager_assigned) || !$manager_assigned) {
    echo "<!DOCTYPE html><html lang='en'><head><title>Access Denied</title><link rel='stylesheet' href='web_home/css_home/bootstrap.css'><style>body{background:#f8f9fa;display:flex;align-items:center;justify-content:center;height:100vh;}</style></head><body>
    <div class='container text-center'><div class='card shadow p-5'><h1 class='text-danger mb-4'>Access Restricted</h1><p class='lead'>Your hostel bills have not been activated yet. Please wait until a manager is assigned to your hostel.</p><a href='home.php' class='btn btn-primary mt-3'>Return to Home</a></div></div>
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
                        <li class="nav-item active"><a class="nav-link" href="hostel_bills.php">Hostel Bills</a></li>
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

<br><br>
<div class="container">
    <h2 class="heading text-capitalize mb-sm-5 mb-4"> Hostel Bills & Ledger </h2>
    
    <?php
    $total_dues = 0;
    if ($hall_ticket !== '') {
        // Calculate Total Dues
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

    $card_class = $total_dues > 0 ? 'dues-card' : 'dues-card paid';
    ?>
    
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card p-4 <?php echo $card_class; ?>">
                <h3 class="mb-3">Total Outstanding Dues</h3>
                <?php if ($total_dues > 0) { ?>
                    <h1 id="display_total_dues" style="color: #d9534f;">₹ <?php echo number_format($total_dues, 2); ?> INR</h1>
                    <p class="text-muted mt-2">Includes Mess, Maintenance, and Security Deposits.</p>
                <?php } else if ($total_dues < 0) { ?>
                    <h1 id="display_total_dues" style="color: #007bff;">₹ <?php echo number_format(abs($total_dues), 2); ?> INR <small>(Excess Credit)</small></h1>
                    <p class="text-muted mt-2">You have excess funds (Scholarship or Refund credit) waiting in your account!</p>
                <?php } else { ?>
                    <h1 id="display_total_dues" style="color: #28a745;">₹ 0.00 INR</h1>
                    <p class="text-muted mt-2">You are all caught up! No pending dues.</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <h4>Generated Bills</h4>
    <?php
    // Only show Charges
    $query = "SELECT * FROM Payment WHERE Student_id = ? AND transaction_type = 'Charge' ORDER BY payment_date DESC, id DESC";
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
                echo "<td style='color:red; font-weight:bold;'>Student charged ₹" . htmlspecialchars($row['amount']) . " Rupees</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No bills have been generated for your account yet.</p>";
        }
        $stmt->close();
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
</body>
</html>
