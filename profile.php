<?php
  require 'includes/config.inc.php';
  
  // If not logged in as student, redirect to login
  if (!isset($_SESSION['hall_ticket'])) {
      header("Location: index.php");
      exit();
  }

  // Handle Semester Update
  if (isset($_POST['sem-update'])) {
      $new_sem = trim($_POST['new_sem']);
      if (!empty($new_sem)) {
          $s_id = $_SESSION['hall_ticket'];
          $upd_sql = "UPDATE Student SET Semester = ? WHERE Student_id = ?";
          $upd_stmt = $conn->prepare($upd_sql);
          $upd_stmt->bind_param("ss", $new_sem, $s_id);
          if ($upd_stmt->execute()) {
              $_SESSION['semester'] = $new_sem;
              echo "<script>alert('Semester gracefully updated to Semester " . htmlspecialchars($new_sem) . "!');</script>";
          } else {
              echo "<script>alert('Error updating semester.');</script>";
          }
          $upd_stmt->close();
      }
  }

  // Fetch Student Photo
  $s_id = $_SESSION['hall_ticket'];
  $photo_sql = "SELECT Photo FROM Student WHERE Student_id = ?";
  $photo_stmt = $conn->prepare($photo_sql);
  $photo_stmt->bind_param("s", $s_id);
  $photo_stmt->execute();
  $photo_res = $photo_stmt->get_result();
  $student_photo = "";
  if ($row = $photo_res->fetch_assoc()) {
      $student_photo = $row['Photo'];
  }
  $photo_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>User Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Consultancy Profile Widget Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false);
		function hideURLbar(){ window.scrollTo(0,1); } </script>
<!-- js -->
<script src="web_profile/js/jquery-2.1.3.min.js" type="text/javascript"></script>
<script type="text/javascript" src="web_profile/js/sliding.form.js"></script>
<!-- //js -->
<link href="web_profile/css/style.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" href="web_profile/css/font-awesome.min.css" />
<link rel="stylesheet" href="web_profile/css/smoothbox.css" type='text/css' media="all" />
<link href="//fonts.googleapis.com/css?family=Pathway+Gothic+One" rel="stylesheet">
<link href='//fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic' rel='stylesheet' type='text/css'>

<script type="application/x-javascript">
	addEventListener("load", function () {
		setTimeout(hideURLbar, 0);
	}, false);

	function hideURLbar() {
		window.scrollTo(0, 1);
	}
</script>
<!--// Meta tag Keywords -->

<link href="web_home/css_home/slider.css" type="text/css" rel="stylesheet" media="all">

<!-- css files -->
<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS -->
<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> <!-- Font-Awesome-Icons-CSS -->
<!-- //css files -->

<!-- testimonials css -->
<link rel="stylesheet" href="web_home/css_home/flexslider.css" type="text/css" media="screen" property="" /><!-- flexslider css -->
<!-- //testimonials css -->

<!-- web-fonts -->
<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
<!-- //web-fonts -->


</head>
<body>
	<!-- banner -->
		<div class="banner" id="home">
			<div class="cd-radial-slider-wrapper">

	<!--Header-->
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">

				<h1><a class="navbar-brand" href="home.php">H-Track <span class="display"></span></a></h1>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item active">
							<a class="nav-link" href="home.php">Home <span class="sr-only">(current)</span></a>
						</li>

						<li class="nav-item">
							<a class="nav-link" href="services.php">Hostels</a>
						</li>


						<li class="nav-item">
							<a class="nav-link" href="contact.php">Contact</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="message_user.php">Message Received <?php if(isset($unread_msg_count) && $unread_msg_count > 0) { echo '<span class="badge badge-danger">'.$unread_msg_count.'</span>'; } ?></a>
						</li>
                        <?php if (isset($manager_assigned) && $manager_assigned) { ?>
						<li class="nav-item">
							<a class="nav-link" href="hostel_bills.php">Hostel Bills</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="student_payment.php">Payments</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="attendance_user.php">Attendance</a>
						</li>
                        <?php } ?>
						<li class="dropdown nav-item">
							<!--<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo $_SESSION['hall_ticket']; ?>
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu agile_short_dropdown">
								<li>
									<a href="profile.php">My Profile</a>
								</li>-->
								<li>
									<a href="includes/logout.inc.php" class="nav-link">Logout</a>
								</li>
							</ul>
						</li>
					</ul>
				</div>

			</nav>
		</div>
	</header>
	<!--Header-->
<br><br><br><br><br>
	<div class="main">
		<div id="navigation" style="display:none;" class="w3_agile">
			<ul>
				<li class="selected">
					<a href="#"><i class="fa fa-list-ul" aria-hidden="true"></i><span>Info</span></a>
				</li>
				<li>
					<a href="#"><i class="fa fa-folder" aria-hidden="true"></i><span>Hostel</span></a>
				</li>
				<li>
					<a href="#"><i class="fa fa-envelope" aria-hidden="true"></i><span>Contact</span></a>
				</li>
			</ul>
		</div>
		<div id="wrapper" class="w3ls_wrapper w3layouts_wrapper">
			<div id="steps" style="margin:0 auto;" class="agileits w3_steps">
				<form id="formElem" name="formElem" action="#" method="post" class="w3_form w3l_form_fancy">
					<fieldset class="step agileinfo w3ls_fancy_step">
						<legend>Personal Info</legend>
						<div class="abt-agile">
							<div class="abt-agile-left" style="padding: 10px;">
                                <?php if(!empty($student_photo) && file_exists($student_photo)): ?>
                                    <img src="<?php echo htmlspecialchars($student_photo); ?>" alt="Profile Photo" style="width: 100%; max-width: 150px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);" />
                                <?php else: ?>
                                    <div style="width: 100%; max-width: 150px; height: 150px; background: rgba(0,0,0,0.5); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff;">No Photo</div>
                                <?php endif; ?>
							</div>
							<div class="abt-agile-right">

								<h3><?php echo $_SESSION['fname']." ".$_SESSION['lname']; ?></h3>
								<h5>Student</h5>
								<ul class="address">
									<li>
										<ul class="address-text">
											<li><b>Hall Ticket Number </b></li>
											<li>: <?php echo $_SESSION['hall_ticket'] ?? ''; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>Father Name </b></li>
											<li>: <?php echo $_SESSION['father_name'] ?? 'Not set'; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>Mother Name </b></li>
											<li>: <?php echo $_SESSION['mother_name'] ?? 'Not set'; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>DOB </b></li>
											<li>: <?php echo $_SESSION['dob'] ?? 'Not set'; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>PHONE </b></li>
											<li>: <?php echo $_SESSION['mob_no'] ?? ''; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>DEPT </b></li>
											<li>: <?php echo $_SESSION['department'] ?? ''; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>SEMESTER </b></li>
											<li>: <?php echo $_SESSION['semester'] ?? ''; ?></li>
										</ul>
									</li>
								</ul>
								
								<hr style="border-top: 1px solid rgba(255,255,255,0.2); margin: 30px 0 20px 0;">
								<h5 style="color: #00bcd4; font-weight: bold; margin-bottom: 15px;">Update Academic Semester</h5>
								<form action="profile.php" method="POST" style="margin-top: 10px;">
									<div style="display: flex; gap: 10px; align-items: center;">
										<select name="new_sem" class="form-control" style="background:rgba(255,255,255,0.8); color:#000; flex: 2;" required>
											<option value="">Select New Semester...</option>
											<option value="1">Semester 1</option>
											<option value="2">Semester 2</option>
											<option value="3">Semester 3</option>
											<option value="4">Semester 4</option>
											<option value="5">Semester 5</option>
											<option value="6">Semester 6</option>
											<option value="7">Semester 7</option>
											<option value="8">Semester 8</option>
										</select>
										<button type="submit" name="sem-update" class="btn btn-info" style="flex: 1; padding: 7px;">Update</button>
									</div>
								</form>

							</div>
								<div class="clear"></div>
						</div>
				</fieldset>
				<fieldset class="step agileinfo w3ls_fancy_step">
					<legend>Hostel Info</legend>
					<div class="abt-agile">
						<div class="abt-agile-left-hostel">
						</div>
						<div class="abt-agile-right">

							<h3><?php echo $_SESSION['fname']." ".$_SESSION['lname']; ?></h3>
							<h5>Student</h5>
							<ul class="address">
								<li>
									<ul class="address-text">
										<li><b>HOSTEL </b></li>
										<?php
											$hostelId = $_SESSION['hostel_id'];
											if($hostelId == NULL){
												$hostelName = 'None';
											}
											else {
												$sql = "SELECT * FROM Hostel WHERE Hostel_id = '$hostelId'";
												$result = mysqli_query($conn, $sql);
												if($row = mysqli_fetch_assoc($result)){
													$hostelName = $row['Hostel_name'];
												}
												else {
													echo "<script type='text/javascript'>alert('Foreign Key Error-hostenName!!')</script>";
												}
											}
										 ?>


										<li>: <?php echo $hostelName; ?></li>
									</ul>
								</li>
								<li>
									<ul class="address-text">
										<li><b>ROOM NO </b></li>
										<?php
											$roomId = $_SESSION['room_id'];
											if($hostelId == NULL || $roomId == NULL){
												$roomNo = 'None';
											}
											else {
												$sql = "SELECT * FROM Room WHERE Room_id = '$roomId'";
												$result = mysqli_query($conn, $sql);
												if($row = mysqli_fetch_assoc($result)){
													$roomNo = $row['Room_No'];
												}
												else {
													echo "<script type='text/javascript'>alert('Foreign Key Error-roomNo!!')</script>";
												}
											}
										 ?>
										<li>: <?php echo $roomNo; ?></li>
									</ul>
								</li>
							</ul>
						</div>
							<div class="clear"></div>
					</div>
			</fieldset>
					<!--	<fieldset class="step wthree">
						<legend>Work</legend>
						<div class="work-w3agile">
							<div class="work-w3agile-top">
								<div class="agileits_w3layouts_work_grid1 w3layouts_work_grid1 hover14 column">
									<div class="w3_agile_work_effect">
										<ul>
											<li>
												<a href="web_profile/images/c1.jpg" class="sb" title="Quis Nostrud Exercitation Ullamco Laboris Quis Autem Vel Eum Iure Reprehenderit">
													<figure>
														<img src="web_profile/images/c1.jpg" alt=" " class="img-responsive" />
													</figure>
												</a>
											</li>
											<li>
												<a href="web_profile/images/c2.jpg" class="sb" title="Quis Nostrud Exercitation Ullamco Laboris Quis Autem Vel Eum Iure Reprehenderit">
													<figure>
														<img src="web_profile/images/c2.jpg" alt=" " class="img-responsive" />
													</figure>
												</a>
											</li>
											<li>
												<a href="web_profile/images/c3.jpg" class="sb" title="Quis Nostrud Exercitation Ullamco Laboris Quis Autem Vel Eum Iure Reprehenderit">
													<figure>
														<img src="web_profile/images/c3.jpg" alt=" " class="img-responsive" />
													</figure>
												</a>
											</li>
											<li>
												<a href="web_profile/images/c4.jpg" class="sb" title="Quis Nostrud Exercitation Ullamco Laboris Quis Autem Vel Eum Iure Reprehenderit">
													<figure>
														<img src="web_profile/images/c4.jpg" alt=" " class="img-responsive" />
													</figure>
												</a>
											</li>
											<li>
												<a href="web_profile/images/c5.jpg" class="sb" title="Quis Nostrud Exercitation Ullamco Laboris Quis Autem Vel Eum Iure Reprehenderit">
													<figure>
														<img src="web_profile/images/c5.jpg" alt=" " class="img-responsive" />
													</figure>
												</a>
											</li>
											<li>
												<a href="web_profile/images/c6.jpg" class="sb" title="Quis Nostrud Exercitation Ullamco Laboris Quis Autem Vel Eum Iure Reprehenderit">
													<figure>
														<img src="web_profile/images/c6.jpg" alt=" " class="img-responsive" />
													</figure>
												</a>
											</li>
												<div class="clear"></div>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</fieldset>-->
					<fieldset class="step agileinfo w3ls_fancy_step">
						<legend>Hostel Manager Info</legend>
						<div class="abt-agile">
							<div class="abt-agile-left">
							</div>
							<div class="abt-agile-right">
								<?php
									$Hid = $_SESSION['hostel_id'];
									$sql1 = "SELECT * FROM Hostel_Manager WHERE Hostel_id = '$Hid'";
									$result1 = mysqli_query($conn, $sql1);
									if($row1 = mysqli_fetch_assoc($result1)){
										$hmfname = $row1['Fname'];
										$hmlname = $row1['Lname'];
										$hmMob  = $row1['Mob_no'];
										$hmemail = $row1['Email'];
									}
									else {
										$hmfname = 'none';
										$hmlname = 'none';
										$hmMob  = 'none';
										$hmemail = 'none';
									}
								 ?>
								<h3><?php echo $hmfname." ".$hmlname; ?></h3>
								<h5>Admin</h5>
								<ul class="address">
									<li>
										<ul class="address-text">
											<li><b>PHONE </b></li>
											<li>: <?php echo $hmMob; ?></li>
										</ul>
									</li>
									<li>
										<ul class="address-text">
											<li><b>Email </b></li>
											<li>: <?php echo $hmemail; ?></li>
										</ul>
									</li>
								</ul>
							</div>
								<div class="clear"></div>
						</div>
				</fieldset>
				<fieldset class="step agileinfo w3ls_fancy_step">
					<legend>Security</legend>
					<div class="abt-agile">
						<div class="abt-agile-left">
						</div>
						<div class="abt-agile-right">
							<h3>Update Password</h3>
							<br>
							<?php
							if (isset($_POST['pwd-update'])) {
								$new_pwd = $_POST['new_pwd'];
								$conf_pwd = $_POST['conf_pwd'];
								if (empty($new_pwd)) {
									echo "<p style='color:red;'>Password cannot be empty.</p>";
								} elseif ($new_pwd !== $conf_pwd) {
									echo "<p style='color:red;'>Passwords do not match.</p>";
								} else {
									$hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
									$s_id = $_SESSION['hall_ticket'];
									$upd_sql = "UPDATE Student SET Pwd = ? WHERE Student_id = ?";
									$upd_stmt = $conn->prepare($upd_sql);
									$upd_stmt->bind_param("ss", $hashed, $s_id);
									if ($upd_stmt->execute()) {
										echo "<p style='color:green;'>Password updated successfully!</p>";
									} else {
										echo "<p style='color:red;'>Error updating password.</p>";
									}
									$upd_stmt->close();
								}
							}
							?>
							<form action="profile.php" method="POST">
								<div class="form-group">
									<label style="color:#fff;">New Password</label>
									<input type="password" name="new_pwd" class="form-control" style="background:rgba(255,255,255,0.1); color:#fff;" required>
								</div>
								<div class="form-group">
									<label style="color:#fff;">Confirm Password</label>
									<input type="password" name="conf_pwd" class="form-control" style="background:rgba(255,255,255,0.1); color:#fff;" required>
								</div>
								<button type="submit" name="pwd-update" class="btn btn-info mt-3">Update Password</button>
							</form>
						</div>
						<div class="clear"></div>
					</div>
				</fieldset>
				</form>
			</div>
		</div>

	</div>
	<script type="text/javascript" src="web_profile/js/smoothbox.jquery2.js"></script>
</body>
</html>
