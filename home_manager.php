<?php
 require 'includes/config.inc.php';
 require_once 'includes/sms_gateway.php';

 if(!isset($_SESSION['hostel_man_id'])){
     header("Location: login-hostel_manager.php");
     exit();
 }
 $hostel_man_id = $_SESSION['hostel_man_id'];
 $hostel_id = $_SESSION['hostel_id'] ?? '';

 // Configuration update
 if (isset($_POST['update_hostel_rates'])) {
     $new_caution = $_POST['caution_deposit'];
     $new_maint = $_POST['maintenance_fee'];
     $upd = $conn->prepare("UPDATE Hostel SET Caution_Deposit_Amount = ?, Maintenance_Fee_Amount = ? WHERE Hostel_id = ?");
     $upd->bind_param("ddi", $new_caution, $new_maint, $hostel_id);
     $upd->execute();
     $upd->close();
 }

 // Fetch current hostel configuration
 $h_query = $conn->prepare("SELECT Caution_Deposit_Amount, Maintenance_Fee_Amount FROM Hostel WHERE Hostel_id = ?");
 $h_query->bind_param("i", $hostel_id);
 $h_query->execute();
 $h_res = $h_query->get_result();
 $current_caution = '5000.00';
 $current_maintenance = '2000.00';
 if ($h_res && $h_row = $h_res->fetch_assoc()) {
     $current_caution = $h_row['Caution_Deposit_Amount'];
     $current_maintenance = $h_row['Maintenance_Fee_Amount'];
 }
 $h_query->close();

 // AUTO-MAINTENANCE TRIGGER
 $curr_year = date('Y');
 $bill_date = date('Y-m-d');
 $stu_query = "SELECT Student_id, Mob_no FROM Student WHERE Hostel_id = '$hostel_id'";
 $stu_result = mysqli_query($conn, $stu_query);
 if ($stu_result) {
     while($row = mysqli_fetch_assoc($stu_result)) {
         $sid = $row['Student_id'];
         $check = $conn->prepare("SELECT id FROM Payment WHERE Student_id = ? AND category = 'Maintenance' AND DATE_FORMAT(payment_date, '%Y') = ?");
         $check->bind_param("ss", $sid, $curr_year);
         $check->execute();
         if ($check->get_result()->num_rows == 0) {
             // Add maintenance charge
             $ins_pay = $conn->prepare("INSERT INTO Payment (Student_id, amount, payment_date, status, transaction_type, category) VALUES (?, ?, ?, 'Pending', 'Charge', 'Maintenance')");
             $ins_pay->bind_param("sss", $sid, $current_maintenance, $bill_date);
             $ins_pay->execute();
             
             $msg_text = "H-Track: Yearly Maintenance fee of N{$current_maintenance} applied for {$curr_year}.";
             $md = date('Y-m-d'); $mt = date('h:i A');
             $ins_msg = $conn->prepare("INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, sender_type, receiver_type) VALUES (?, ?, ?, 'Maintenance Fee Charge', ?, ?, ?, 'manager', 'student')");
             $ins_msg->bind_param("ssisss", $hostel_man_id, $sid, $hostel_id, $msg_text, $md, $mt);
             $ins_msg->execute();

             if (!empty($row['Mob_no'])) send_student_sms($row['Mob_no'], $msg_text);
         }
         $check->close();
     }
 }

 // AUTO-ATTENDANCE TRIGGER
 $yesterday = date('Y-m-d', strtotime('-1 day'));
 $stu_att_query = "SELECT Student_id, Join_date FROM Student WHERE Hostel_id = '$hostel_id' AND Join_date IS NOT NULL";
 $stu_att_res = mysqli_query($conn, $stu_att_query);
 if ($stu_att_res) {
     while($arow = mysqli_fetch_assoc($stu_att_res)) {
         $sid = $arow['Student_id'];
         $jdate = $arow['Join_date'];
         if(strtotime($jdate) > strtotime($yesterday)) continue; 
         
         $log_q = $conn->prepare("SELECT date FROM Attendance WHERE Student_id = ? AND date BETWEEN ? AND ?");
         $log_q->bind_param("sss", $sid, $jdate, $yesterday);
         $log_q->execute();
         $log_res = $log_q->get_result();
         $logged_dates = [];
         while($lr = $log_res->fetch_assoc()){
             $logged_dates[] = $lr['date'];
         }
         $log_q->close();
         
         $current_t = strtotime($jdate);
         $end_t = strtotime($yesterday);
         $ins_att = $conn->prepare("INSERT INTO Attendance (Student_id, date, status) VALUES (?, ?, 'Present')");
         while($current_t <= $end_t) {
             $cdate = date('Y-m-d', $current_t);
             if(!in_array($cdate, $logged_dates)){
                 $ins_att->bind_param("ss", $sid, $cdate);
                 $ins_att->execute();
             }
             $current_t = strtotime('+1 day', $current_t);
         }
         $ins_att->close();
     }
 }

// PENDING APPLICATIONS NOTIFICATION
$managed_ids = $_SESSION['managed_hostels'] ?? [$hostel_id];
if (empty($managed_ids)) $managed_ids = [$hostel_id];
$placeholders = implode(',', array_fill(0, count($managed_ids), '?'));
$q_apps = $conn->prepare("SELECT COUNT(*) as cnt FROM Application WHERE Hostel_id IN ($placeholders) AND Application_status = '1'");
$q_apps->bind_param(str_repeat("s", count($managed_ids)), ...$managed_ids);
$q_apps->execute();
$res_apps = $q_apps->get_result();
$pending_app_count = $res_apps->fetch_assoc()['cnt'] ?? 0;
$q_apps->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title> Intrend Interior Category Flat Bootstrap Responsive Website Template | Index : W3layouts</title>

	<!-- Meta tag Keywords -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<meta name="keywords" content="Intrend Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
	Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
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
<style>
    body { margin: 0; padding: 0; overflow: hidden; } /* Prevent double scrollbars */
    .manager-flex-layout { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
    .manager-sidebar {
        width: 250px; flex-shrink: 0; background: rgba(0, 0, 0, 0.88);
        border-right: 1px solid rgba(255,255,255,0.1); padding: 30px 20px; overflow-y: auto; z-index: 10000;
        box-shadow: 2px 0 15px rgba(0,0,0,0.5);
    }
    .manager-main-view { flex-grow: 1; overflow-y: auto; overflow-x: hidden; position: relative; }
    
    .admin-sidebar-title { color: #00bcd4; font-size: 0.85em; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px; border-bottom: 2px solid rgba(0,188,212,0.3); padding-bottom: 8px; }
    .admin-sidebar-nav { list-style: none; padding: 0; margin: 0; }
    .admin-sidebar-item { margin-bottom: 15px; }
    .admin-sidebar-link { 
        display: flex; align-items: center; padding: 12px 15px; color: rgba(255,255,255,0.8);
        text-decoration: none; border-radius: 8px; transition: 0.3s; background: rgba(255,255,255,0.05);
        font-weight: 600; font-size: 0.9em;
    }
    .admin-sidebar-link i { width: 25px; margin-right: 10px; font-size: 1.1em; color: #00bcd4; opacity: 0.9; }
    .admin-sidebar-link:hover { background: rgba(0,188,212,0.2); color: #fff; text-decoration: none; }
</style>

<div class="manager-flex-layout">
    <aside class="manager-sidebar">
        <h5 class="admin-sidebar-title"><i class="fas fa-cogs mr-2"></i> Operations Hub</h5>
        <ul class="admin-sidebar-nav">
            <li class="admin-sidebar-item">
                <a href="allocate_room.php" class="admin-sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'allocate_room.php') echo 'active'; ?>">
                    <i class="fas fa-bed"></i> Room Allocation
                    <?php if(isset($pending_app_count) && $pending_app_count > 0): ?>
                        <span class="badge badge-danger ml-auto animated pulse infinite" style="border-radius: 50%; padding: 5px 8px; font-size: 0.75em;"><?php echo $pending_app_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/pending_signups.php" class="admin-sidebar-link">
                    <i class="fas fa-user-clock"></i> Pending Signups
                    <?php if(isset($pending_signup_count) && $pending_signup_count > 0): ?>
                        <span class="badge badge-danger ml-auto animated pulse infinite"><?php echo $pending_signup_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/upload_student_entries.php" class="admin-sidebar-link">
                    <i class="fas fa-user-plus"></i> Student Entry
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/upload_mandates.php" class="admin-sidebar-link">
                    <i class="fas fa-file-contract"></i> Mandates
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/upload_firms.php" class="admin-sidebar-link">
                    <i class="fas fa-building"></i> Firms
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/generate_bills.php" class="admin-sidebar-link">
                    <i class="fas fa-file-invoice-dollar"></i> Bill Generation
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/view_final_bills.php" class="admin-sidebar-link">
                    <i class="fas fa-receipt"></i> Final Bills
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/update_challans.php" class="admin-sidebar-link">
                    <i class="fas fa-edit"></i> Fee Challans Updates
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="admin/firm_payments.php" class="admin-sidebar-link">
                    <i class="fas fa-money-check-alt"></i> Firms Payments
                </a>
            </li>
        </ul>
    </aside>
    <main class="manager-main-view">
        <!-- SUBTLE NOTIFICATION BAR -->
        <?php if ($pending_app_count > 0): ?>
        <div class="py-2 px-4 bg-info text-white d-flex align-items-center justify-content-between" style="font-size: 0.9em; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <span><i class="fas fa-info-circle mr-2"></i> You have <?php echo $pending_app_count; ?> pending student application(s) awaiting allocation.</span>
            <a href="allocate_room.php" class="btn btn-sm btn-light font-weight-bold" style="font-size: 0.8em; color: #17a2b8;">MANAGE NOW</a>
        </div>
        <?php endif; ?>

<!-- banner -->
	<div class="banner" id="home">
		<div class="cd-radial-slider-wrapper">

<!--Header-->
<header>
	<div class="container agile-banner_nav">
		<nav class="navbar navbar-expand-lg navbar-light bg-light">

			<h1><a class="navbar-brand" href="home_manager.php">ESUT<span class="display"></span></a></h1>
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
				<ul class="navbar-nav ml-auto">
					<li class="nav-item active">
						<a class="nav-link" href="home_manager.php"> Home<span class="sr-only">(current)</span></a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="allocate_room.php">Allocate Rooms <?php if(isset($pending_app_count) && $pending_app_count > 0) { echo '<span class="badge badge-danger">'.$pending_app_count.'</span>'; } ?></a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="message_hostel_manager.php">Messages Received <?php if(isset($unread_msg_manager_count) && $unread_msg_manager_count > 0) { echo '<span class="badge badge-info">'.$unread_msg_manager_count.'</span>'; } ?></a>
					</li>
					<li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Rooms
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li>
								<a href="allocated_rooms.php">Allocated Rooms</a>
							</li>
							<li>
								<a href="empty_rooms.php">Empty Rooms</a>
							</li>
							<li>
								<a href="vacate_rooms.php">Vacate Rooms</a>
							</li>
						</ul>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="contact_manager.php">Contact</a>
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
							<li>
								<a href="admin/manager_profile.php">My Profile</a>
							</li>
							<li>
								<a href="includes/logout.inc.php">Logout</a>
							</li>
						</ul>
					</li>
				</ul>
			</div>

		</nav>
	</div>
</header>
<!--Header-->

			<ul class="cd-radial-slider" data-radius1="60" data-radius2="1364" data-centerx1="110" data-centerx2="1290">
				<li class="visible">
					<div class="svg-wrapper">
						<svg viewBox="0 0 1400 800">
							<title>Animated SVG</title>
							<defs>
								<clipPath id="cd-image-1">
									<circle id="cd-circle-1" cx="110" cy="400" r="1364"/>
								</clipPath>
							</defs>
							<image height='800px' width="1400px" clip-path="url(#cd-image-1)" xlink:href="web_home/images/1.png"></image>
						</svg>
					</div> <!-- .svg-wrapper -->
					<div class="cd-radial-slider-content">
						<div class="wrapper">
							<div class="text-center">
								<h2>Hostel Management</h2>
								<h2>System<h2>
								
							</div>
						</div>
					</div> <!-- .cd-radial-slider-content -->
				</li>
				<li class="next-slide">
					<div class="svg-wrapper">
						<svg viewBox="0 0 1400 800">
							<title>Animated SVG</title>
							<defs>
								<clipPath id="cd-image-2">
									<circle id="cd-circle-2" cx="1290" cy="400" r="60"/>
								</clipPath>
							</defs>
							<image height='800px' width="1400px" clip-path="url(#cd-image-2)" xlink:href="web_home/images/2.png"></image>
						</svg>
					</div> <!-- .svg-wrapper -->
					<div class="cd-radial-slider-content text-center">
						<div class="wrapper">
							<div class="text-center">
								<h3>Rooms </h3>
							
								

							</div>
						</div>
					</div> <!-- .cd-radial-slider-content -->
				</li>
				<li>
					<div class="svg-wrapper">
						<svg viewBox="0 0 1400 800">
							<title>Animated SVG</title>
							<defs>
								<clipPath id="cd-image-3">
									<circle id="cd-circle-3" cx="110" cy="400" r="60"/>
								</clipPath>
							</defs>
							<image height='800px' width="1400px" clip-path="url(#cd-image-3)" xlink:href="web_home/images/3.png"></image>
						</svg>
					</div> <!-- .svg-wrapper -->
					<div class="cd-radial-slider-content text-center">
						<div class="wrapper">
							<div class="text-center">
								<h3>Hostels </h3>
								
								
							</div>
						</div>
					</div> <!-- .cd-radial-slider-content -->
				</li>
				
			</ul> <!-- .cd-radial-slider -->
			<ul class="cd-radial-slider-navigation">
				<li><a href="#0" class="next"><i class="fas fa-chevron-right"></i></a></li>
				<li><a href="#0" class="prev"><i class="fas fa-chevron-left"></i></a></li>
			</ul> <!-- .cd-radial-slider-navigation -->
		</div> <!-- .cd-radial-slider-wrapper -->
	</div>
<!-- //banner -->

<!-- about -->
<br><br>
<div class="container">
    <div class="row">
        <!-- Hostel Fee Configuration (Original) -->
        <div class="col-md-6 offset-md-3">
            <div class="card p-4 shadow-sm h-100 border-0" style="background:#fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;">
                <h3 class="mb-4" style="color: #444; font-weight: 700; border-bottom: 2px solid #e9ecef; padding-bottom: 15px;"><i class="fas fa-sliders-h mr-2 text-warning"></i> Hostel Configuration</h3>
                <form action="home_manager.php" method="POST">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark">Caution Deposit Amount (N)</label>
                        <input type="number" name="caution_deposit" class="form-control form-control-lg bg-light" step="0.01" value="<?php echo htmlspecialchars($current_caution); ?>" required>
                        <small class="form-text text-muted mt-1"><i class="fas fa-info-circle mr-1"></i> Charged automatically on room assignment.</small>
                    </div>
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark">Yearly Maintenance Fee (N)</label>
                        <input type="number" name="maintenance_fee" class="form-control form-control-lg bg-light" step="0.01" value="<?php echo htmlspecialchars($current_maintenance); ?>" required>
                        <small class="form-text text-muted mt-1"><i class="fas fa-info-circle mr-1"></i> Charged automatically once per calendar year.</small>
                    </div>

                    <button type="submit" name="update_hostel_rates" class="btn btn-warning btn-block">Update Fee Rates</button>
                </form>
            </div>
        </div>
    </div>
</div>
<br><br>
<!-- //about -->

<!-- why choose us -->

<!-- //why choose us -->

<!-- team -->

<!-- //team -->

<!-- Clients -->

<!--// Clients -->

<!-- latest news -->

<!-- //latest news -->

<!-- footer -->
<footer class="py-5">
	<div class="container pb-sm-5 pb-3">
		<h2 class="heading text-capitalize mb-sm-5 mb-4"> Hostel Manager Dashboard </h2>

        <?php if (!empty($_SESSION['managed_hostels']) && count($_SESSION['managed_hostels']) > 1): ?>
        <div class="row mb-5">
            <div class="col-md-6 offset-md-3">
                <div class="card shadow-sm border-0" style="background: rgba(255,255,255,0.95); border-radius: 10px;">
                    <div class="card-body text-center p-4">
                        <h5 class="font-weight-bold mb-3 text-dark"><i class="fas fa-exchange-alt mr-2 text-primary"></i> Switch Active Hostel</h5>
                        <form action="admin/switch_hostel.php" method="POST" class="d-flex align-items-center justify-content-center">
                            <select name="new_hostel_id" class="form-control mr-3" style="max-width: 250px; font-weight: bold; border-radius: 5px;" onchange="this.form.submit()">
                                <?php
                                $hostel_desc = [
                                    '1' => 'Hostel A (Old Boys)', '2' => 'Hostel B (New Boys)', 
                                    '3' => 'Hostel C (Girls)', '4' => 'Hostel D (Degree Boys)',
                                    '5' => 'Hostel E (Degree Girls)', '6' => 'Hostel F (PG)'
                                ];
                                foreach ($_SESSION['managed_hostels'] as $hid) {
                                    $h_name = isset($hostel_desc[$hid]) ? $hostel_desc[$hid] : "Hostel ID: " . $hid;
                                    $sel = ($hid == $_SESSION['hostel_id']) ? "selected" : "";
                                    echo "<option value='{$hid}' {$sel}>{$h_name}</option>";
                                }
                                ?>
                            </select>
                            <noscript><button type="submit" class="btn btn-primary">Switch</button></noscript>
                        </form>
                        <small class="text-muted mt-2 d-block">This determines which hostel's students and rooms you manage across the entire dashboard.</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand"  href="https://dresut.vercel.app/" target="_blank">ESUT<span class="display"></span></a>
		</div>
		<div class="footer-grid">
			
			<div class="list-footer">
				<ul class="footer-nav text-center">
					<li>
						<a href="home_manager.php">Home</a>
					</li>
					<li>
						<a href="allocate_room.php">Allocate</a>
					</li>
					<li>
						<a href="contact_manager.php">Contact</a>
					</li>
					<li>
						<a href="manager_profile.php">Profile</a>
					</li>
				</ul>
			</div>
			
		</div>
	</div>
</footer>
<!-- footer -->

<!-- js-scripts -->

	<!-- js -->
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="web_home/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap -->
	<!-- //js -->

	<!-- banner js -->
	<script src="web_home/js/snap.svg-min.js"></script>
	<script src="web_home/js/main.js"></script> <!-- Resource jQuery -->
	<!-- //banner js -->

	<!-- flexSlider --><!-- for testimonials -->
	<script defer src="web_home/js/jquery.flexslider.js"></script>
	<script type="text/javascript">
		$(window).load(function(){
		  $('.flexslider').flexslider({
			animation: "slide",
			start: function(slider){
			  $('body').removeClass('loading');
			}
		  });
		});
	</script>
	<!-- //flexSlider --><!-- for testimonials -->

	<!-- start-smoth-scrolling -->
	<script src="web_home/js/SmoothScroll.min.js"></script>
	<script type="text/javascript" src="web_home/js/move-top.js"></script>
	<script type="text/javascript" src="web_home/js/easing.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(".scroll").click(function(event){
				event.preventDefault();
				$('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
			});
		});
	</script>
	<!-- here stars scrolling icon -->
	<script type="text/javascript">
		$(document).ready(function() {
			/*
				var defaults = {
				containerID: 'toTop', // fading element id
				containerHoverID: 'toTopHover', // fading element hover id
				scrollSpeed: 1200,
				easingType: 'linear'
				};
			*/

			$().UItoTop({ easingType: 'easeOutQuart' });

			});
	</script>
	<!-- //here ends scrolling icon -->
	<!-- start-smoth-scrolling -->


<!-- //js-scripts -->
    </main>
</div>
</body>
</html>
