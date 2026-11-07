<?php
require 'includes/config.inc.php';

if (isset($_POST['mark_attendance'])) {
	$date = date('Y-m-d');
	$status = $_POST['status'];
	foreach ($status as $student_id => $stu_status) {
		$check = mysqli_query($conn, "SELECT * FROM Attendance WHERE Student_id='$student_id' AND date='$date'");
		if (mysqli_num_rows($check) > 0) {
			mysqli_query($conn, "UPDATE Attendance SET status='$stu_status' WHERE Student_id='$student_id' AND date='$date'");
		}
		else {
			mysqli_query($conn, "INSERT INTO Attendance (Student_id, date, status) VALUES ('$student_id', '$date', '$stu_status')");
		}
	}
	echo "<script>alert('Attendance Updated Successfully!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title> Attendance Tracking</title>
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
<div class="clearfix mb-4">
    <h2 class="heading text-capitalize mb-sm-5 mb-4 float-left"> Student Attendance - <?php echo date('Y-m-d'); ?> </h2>
    <a href="monthly_attendance.php" class="btn btn-warning float-right mt-2" style="font-size: 18px; font-weight: bold;"><i class="fas fa-calendar-alt"></i> Switch to Monthly Summaries & Mess Bills</a>
</div>
<form action="attendance.php" method="post">
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
    <?php
if (isset($_SESSION['hostel_id'])) {
	$hostel_id = $_SESSION['hostel_id'];
	$query = "SELECT * FROM Student WHERE Hostel_id = '$hostel_id'";
	$result = mysqli_query($conn, $query);
	if (mysqli_num_rows($result) == 0) {
		echo '<tr><td colspan="3" style="text-align:center;">No Students Assigned</td></tr>';
		$hide_submit = true;
	}
	else {
		$date = date('Y-m-d');
		$all_submitted = true;
		
		while ($row = mysqli_fetch_assoc($result)) {
			$sid = $row['Student_id'];
			$name = $row['Fname'] . " " . $row['Lname'];

			$ch_query = "SELECT status FROM Attendance WHERE Student_id='$sid' AND date='$date'";
			$ch_res = mysqli_query($conn, $ch_query);
			
			if (mysqli_num_rows($ch_res) > 0) {
				$cr = mysqli_fetch_assoc($ch_res);
				$current_status = $cr['status'];
				// It is submitted for this student, show the text instead of radio buttons
				echo "<tr>
                          <td>{$sid}</td>
                          <td>{$name}</td>
                          <td style='color: #28a745; font-weight: bold;'>
                              Attendance submitted already ({$current_status})
                          </td>
                      </tr>";
			} else {
			    $all_submitted = false; // At least one student needs attendance
				echo "<tr>
                          <td>{$sid}</td>
                          <td>{$name}</td>
                          <td>
                              <input type='radio' name='status[$sid]' value='Present' checked> Present &nbsp;&nbsp;
                              <input type='radio' name='status[$sid]' value='Absent'> Absent
                          </td>
                      </tr>";
			}
		}
		if ($all_submitted) {
		    $hide_submit = true;
		} else {
		    $hide_submit = false;
		}
	}
}
else {
    $hide_submit = true;
	echo '<tr><td colspan="3" style="text-align:center;">Please login as a manager</td></tr>';
}
?>
    </tbody>
  </table>
  <?php if (!isset($hide_submit) || !$hide_submit) { ?>
  <input type="submit" name="mark_attendance" class="btn btn-primary" value="Submit Attendance">
  <?php } ?>
</form>
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
