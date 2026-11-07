<?php
  require 'includes/config.inc.php';
  require_once 'includes/sms_gateway.php';

  if (!isset($_SESSION['hostel_man_id'])) {
      header("Location: login-hostel_manager.php");
      exit();
  }

  $hostel_id = $_SESSION['hostel_id'] ?? '';
  $hostel_man_id = $_SESSION['hostel_man_id'] ?? 'Manager';

  // 1. GENERATE INDIVIDUAL MESS CHARGES
  if (isset($_POST['generate_mess'])) {
      $amounts = $_POST['mess_amounts'] ?? []; // Associative array: student_id => amount
      $generated_count = 0;
      $bill_date = date('Y-m-d');
      $bill_month_str = date('Y-m');

      foreach ($amounts as $sid => $amount) {
          if (empty($amount) || $amount <= 0) continue;
          
          $check_sql = "SELECT id FROM Payment WHERE Student_id = ? AND category = 'Mess' AND DATE_FORMAT(payment_date, '%Y-%m') = ?";
          $stmt = $conn->prepare($check_sql);
          $stmt->bind_param("ss", $sid, $bill_month_str);
          $stmt->execute();
          $res = $stmt->get_result();
          
          if ($res->num_rows == 0) {
              $ins_pay = $conn->prepare("INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Pending', 'Charge', 'Mess')");
              $ins_pay->bind_param("sss", $sid, $amount, $bill_date);
              $ins_pay->execute();
              
              $msg_text = "H-Track: A new Mess Fee of {$amount} for " . date('F Y') . " has been charged.";
              
              $ins_msg = $conn->prepare("INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, sender_type, receiver_type) VALUES (?, ?, ?, 'New Mess Fee', ?, ?, ?, 'manager', 'student')");
              $md = date('Y-m-d'); $mt = date('h:i A');
              $ins_msg->bind_param("ssisss", $hostel_man_id, $sid, $hostel_id, $msg_text, $md, $mt);
              $ins_msg->execute();

              $mob_query = $conn->query("SELECT Mob_no FROM Student WHERE Student_id = '$sid'");
              if ($mob_row = $mob_query->fetch_assoc()) {
                  if (!empty($mob_row['Mob_no'])) send_student_sms($mob_row['Mob_no'], $msg_text);
              }
              $generated_count++;
          }
      }
      echo "<script>alert('{$generated_count} New Individual Mess Fees Generated!'); window.location.href='payments.php';</script>";
  }

  // 3. AWARD SCHOLARSHIP
  if (isset($_POST['grant_scholarship'])) {
      $sid = $_POST['student_id'];
      $amount = $_POST['scholar_amount'];
      $date = date('Y-m-d');
      
      $check_stu = $conn->prepare("SELECT Mob_no FROM Student WHERE Student_id = ? AND Hostel_id = ?");
      $check_stu->bind_param("ss", $sid, $hostel_id);
      $check_stu->execute();
      $s_res = $check_stu->get_result();
      if ($s_res->num_rows > 0) {
          $s_row = $s_res->fetch_assoc();
          $ins = $conn->prepare("INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Paid', 'Credit', 'Scholarship')");
          $ins->bind_param("sss", $sid, $amount, $date);
          if ($ins->execute()) {
               $msg_text = "H-Track: {$amount} Scholarship credited to your account! Total Dues reduced.";
               $md = date('Y-m-d'); $mt = date('h:i A');
               $ins_msg = $conn->prepare("INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, sender_type, receiver_type) VALUES (?, ?, ?, 'Scholarship Applied', ?, ?, ?, 'manager', 'student')");
               $ins_msg->bind_param("ssisss", $hostel_man_id, $sid, $hostel_id, $msg_text, $md, $mt);
               $ins_msg->execute();
               if (!empty($s_row['Mob_no'])) send_student_sms($s_row['Mob_no'], $msg_text);
               echo "<script>alert('Scholarship successfully applied to {$sid}!'); window.location.href='payments.php';</script>";
          }
      } else {
          echo "<script>alert('Error: Student ID not found in your hostel.');</script>";
      }
  }

  // 4. PROCESS REFUND
  if (isset($_POST['process_refund'])) {
      $sid = $_POST['refund_student_id'];
      $amount = $_POST['refund_amount'];
      $date = date('Y-m-d');
      
      $check_stu = $conn->prepare("SELECT Mob_no FROM Student WHERE Student_id = ? AND Hostel_id = ?");
      $check_stu->bind_param("ss", $sid, $hostel_id);
      $check_stu->execute();
      $s_res = $check_stu->get_result();
      if ($s_res->num_rows > 0) {
          // A refund is essentially a 'Charge' because it debits the ledger, returning money to the student and pulling their negative balance up towards 0.
          $ins = $conn->prepare("INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Paid', 'Charge', 'Refund Disbursement')");
          $ins->bind_param("sss", $sid, $amount, $date);
          if ($ins->execute()) {
               echo "<script>alert('Refund of ₹{$amount} successfully processed for {$sid}!'); window.location.href='payments.php';</script>";
          }
      } else {
          echo "<script>alert('Error: Student ID not found in your hostel.');</script>";
      }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title> Manage Hostel Bills & Ledger </title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> 
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> 
	<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> 
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet">
</head>

<body>
<div class="inner-page-banner" id="home"> 	   
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">
				<h1><a class="navbar-brand" href="home_manager.php">H-Track<span class="display"></span></a></h1>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item">
							<a class="nav-link" href="home_manager.php">Home <span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="allocate_room.php">Allocate Rooms <?php if(isset($pending_app_count) && $pending_app_count > 0) { echo '<span class="badge badge-danger">'.$pending_app_count.'</span>'; } ?></a>
						</li>
					    <li class="nav-item"><a class="nav-link" href="message_hostel_manager.php">Messages <?php if(isset($unread_msg_manager_count) && $unread_msg_manager_count > 0) { echo '<span class="badge badge-info">'.$unread_msg_manager_count.'</span>'; } ?></a></li>
					    <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Rooms <b class="caret"></b></a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li><a href="allocated_rooms.php">Allocated Rooms</a></li>
							<li><a href="empty_rooms.php">Empty Rooms</a></li>
							<li><a href="vacate_rooms.php">Vacate Rooms</a></li>
						</ul>
					    </li>
                        <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Manage <b class="caret"></b></a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li><a href="attendance.php">Attendance</a></li>
							<li><a href="payments.php">Payments</a></li>
							<li><a href="visitors.php">Visitors</a></li>
						</ul>
					    </li>
					    <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo $_SESSION['username']; ?> <b class="caret"></b></a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li><a href="admin/manager_profile.php">My Profile</a></li>
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
<h2 class="heading text-capitalize mb-sm-5 mb-4"> Manage Hostel Bills </h2>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card p-3 shadow-sm border-0" style="background:#e8f4fd;">
            <h4><i class="fas fa-graduation-cap"></i> Grant Scholarship</h4><br>
            <form action="payments.php" method="post" class="form-group mb-0">
                <label>Student ID:</label>
                <input type="text" name="student_id" class="form-control mb-2" required>
                <label>Scholarship Amount:</label>
                <input type="number" name="scholar_amount" class="form-control mb-3" required step="0.01">
                <input type="submit" name="grant_scholarship" class="btn btn-success btn-block" value="Apply Credit to Account">
            </form>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card p-3 shadow-sm border-0" style="background:#fff3cd;">
            <h4><i class="fas fa-hand-holding-usd"></i> Process Refund</h4><br>
            <p class="text-muted small">Refund excess balances (from Scholarships) back to students so they can vacate cleanly without pending credit.</p>
            <form action="payments.php" method="post" class="form-group mb-0">
                <label>Student ID:</label>
                <input type="text" name="refund_student_id" class="form-control mb-2" required>
                <label>Refund Amount (Rs):</label>
                <input type="number" name="refund_amount" class="form-control mb-3" required step="0.01">
                <input type="submit" name="process_refund" class="btn btn-warning btn-block" value="Issue Refund (Debits Excess Credit)">
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card p-3 shadow-sm border-0">
            <h4><i class="fas fa-utensils"></i> Assign Monthly Mess Charges based on Attendance</h4>
            <p class="text-muted">Enter individual amounts according to their recorded attendance days for this month.</p>
            <form action="payments.php" method="post">
                <table class="table table-bordered table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Days Present (This Month)</th>
                            <th>Charge Amount (N)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $curr_m_y = date('Y-m');
                        $a_query = "SELECT s.Student_id, s.Fname, s.Lname, 
                                    (SELECT COUNT(*) FROM Attendance WHERE Student_id = s.Student_id AND status = 'Present' AND DATE_FORMAT(date, '%Y-%m') = '$curr_m_y') as DaysPresent 
                                    FROM Student s WHERE s.Hostel_id = '$hostel_id'";
                        $a_result = mysqli_query($conn, $a_query);
                        if ($a_result && mysqli_num_rows($a_result) > 0) {
                            while($arow = mysqli_fetch_assoc($a_result)){
                                // Fallback to 0 if null
                                $days = $arow['DaysPresent'] ? $arow['DaysPresent'] : 0;
                                echo "<tr>
                                        <td>{$arow['Student_id']}</td>
                                        <td>{$arow['Fname']} {$arow['Lname']}</td>
                                        <td><strong>{$days} Days</strong></td>
                                        <td><input type='number' name='mess_amounts[{$arow['Student_id']}]' class='form-control' step='0.01' placeholder='Enter Amount...'></td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No students allocated to this hostel.</td></tr>";
                        }
                    ?>
                    </tbody>
                </table>
                <input type="submit" name="generate_mess" class="btn btn-primary btn-block" value="Submit Individual Mess Bills">
            </form>
        </div>
    </div>
</div>

<br>
<h4>Recent Ledger Transactions</h4>
<table class="table table-hover mt-4">
  <thead>
    <tr>
      <th>Date</th>
      <th>Student ID</th>
      <th>Description</th>
      <th>Type</th>
      <th>Amount</th>
    </tr>
  </thead>
  <tbody>
  <?php
    if(isset($_SESSION['hostel_id'])){
        $hostel_id = $_SESSION['hostel_id'];
        $query = "SELECT p.*, s.Fname, s.Lname 
                  FROM Payment p 
                  JOIN Student s ON p.Student_id = s.Student_id 
                  WHERE s.Hostel_id = '$hostel_id'
                  ORDER BY p.payment_date DESC, p.id DESC LIMIT 100";
        $result = mysqli_query($conn, $query);
        if(mysqli_num_rows($result)==0){
           echo '<tr><td colspan="5">No Transactions Found</td></tr>';
        } else {
           while($row = mysqli_fetch_assoc($result)){
               $txColor = $row['transaction_type'] == 'Charge' ? 'red' : 'green';
               $sign = $row['transaction_type'] == 'Charge' ? '' : '-';
               echo "<tr>
                       <td>{$row['payment_date']}</td>
                       <td>{$row['Student_id']} ({$row['Fname']} {$row['Lname']})</td>
                       <td>{$row['category']}</td>
                       <td><span style='color:{$txColor}; font-weight:bold;'>{$row['transaction_type']}</span></td>
                       <td style='color:{$txColor}; font-weight:bold;'>{$sign}{$row['amount']}</td>
                     </tr>";
           }
        }
    }
  ?>
  </tbody>
</table>
</div>
<br><br><br>

<footer class="py-5">
	<div class="container py-md-5">
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand" href="#">ESUT<span class="display"></span></a>
		</div>
	</div>
</footer>

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
</body>
</html>
