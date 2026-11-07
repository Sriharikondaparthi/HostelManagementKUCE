<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../includes/config.inc.php';

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $mob = trim($_POST['mob_no']);
    $email = trim($_POST['email']);
    $mid = $_SESSION['hostel_man_id'];

    $updQ = $conn->prepare("UPDATE Hostel_Manager SET Fname=?, Lname=?, Mob_no=?, Email=? WHERE Hostel_man_id=?");
    $updQ->bind_param("sssss", $fname, $lname, $mob, $email, $mid);
    if ($updQ->execute()) {
        $_SESSION['fname'] = $fname;
        $_SESSION['lname'] = $lname;
        $_SESSION['mob_no'] = $mob;
        $_SESSION['email'] = $email;
    }
}

// Handle Hostel Capacity Update
if (isset($_POST['update_hostel_capacity'])) {
    $hid = (int)$_POST['hostel_id'];
    $new_total = (int)$_POST['no_of_rooms'];
    
    // Calculate new current_no_of_rooms (Available)
    // Available = New Total - No_of_students
    $q = $conn->prepare("SELECT No_of_students FROM Hostel WHERE Hostel_id = ?");
    $q->bind_param("i", $hid);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    $students = $res['No_of_students'] ?? 0;
    
    $new_available = max(0, $new_total - $students);
    
    $updH = $conn->prepare("UPDATE Hostel SET No_of_rooms = ?, current_no_of_rooms = ? WHERE Hostel_id = ?");
    $updH->bind_param("iii", $new_total, $new_available, $hid);
    $updH->execute();
    echo "<script>alert('Hostel capacity updated successfully!');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Manager Profile | H-Track</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="../web_profile/js/jquery-2.1.3.min.js" type="text/javascript"></script>
<script type="text/javascript" src="../web_profile/js/sliding.form.js"></script>
<link href="../web_profile/css/style.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" href="../web_profile/css/font-awesome.min.css" />
<link rel="stylesheet" href="../web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" />
<link rel="stylesheet" href="../web_home/css_home/fontawesome-all.css">
<style>
    .abt-agile-right h3 { font-family: 'Poiret One', cursive; font-weight: bold; color: #00bcd4; margin-bottom: 20px; }
    .address-text li { font-size: 15px; margin-bottom: 10px; color: #555; }
    .address-text b { color: #111; min-width: 100px; display: inline-block; }
    .form-group label { font-weight: bold; color: #333; margin-bottom: 5px; }
    .form-control { border-radius: 4px; border: 1px solid #ddd; padding: 10px; }
</style>
</head>
<body>
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">
				<h1><a class="navbar-brand" href="../home_manager.php">ESUT<span class="display"></span></a></h1>
				<div class="collapse navbar-collapse justify-content-center">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item"><a class="nav-link" href="../home_manager.php">Home</a></li>
						<li class="nav-item"><a class="nav-link" href="../allocate_room.php">Allocate Rooms</a></li>
						<li class="nav-item"><a class="nav-link" href="../includes/logout.inc.php">Logout</a></li>
					</ul>
				</div>
			</nav>
		</div>
	</header>

<br><br><br><br><br>
<div class="main">
    <div id="navigation" style="display:none;" class="w3_agile">
        <ul>
            <li class="selected"><a href="#"><i class="fa fa-user"></i><span>Profile</span></a></li>
            <li><a href="#"><i class="fa fa-edit"></i><span>Update</span></a></li>
            <li><a href="#"><i class="fa fa-shield"></i><span>Admin</span></a></li>
        </ul>
    </div>
    <div id="wrapper" class="w3ls_wrapper w3layouts_wrapper">
        <div id="steps" style="margin:0 auto;" class="agileits w3_steps">
            <form id="formElem" name="formElem" action="manager_profile.php" method="post" class="w3_form w3l_form_fancy">
                
                <!-- STEP 1: VIEW INFO -->
                <fieldset class="step agileinfo w3ls_fancy_step">
                    <legend>Personal Info</legend>
                    <div class="abt-agile">
                        <div class="abt-agile-right" style="width:100%">
                            <h3>Account Overview</h3>
                            <ul class="address">
                                <li><ul class="address-text"><li><b>Full Name </b></li><li>: <?php echo $_SESSION['fname']." ".$_SESSION['lname']; ?></li></ul></li>
                                <li><ul class="address-text"><li><b>Username </b></li><li>: <?php echo $_SESSION['username']; ?></li></ul></li>
                                <li><ul class="address-text"><li><b>Phone </b></li><li>: <?php echo $_SESSION['mob_no']; ?></li></ul></li>
                                <li><ul class="address-text"><li><b>Email </b></li><li>: <?php echo $_SESSION['email']; ?></li></ul></li>
                                <li><ul class="address-text"><li><b>Hostels </b></li><li>: <?php echo is_array($_SESSION['managed_hostels']) ? implode(', ', $_SESSION['managed_hostels']) : ($_SESSION['managed_hostels'] ?? 'None'); ?></li></ul></li>
                            </ul>
                        </div>
                        <div class="clear"></div>
                    </div>
                </fieldset>

                <!-- STEP 2: UPDATE INFO -->
                <fieldset class="step agileinfo w3ls_fancy_step">
                    <legend>Update Profile</legend>
                    <div class="abt-agile">
                        <div class="abt-agile-right" style="width:100%">
                            <h3>Modify Details</h3>
                            <div class="form-group mb-3">
                                <label>First Name</label>
                                <input type="text" name="fname" class="form-control" value="<?php echo $_SESSION['fname']; ?>" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Last Name</label>
                                <input type="text" name="lname" class="form-control" value="<?php echo $_SESSION['lname']; ?>" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Mobile No</label>
                                <input type="text" name="mob_no" class="form-control" value="<?php echo $_SESSION['mob_no']; ?>" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Email ID</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $_SESSION['email']; ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-info btn-block mt-4 font-weight-bold">SAVE PROFILE CHANGES</button>
                            
                            <hr class="my-5">
                            
                            <h3 class="mt-4">Hostel Capacity Management</h3>
                            <p class="text-muted mb-4 small">Update the total number of rooms for your assigned hostels here.</p>
                            
                            <?php
                            $managed_ids = $_SESSION['managed_hostels'] ?? [$_SESSION['hostel_id']];
                            if (!empty($managed_ids)) {
                                $placeholders = implode(',', array_fill(0, count($managed_ids), '?'));
                                $h_query = $conn->prepare("SELECT * FROM Hostel WHERE Hostel_id IN ($placeholders)");
                                $h_query->bind_param(str_repeat("i", count($managed_ids)), ...$managed_ids);
                                $h_query->execute();
                                $h_res = $h_query->get_result();
                                while ($hrow = $h_res->fetch_assoc()) {
                                    ?>
                                    <div class="card mb-3 border shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="font-weight-bold m-0 text-primary">Hostel <?php echo $hrow['Hostel_name']; ?></h6>
                                                <span class="badge badge-secondary"><?php echo $hrow['No_of_students']; ?> Students Filled</span>
                                            </div>
                                            <form method="POST" action="manager_profile.php" class="form-inline">
                                                <input type="hidden" name="hostel_id" value="<?php echo $hrow['Hostel_id']; ?>">
                                                <div class="input-group w-100">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Total Rooms</span>
                                                    </div>
                                                    <input type="number" name="no_of_rooms" class="form-control" value="<?php echo $hrow['No_of_rooms']; ?>" min="<?php echo $hrow['No_of_students']; ?>" required>
                                                    <div class="input-group-append">
                                                        <button type="submit" name="update_hostel_capacity" class="btn btn-primary font-weight-bold">UPDATE</button>
                                                    </div>
                                                </div>
                                                <small class="text-info mt-1">Available: <?php echo $hrow['current_no_of_rooms']; ?> rooms</small>
                                            </form>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <div class="clear"></div>
                    </div>
                </fieldset>

                <!-- STEP 3: ADMIN INFO -->
                <fieldset class="step agileinfo w3ls_fancy_step">
                    <legend>Admin Support</legend>
                    <div class="abt-agile">
                        <div class="abt-agile-right" style="width:100%">
                            <?php
                                $queryA = "SELECT * FROM Hostel_Manager WHERE Isadmin = '1' LIMIT 1";
                                $resultA = mysqli_query($conn,$queryA);
                                $rowA = mysqli_fetch_assoc($resultA);
                            ?>
                            <h3>Primary Administrator</h3>
                            <ul class="address">
                                <li><ul class="address-text"><li><b>Name </b></li><li>: <?php echo ($rowA['Fname'] ?? 'System')." ".($rowA['Lname'] ?? 'Admin'); ?></li></ul></li>
                                <li><ul class="address-text"><li><b>Username </b></li><li>: <?php echo $rowA['Username'] ?? 'admin'; ?></li></ul></li>
                                <li><ul class="address-text"><li><b>Contact </b></li><li>: <?php echo $rowA['Mob_no'] ?? 'N/A'; ?></li></ul></li>
                                <li><ul class="address-text"><li><b>Email </b></li><li>: <?php echo $rowA['Email'] ?? 'admin@htrack.com'; ?></li></ul></li>
                            </ul>
                        </div>
                        <div class="clear"></div>
                    </div>
                </fieldset>

            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="../web_profile/js/smoothbox.jquery2.js"></script>
<script type="text/javascript" src="../web_home/js/bootstrap.js"></script>
</body>
</html>
