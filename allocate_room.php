<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/config.inc.php';

// Ensure user is logged in
if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$is_admin = $_SESSION['isadmin'] ?? 0;
$manager_id = $_SESSION['hostel_man_id'] ?? 'ADMIN';
$hostel_id = $_SESSION['hostel_id'] ?? null;
$managed_ids = $_SESSION['managed_hostels'] ?? ($hostel_id ? [$hostel_id] : []);

$message = "";
$message_type = "";
$alloc_success = false;
$alloc_details = [];

// HANDLE MANUAL ALLOCATION (This is what happens when the manager clicks the button beside a specific room)
if (isset($_POST['manual_allocate']) && !empty($managed_ids)) {
    $sid = $_POST['student_id'];
    $rid = (int)$_POST['room_id'];
    $rno = $_POST['room_no'];
    $target_h_id = (int)$_POST['hostel_id'];
    $date = date('Y-m-d');

    if ($sid && $rid) {
        $conn->begin_transaction();
        try {
            // 1. Fetch Student/Parent Details for WhatsApp
            $q = $conn->prepare("SELECT Fname, Lname, Mob_no, Parent_mob_no FROM Student WHERE Student_id = ?");
            $q->bind_param("s", $sid);
            $q->execute();
            $s_data = $q->get_result()->fetch_assoc();

            // 2. Update Student Record
            $upd1 = $conn->prepare("UPDATE Student SET Hostel_id = ?, Room_id = ?, Join_date = ? WHERE Student_id = ?");
            $upd1->bind_param("iiss", $target_h_id, $rid, $date, $sid);
            $upd1->execute();

            // 3. Update Application Status
            $upd2 = $conn->prepare("UPDATE Application SET Application_status = '0' WHERE Student_id = ? AND Hostel_id = ?");
            $upd2->bind_param("si", $sid, $target_h_id);
            $upd2->execute();

            // 4. Internal Message
            $msg_body = "Congratulations! You have been manually allocated to Room $rno. Your portal access for bills and payments is now active.";
            $md = date('Y-m-d'); $mt = date('h:i A');
            $ins_msg = $conn->prepare("INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, sender_type, receiver_type) VALUES (?, ?, ?, 'Room Allocation', ?, ?, ?, 'manager', 'student')");
            $ins_msg->bind_param("ssisss", $manager_id, $sid, $target_h_id, $msg_body, $md, $mt);
            $ins_msg->execute();

            // 5. Update Stats
            $conn->query("UPDATE Room SET Allocated = 1 WHERE Room_id = $rid");
            $conn->query("UPDATE Hostel SET No_of_students = No_of_students + 1 WHERE Hostel_id = $target_h_id");

            $conn->commit();
            $alloc_success = true;
            $alloc_details = [
                'name' => ($s_data['Fname'] ?? 'Student') . ' ' . ($s_data['Lname'] ?? ''),
                'mob' => $s_data['Mob_no'] ?? '',
                'pmob' => $s_data['Parent_mob_no'] ?? '',
                'rno' => $rno,
                'hname' => $_POST['hostel_name_hidden'] ?? 'N/A', // Passed from JS
                'college' => 'ESUT Dr. Manmohan Singh Earth Science University of Telangana' 
            ];
            $message = "Success: Student $sid has been assigned to Room $rno.";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Manual Allocation | H-Track</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i" rel="stylesheet">
<style>
    .glass-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 30px; border: 1px solid #eee; }
    .wa-float { position: fixed; bottom: 30px; right: 30px; z-index: 9999; width: 350px; }
    .wa-box { background: #25D366; color: #fff; padding: 15px; border-radius: 10px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    .wa-box.parent { background: #075E54; }
    .sticky-top { position: sticky; top: 0; background: #fff; z-index: 10; }
</style>
</head>

<body>

<div class="inner-page-banner" id="home"> 	   
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">
				<h1><a class="navbar-brand" href="home_manager.php">ESUT</a></h1>
				<div class="collapse navbar-collapse justify-content-center">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item"><a class="nav-link" href="home_manager.php">Home</a></li>
						<li class="nav-item active"><a class="nav-link" href="allocate_room.php">Allocate Room</a></li>
						<li class="nav-item"><a class="nav-link" href="includes/logout.inc.php">Logout</a></li>
					</ul>
				</div>
			</nav>
		</div>
	</header>
</div>

<section class="contact py-5">
	<div class="container">
		<h2 class="heading text-capitalize mb-sm-5 mb-4"> Manual Room Allocation </h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> shadow-sm"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- STEP 1: PENDING APPLICATIONS -->
		<div class="glass-card">
            <h4 class="font-weight-bold mb-4">1. Applications Received</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="bg-dark text-white">
                        <tr><th>Student ID</th><th>Name</th><th>Applied Hostel</th><th>Note</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $ids_str = implode(',', array_map('intval', $managed_ids));
                        $query = "SELECT a.*, s.Fname, s.Lname, h.Hostel_name FROM Application a JOIN Student s ON a.Student_id = s.Student_id JOIN Hostel h ON a.Hostel_id = h.Hostel_id WHERE a.Hostel_id IN ($ids_str) AND a.Application_status = '1'";
                        $apps = mysqli_query($conn, $query);
                        if (mysqli_num_rows($apps) > 0) {
                            while ($row = mysqli_fetch_assoc($apps)) {
                                echo "<tr><td class='font-weight-bold'>{$row['Student_id']}</td><td>{$row['Fname']} {$row['Lname']}</td><td>Hostel {$row['Hostel_name']}</td><td><small>{$row['Message']}</small></td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No pending applications for your buildings.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php if (mysqli_num_rows($apps) > 0): ?>
            <div class="text-center mt-4">
                <button class="btn btn-primary btn-lg px-5" onclick="$('#room-selection-area').fadeIn(); $(this).hide(); window.scrollTo(0, $('#room-selection-area').offset().top);">CHOOSE ROOMS & ALLOCATE</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- STEP 2: ROOM SELECTION (Hidden by default) -->
        <div id="room-selection-area" style="display:none;">
            <div class="glass-card">
                <h4 class="font-weight-bold mb-4">2. Select Student & Target Room</h4>
                
                <div id="selection-error" class="alert alert-danger mb-4 text-center font-weight-bold" style="display:none; border: 2px solid #dc3545; border-radius: 8px;"></div>
            <div class="p-3 bg-light border rounded mb-4 d-flex align-items-center">
                    <strong class="mr-3 text-uppercase">Target Student:</strong>
                    <select id="active-student-id" class="form-control" style="max-width: 450px; border: 2px solid #00bcd4;">
                        <option value="">-- Choose Applicant to Assign --</option>
                        <?php
                        mysqli_data_seek($apps, 0);
                        while ($row = mysqli_fetch_assoc($apps)) {
                            echo "<option value='{$row['Student_id']}' data-hostel='{$row['Hostel_id']}'>{$row['Fname']} {$row['Lname']} ({$row['Student_id']}) - Hostel {$row['Hostel_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-secondary text-white sticky-top">
                            <tr><th>Building</th><th>Room Number</th><th>Current Residents</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $rooms_q = mysqli_query($conn, "SELECT r.*, h.Hostel_name, (SELECT COUNT(*) FROM Student WHERE Room_id = r.Room_id) as student_count FROM Room r JOIN Hostel h ON r.Hostel_id = h.Hostel_id WHERE r.Hostel_id IN ($ids_str) ORDER BY h.Hostel_name ASC, r.Room_No ASC");
                            while ($r = mysqli_fetch_assoc($rooms_q)) {
                                $count = $r['student_count'];
                                $badge_class = $count > 0 ? 'badge-info' : 'badge-light';
                                
                                // Fetch student names for this room
                                $residents = [];
                                if ($count > 0) {
                                    $rid = $r['Room_id'];
                                    $res_q = mysqli_query($conn, "SELECT Fname, Lname FROM Student WHERE Room_id = '$rid'");
                                    while ($s_row = mysqli_fetch_assoc($res_q)) {
                                        $residents[] = htmlspecialchars($s_row['Fname'] . ' ' . $s_row['Lname']);
                                    }
                                }
                                $residents_list = !empty($residents) ? '<br><small class="text-muted">Residents: ' . implode(', ', $residents) . '</small>' : '';

                                echo "<tr class='room-row' data-hostel-id='{$r['Hostel_id']}'>
                                    <td>Hostel {$r['Hostel_name']}</td>
                                    <td class='font-weight-bold'>Room #{$r['Room_No']}</td>
                                    <td><span class='badge badge-pill $badge_class border'>$count Students</span>$residents_list</td>
                                    <td class='text-right'>
                                        <button class='btn btn-success btn-sm font-weight-bold px-4' onclick='confirmAllocation({$r['Room_id']}, \"{$r['Room_No']}\", {$r['Hostel_id']}, \"{$r['Hostel_name']}\")'>ALLOCATE HERE</button>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

	</div>
</section>

<!-- WHATSAPP NOTIFICATION TRAY -->
<?php if ($alloc_success): ?>
<div class="wa-float">
    <?php 
        $wa_msg = "Hello {$alloc_details['name']}, you have been successfully allocated to Room {$alloc_details['rno']} in Hostel {$alloc_details['hname']} at {$alloc_details['college']}. Welcome!";
        $wa_encoded = urlencode($wa_msg);
    ?>
    <div class="wa-box">
        <div><strong>STUDENT</strong><br><small><?php echo $alloc_details['mob']; ?></small></div>
        <a href="https://wa.me/91<?php echo $alloc_details['mob']; ?>?text=<?php echo $wa_encoded; ?>" target="_blank" class="btn btn-light btn-sm">SEND WA</a>
    </div>
    <?php if(!empty($alloc_details['pmob'])): 
        $p_wa_msg = "Dear Parent, your child {$alloc_details['name']} has been assigned to Room {$alloc_details['rno']} in Hostel {$alloc_details['hname']} at {$alloc_details['college']}.";
        $p_wa_encoded = urlencode($p_wa_msg);
    ?>
    <div class="wa-box parent">
        <div><strong>PARENT</strong><br><small><?php echo $alloc_details['pmob']; ?></small></div>
        <a href="https://wa.me/91<?php echo $alloc_details['pmob']; ?>?text=<?php echo $p_wa_encoded; ?>" target="_blank" class="btn btn-light btn-sm">SEND WA</a>
    </div>
    <?php endif; ?>
    <button class="btn btn-dark btn-block btn-sm mt-3" onclick="$('.wa-float').fadeOut()">Close Notification</button>
</div>
<?php endif; ?>

<!-- HIDDEN FORM FOR SUBMISSION -->
<form id="submission-form" method="POST" action="allocate_room.php">
    <input type="hidden" name="student_id" id="post-sid">
    <input type="hidden" name="room_id" id="post-rid">
    <input type="hidden" name="room_no" id="post-rno">
    <input type="hidden" name="hostel_id" id="post-hid">
    <input type="hidden" name="hostel_name_hidden" id="post-hname">
    <input type="hidden" name="manual_allocate" value="1">
</form>

<footer class="py-5">
	<div class="container py-md-5 text-center">
		<a class="navbar-brand" href="#">H-Track</a>
		<p class="mt-4">Manual Allocation Console v2.0</p>
	</div>
</footer>

<script src="web_home/js/jquery-2.2.3.min.js"></script>
<script src="web_home/js/bootstrap.js"></script>
<script>
// Filter rooms based on selected student's hostel
$('#active-student-id').on('change', function() {
    const selectedHostel = $(this).find(':selected').data('hostel');
    $('#selection-error').hide();

    if (selectedHostel) {
        $('.room-row').hide();
        $('.room-row[data-hostel-id="' + selectedHostel + '"]').fadeIn();
    } else {
        $('.room-row').show();
    }
});

function confirmAllocation(rid, rno, hid, hname) {
    const sid = $('#active-student-id').val();
    const studentHostel = $('#active-student-id option:selected').data('hostel');

    if (!sid) {
        $('#selection-error').text('❌ Please select a student from the dropdown above first!').fadeIn();
        window.scrollTo(0, $('#room-selection-area').offset().top);
        return;
    }

    // Process allocation
    $('#post-sid').val(sid);
    $('#post-rid').val(rid);
    $('#post-rno').val(rno);
    $('#post-hid').val(hid);
    $('#post-hname').val(hname);
    $('#submission-form').submit();
}
</script>

</body>
</html>