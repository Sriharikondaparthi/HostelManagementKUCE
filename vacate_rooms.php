<?php
  require 'includes/config.inc.php';
  
  if (!isset($_SESSION['hostel_man_id'])) {
      header("Location: login-hostel_manager.php");
      exit();
  }
  
  $hostel_id = $_SESSION['hostel_id'];
  
  // Vacate Action removed: Only Admin can vacate students now.
  $query1 = "SELECT * FROM Hostel WHERE Hostel_id = '$hostel_id'";
  $result1 = mysqli_query($conn,$query1);
  $row1 = mysqli_fetch_assoc($result1);
  $hostel_name = $row1['Hostel_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title> View Vacated Students | Manager </title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> 
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> 
	<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> 
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet">
</head>

<body>

<!-- banner -->
<div class="inner-page-banner" id="home"> 	   
	<!--Header-->
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">
				
				<h1><a class="navbar-brand" href="home_manager.php"><span class="display">H-Track</span></a></h1>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item">
							<a class="nav-link" href="home_manager.php">Home <span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item">
						<a class="nav-link" href="allocate_room.php">Allocate Room <?php if(isset($pending_app_count) && $pending_app_count > 0) { echo '<span class="badge badge-danger">'.$pending_app_count.'</span>'; } ?></a>
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
					<li class="nav-item"><a class="nav-link" href="contact_manager.php">Contact</a></li>
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
	<h2 class="heading text-capitalize mb-sm-5 mb-4"> Live Capacity & Vacated History </h2>
    <p class="mb-4">Hostel Name: <strong><?php echo $hostel_name; ?></strong></p>
    
    <table class="table table-bordered table-striped table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Room No</th>
                <th>Join Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $stu_q = "SELECT s.Student_id, s.Fname, s.Lname, s.Join_date, r.Room_No FROM Student s JOIN Room r ON s.Room_id = r.Room_id WHERE s.Hostel_id = '$hostel_id'";
            $stu_res = mysqli_query($conn, $stu_q);
            if(mysqli_num_rows($stu_res) > 0) {
                while($srow = mysqli_fetch_assoc($stu_res)) {
                    $jd = $srow['Join_date'] ? $srow['Join_date'] : 'Unknown';
                    echo "<tr>
                            <td>{$srow['Student_id']}</td>
                            <td>{$srow['Fname']} {$srow['Lname']}</td>
                            <td>{$srow['Room_No']}</td>
                            <td>{$jd}</td>
                            <td>
                                <button type='button' class='btn btn-secondary btn-sm' title='Only Admin can vacate students' disabled>Admin Only</button>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No students currently allocated to this hostel.</td></tr>";
            }
        ?>
        </tbody>
    </table>
    
    <hr class="my-5">
    <h3 class="heading text-capitalize mb-sm-4 mb-3"> Read-Only Vacated History </h3>
    <p class="mb-4 text-muted">Historical archive of students who have vacated from your hostel. (Data clearing is restricted to System Admins)</p>
    <table class="table table-bordered table-striped table-hover">
        <thead class="thead-light">
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Room No</th>
                <th>Join Date</th>
                <th>Vacated On</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $v_q = "SELECT Student_id, Fname, Lname, Join_date, Vacate_date, Room_id FROM Vacated_Students WHERE Hostel_id = '$hostel_id' ORDER BY Vacate_date DESC LIMIT 100";
            $v_res = mysqli_query($conn, $v_q);
            if(mysqli_num_rows($v_res) > 0) {
                while($vrow = mysqli_fetch_assoc($v_res)) {
                    
                    $r_id = $vrow['Room_id'];
                    $r_q = "SELECT Room_No FROM Room WHERE Room_id = '$r_id'";
                    $r_r = mysqli_query($conn, $r_q);
                    $r_num = "Unknown";
                    if($r_row = mysqli_fetch_assoc($r_r)){
                        $r_num = $r_row['Room_No'];
                    }

                    echo "<tr>
                            <td>{$vrow['Student_id']}</td>
                            <td>{$vrow['Fname']} {$vrow['Lname']}</td>
                            <td>{$r_num}</td>
                            <td>{$vrow['Join_date']}</td>
                            <td>{$vrow['Vacate_date']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center text-muted'>No vacated history found for your hostel.</td></tr>";
            }
        ?>
        </tbody>
    </table>
</div>

<br><br><br>

<footer class="py-5">
	<div class="container py-md-5">
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand" href="https://dresut.vercel.app/">ESUT<span class="display"></span></a>
		</div>
	</div>
</footer>

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
</body>
</html>