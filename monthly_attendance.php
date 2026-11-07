<?php
require 'includes/config.inc.php';

if (!isset($_SESSION['hostel_man_id'])) {
    header("Location: login-hostel_manager.php");
    exit();
}

$hostel_id = $_SESSION['hostel_id'];
$manager_id = $_SESSION['hostel_man_id'];

// Handle Mess Bill Assignment
if (isset($_POST['assign_mess_bill'])) {
    $student_id = $_POST['student_id'];
    $month_year = $_POST['month_year'];
    $amount = $_POST['mess_amount'];
    
    // Check if bill already exists
    $cat_name = "Mess Bill - " . $month_year;
    $chk = $conn->prepare("SELECT id FROM Payment WHERE Student_id = ? AND category = ?");
    $chk->bind_param("ss", $student_id, $cat_name);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo "<script>alert('A Mess Bill for $month_year has already been assigned to this student!');</script>";
    } else {
        $ins = $conn->prepare("INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Pending', 'Charge', ?)");
        $date = date('Y-m-d');
        $ins->bind_param("ssss", $student_id, $amount, $date, $cat_name);
        if ($ins->execute()) {
            // Also notify student
            $msg = "A new Mess Bill of ₹$amount for $month_year has been added to your Account Dues.";
            $mt = date('h:i A');
            $imsg = $conn->prepare("INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, sender_type, receiver_type) VALUES (?, ?, ?, 'New Mess Bill Assinged', ?, ?, ?, 'manager', 'student')");
            $imsg->bind_param("ssisss", $manager_id, $student_id, $hostel_id, $msg, $date, $mt);
            $imsg->execute();
            echo "<script>alert('Mess Bill assigned successfully!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Manager - Monthly Attendance & Mess Bills</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> 
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> 
	<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> 
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
						<li class="nav-item"><a class="nav-link" href="home_manager.php">Home</a></li>
						<li class="nav-item"><a class="nav-link" href="allocate_room.php">Allocate Rooms <?php if(isset($pending_app_count) && $pending_app_count > 0) { echo '<span class="badge badge-danger">'.$pending_app_count.'</span>'; } ?></a></li>
					    <li class="nav-item"><a class="nav-link" href="message_hostel_manager.php">Messages <?php if(isset($unread_msg_manager_count) && $unread_msg_manager_count > 0) { echo '<span class="badge badge-info">'.$unread_msg_manager_count.'</span>'; } ?></a></li>
                        <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Manage <b class="caret"></b></a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li><a href="attendance.php">Attendance</a></li>
							<li><a href="payments.php">Payments</a></li>
							<li><a href="visitors.php">Visitors</a></li>
						</ul>
					    </li>
					    <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($_SESSION['username']); ?> <b class="caret"></b></a>
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
    <div class="clearfix mb-4">
        <h2 class="heading text-capitalize float-left"> Monthly Attendance & Mess Bills </h2>
        <a href="attendance.php" class="btn btn-secondary float-right">Back to Daily Attendance</a>
    </div>

    <!-- Month Filter -->
    <form method="GET" action="monthly_attendance.php" class="form-inline mb-4">
        <label class="mr-2 font-weight-bold">Select Month:</label>
        <select name="month_filter" class="form-control mr-3">
            <?php
            $m_query = "SELECT DISTINCT DATE_FORMAT(date, '%M %Y') as myear FROM attendance WHERE Student_id IN (SELECT Student_id FROM Student WHERE Hostel_id = ?) ORDER BY MAX(date) DESC";
            $mst = $conn->prepare($m_query);
            $mst->bind_param("s", $hostel_id);
            $mst->execute();
            $mres = $mst->get_result();
            $months = [];
            while($mrow = $mres->fetch_assoc()) {
                $months[] = $mrow['myear'];
            }
            if(empty($months)) {
                $months[] = date('F Y'); 
            }
            $selected_month = $_GET['month_filter'] ?? $months[0];
            foreach($months as $m) {
                $sel = ($m === $selected_month) ? 'selected' : '';
                echo "<option value='".htmlspecialchars($m)."' $sel>".htmlspecialchars($m)."</option>";
            }
            ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <table class="table table-bordered table-hover" style="background:#fff;">
        <thead class="thead-dark">
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Days Present</th>
                <th>Mess Bill Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT s.Student_id, s.Fname, s.Lname, 
                SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days 
                FROM Student s 
                LEFT JOIN attendance a ON s.Student_id = a.Student_id AND DATE_FORMAT(a.date, '%M %Y') = ?
                WHERE s.Hostel_id = ? 
                GROUP BY s.Student_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $selected_month, $hostel_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sid = $row['Student_id'];
                $sname = $row['Fname'] . " " . $row['Lname'];
                $pdays = $row['present_days'] ?? 0;

                // Check if mess bill assigned
                $cat_check = "Mess Bill - " . $selected_month;
                $chk = $conn->prepare("SELECT amount FROM Payment WHERE Student_id = ? AND category = ?");
                $chk->bind_param("ss", $sid, $cat_check);
                $chk->execute();
                $cres = $chk->get_result();

                echo "<tr>";
                echo "<td>".htmlspecialchars($sid)."</td>";
                echo "<td>".htmlspecialchars($sname)."</td>";
                echo "<td style='font-size:18px;'><strong>{$pdays}</strong> days</td>";
                
                echo "<td>";
                if ($cres->num_rows > 0) {
                    $crow = $cres->fetch_assoc();
                    echo "<span style='color:green; font-weight:bold;'><i class='fas fa-check-circle'></i> Assigned: ₹".htmlspecialchars($crow['amount'])."</span>";
                } else {
                    // Show form
                    echo "<form method='POST' action='monthly_attendance.php' class='form-inline'>
                            <input type='hidden' name='student_id' value='".htmlspecialchars($sid)."'>
                            <input type='hidden' name='month_year' value='".htmlspecialchars($selected_month)."'>
                            <div class='input-group input-group-sm'>
                                <div class='input-group-prepend'><span class='input-group-text'>₹</span></div>
                                <input type='number' step='0.01' name='mess_amount' class='form-control' placeholder='Enter Amount' required min='0'>
                                <div class='input-group-append'>
                                    <button type='submit' name='assign_mess_bill' class='btn btn-warning'>Assign</button>
                                </div>
                            </div>
                          </form>";
                }
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center'>No students found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<br><br><br>

<footer class="py-5">
	<div class="container py-md-5">
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand" href="#">ESUT</a>
		</div>
	</div>
</footer>

<script src="web_home/js/jquery-2.2.3.min.js"></script>
<script src="web_home/js/bootstrap.js"></script>
</body>
</html>
