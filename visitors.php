<?php
  require 'includes/config.inc.php';
  require_once 'includes/sms_gateway.php';

  // Handle Decline
  if (isset($_GET['decline_req_id'])) {
      $req_id = $_GET['decline_req_id'];
      $conn->query("UPDATE Visitor_Requests SET status='Declined' WHERE id='$req_id'");
      $r_q = $conn->query("SELECT mobile_no, visitor_name, student_id FROM Visitor_Requests WHERE id='$req_id'");
      if ($r_q && $r_row = $r_q->fetch_assoc()) {
          $guest_mob = $r_row['mobile_no'];
          $vname = $r_row['visitor_name'];
          $sid = $r_row['student_id'];
          if(!empty($guest_mob)) send_student_sms($guest_mob, "H-Track: Apologies, your visit request has been DECLINED by the manager.");
          
          $s_q = $conn->query("SELECT Mob_no FROM Student WHERE Student_id = '$sid'");
          if ($s_q && $s_row = $s_q->fetch_assoc()) {
             $stu_mob = $s_row['Mob_no'];
             if(!empty($stu_mob)) send_student_sms($stu_mob, "H-Track: A visit request from $vname was DECLINED by the manager.");
          }
      }
      echo "<script>alert('Status updated to Declined and SMS sent via Gateway.'); window.location.href='visitors.php';</script>";
  }

  if (isset($_POST['add_visitor'])) {
      $vname = $_POST['visitor_name'];
      $sid = $_POST['student_id'];
      $staying = $_POST['staying'] ?? 'No';
      $date = date('Y-m-d');
      $charge = "100.00"; // Fixed 1 day charge
      
      // First check if the Student ID actually exists in the system
      $check_student = $conn->prepare("SELECT * FROM Student WHERE Student_id = ?");
      $check_student->bind_param("s", $sid);
      $check_student->execute();
      $res = $check_student->get_result();

      if ($res->num_rows == 0) {
          echo "<script>alert('Error: The Student ID does not exist in the database.');</script>";
      } else {
          $msg = "Visitor Logged Successfully! Charged 1 day fee.";

          if ($staying === 'Yes') {
              $charge = "500.00"; // Higher charge for staying
              $stmt = $conn->prepare("INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Pending', 'Charge', 'Visitor Stay')");
              $stmt->bind_param("sss", $sid, $charge, $date);
              $stmt->execute();
              $msg = "Visitor Logged! Student account charged ₹$charge for stay.";
          }

          $ins_visitor = $conn->prepare("INSERT INTO Visitor (visitor_name, Student_id, visit_date, charge, status) VALUES (?, ?, ?, ?, 'Pending')");
          $ins_visitor->bind_param("ssss", $vname, $sid, $date, $charge);
          $ins_visitor->execute();

          // Handle SMS Approval if it's tied to a request
          if (isset($_POST['req_id']) && !empty($_POST['req_id'])) {
             $req_id = $_POST['req_id'];
             $conn->query("UPDATE Visitor_Requests SET status='Approved' WHERE id='$req_id'");
             $r_q = $conn->query("SELECT mobile_no FROM Visitor_Requests WHERE id='$req_id'");
             if ($r_q && $r_row = $r_q->fetch_assoc()) {
                $guest_mob = $r_row['mobile_no'];
                if(!empty($guest_mob)) send_student_sms($guest_mob, "H-Track: Your Visit Request has been APPROVED! Please proceed to the hostel.");
             }
             $s_q = $conn->query("SELECT Mob_no FROM Student WHERE Student_id = '$sid'");
             if ($s_q && $s_row = $s_q->fetch_assoc()) {
                $stu_mob = $s_row['Mob_no'];
                if(!empty($stu_mob)) send_student_sms($stu_mob, "H-Track: A Visit Request from $vname has been APPROVED. They will arrive soon.");
             }
             $msg .= " SMS notifications sent automatically.";
          }
          
          echo "<script>alert('$msg');</script>";
      }
  }

  if (isset($_GET['pay_visitor_id'])) {
      $vid = $_GET['pay_visitor_id'];
      mysqli_query($conn, "UPDATE Visitor SET status='Paid' WHERE id='$vid'");
      echo "<script>window.location.href='visitors.php';</script>";
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title> Visitor Management </title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<meta name="keywords" content="" />
	<script type="application/x-javascript">
		addEventListener("load", function () {
			setTimeout(hideURLbar, 0);
		}, false);
		function hideURLbar() {
			window.scrollTo(0, 1);
		}
	</script>
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
					    <li class="nav-item">
						    <a class="nav-link" href="message_hostel_manager.php">Messages <?php if(isset($unread_msg_manager_count) && $unread_msg_manager_count > 0) { echo '<span class="badge badge-info">'.$unread_msg_manager_count.'</span>'; } ?></a>
					    </li>
					    <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Rooms
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li><a href="allocated_rooms.php">Allocated Rooms</a></li>
							<li><a href="empty_rooms.php">Empty Rooms</a></li>
							<li><a href="vacate_rooms.php">Vacate Rooms</a></li>
						</ul>
					    </li>
                        <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Manage
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li><a href="attendance.php">Attendance</a></li>
							<li><a href="payments.php">Payments</a></li>
							<li><a href="visitors.php">Visitors</a></li>
						</ul>
					    </li>
					    <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo $_SESSION['username']; ?>
							<b class="caret"></b>
						</a>
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
<br><br><br>

<div class="container">
<h2 class="heading text-capitalize mb-sm-5 mb-4"> Visitor Management </h2>

<div class="row">
    <div class="col-md-5">
        <?php
           $auto_vname = ''; $auto_sid = ''; $auto_stay = 'No'; $auto_req_id = '';
           if (isset($_GET['approve_req_id'])) {
               $aid = $_GET['approve_req_id'];
               $aq = $conn->query("SELECT * FROM Visitor_Requests WHERE id='$aid'");
               if ($aq && $ar = $aq->fetch_assoc()) {
                   $auto_vname = $ar['visitor_name'];
                   $auto_sid = $ar['student_id'];
                   $auto_stay = $ar['staying_overnight'];
                   $auto_req_id = $aid;
               }
           }
        ?>
        <h4>Register New Visitor</h4><br>
        <form action="visitors.php" method="post" class="form-group">
            <?php if($auto_req_id !== '') { ?>
                <div class="alert alert-info">Auto-filled from Pending Request #<?php echo $auto_req_id; ?></div>
                <input type="hidden" name="req_id" value="<?php echo htmlspecialchars($auto_req_id); ?>">
            <?php } ?>
            <label>Visitor Name:</label>
            <input type="text" name="visitor_name" class="form-control" value="<?php echo htmlspecialchars($auto_vname); ?>" required>
            <br>
            <label>Visiting Student (Student ID):</label>
            <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($auto_sid); ?>" required>
            <br>
            <label>Staying in Hostel?</label>
            <select name="staying" class="form-control" required>
                <option value="No" <?php if($auto_stay == 'No') echo 'selected'; ?>>No (₹100 charge on Visitor)</option>
                <option value="Yes" <?php if($auto_stay == 'Yes') echo 'selected'; ?>>Yes (₹500 charge to Student Account)</option>
            </select>
            <br>
            <input type="submit" name="add_visitor" class="btn btn-primary" value="Register Visitor">
        </form>
    </div>
    
    <!-- PENDING REQUESTS -->
    <div class="col-md-7">
        <h4><i class="fas fa-bell"></i> Pending Visitor Requests</h4><br>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead style="background:#ffc107;">
                    <tr>
                        <th>Visitor</th>
                        <th>Student</th>
                        <th>Arrival Schedule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                   if(isset($_SESSION['hostel_id'])) {
                       $hid = $_SESSION['hostel_id'];
                       $q = "SELECT r.*, s.Fname, s.Lname FROM Visitor_Requests r JOIN Student s ON r.student_id = s.Student_id WHERE s.Hostel_id = '$hid' AND r.status = 'Pending' ORDER BY r.request_date ASC";
                       $res_r = $conn->query($q);
                       if($res_r && $res_r->num_rows > 0) {
                           while($row = $res_r->fetch_assoc()) {
                               $d = htmlspecialchars($row['visit_date']);
                               $t = htmlspecialchars($row['visit_time']);
                               echo "<tr>";
                               echo "<td><strong>{$row['visitor_name']}</strong><br><small>{$row['mobile_no']}</small></td>";
                               echo "<td>{$row['student_id']}<br><small class='text-muted'>{$row['Fname']} {$row['Lname']}</small></td>";
                               echo "<td><strong>$d at $t</strong><br><small class='text-muted'>{$row['relation']} | {$row['hours']}h | Stay: {$row['staying_overnight']}</small></td>";
                               echo "<td>
                                       <a href='visitors.php?approve_req_id={$row['id']}' class='btn btn-success btn-sm mb-1'>Approve</a>
                                       <br>
                                       <a href='visitors.php?decline_req_id={$row['id']}' class='btn btn-danger btn-sm' onclick=\"return confirm('Disapprove this visit?');\">Decline</a>
                                     </td>";
                               echo "</tr>";
                           }
                       } else {
                           echo "<tr><td colspan='4' class='text-center'>No pending requests.</td></tr>";
                       }
                   }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<br><br>

<h4>Visitor Logs & Payments</h4>
<table class="table table-hover mt-4">
  <thead>
    <tr>
      <th>Visitor Name</th>
      <th>Student ID</th>
      <th>Visit Date</th>
      <th>Charge (1 Day)</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php
    if(isset($_SESSION['hostel_id'])){
        $hostel_id = $_SESSION['hostel_id'];
        $query = "SELECT v.* FROM Visitor v 
                  JOIN Student s ON v.Student_id = s.Student_id 
                  WHERE s.Hostel_id = '$hostel_id'
                  ORDER BY v.visit_date DESC";
        $result = mysqli_query($conn, $query);
        if(mysqli_num_rows($result)==0){
           echo '<tr><td colspan="6">No Visitors Logged</td></tr>';
        } else {
           while($row = mysqli_fetch_assoc($result)){
               echo "<tr>
                       <td>{$row['visitor_name']}</td>
                       <td>{$row['Student_id']}</td>
                       <td>{$row['visit_date']}</td>
                       <td>{$row['charge']}</td>
                       <td>{$row['status']}</td>
                       <td>";
               if($row['status'] == 'Pending') {
                   echo "<a href='visitors.php?pay_visitor_id={$row['id']}' class='btn btn-success btn-sm'>Mark Paid</a>";
               } else {
                   echo "<button class='btn btn-secondary btn-sm' disabled>Paid</button>";
               }
               echo "</td></tr>";
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
