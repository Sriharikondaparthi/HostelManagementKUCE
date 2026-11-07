<?php
require 'includes/config.inc.php';
$msg = "";
$msg_type = "";

if (isset($_POST['signup-submit'])) {
    $ssc = trim($_POST['ssc_hallticket']);
    $phone = trim($_POST['phone']);
    $hostel_id = trim($_POST['hostel']);
    
    if(!empty($ssc) && !empty($phone) && !empty($hostel_id)){
        // Check if THIS SPECIFIC SSC already requested
        $check = $conn->prepare("SELECT status FROM Signup_Requests WHERE ssc_hallticket = ?");
        $check->bind_param("s", $ssc);
        $check->execute();
        $res = $check->get_result();
        if($res->num_rows > 0){
            $r = $res->fetch_assoc();
            if($r['status'] == 'Pending') {
                $msg = "A request is already pending for this SSC Hall Ticket. Please wait.";
                $msg_type = "warning";
            } elseif($r['status'] == 'Approved') {
                $msg = "This record has already been approved. An SMS with your Hall Ticket was sent.";
                $msg_type = "info";
            } else {
                $msg = "Your previous request was rejected. Please contact the administrator.";
                $msg_type = "danger";
            }
        } else {
            // No check for phone number in Student table - allowing shared numbers
            $ins = $conn->prepare("INSERT INTO Signup_Requests (ssc_hallticket, mob_no, requested_hostel_id) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $ssc, $phone, $hostel_id);
            if($ins->execute()){
                $msg = "Request sent successfully! Once approved, your login Hall Ticket Number will be sent via SMS.";
                $msg_type = "success";
            } else {
                $msg = "Error submitting request. Please try again.";
                $msg_type = "danger";
            }
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Request Portal Access - HMS</title>
    <!-- meta tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="web/css/style.css" rel="stylesheet" type="text/css" />
    <link href="web/css/fontawesome-all.css" rel="stylesheet" />
    <link href="//fonts.googleapis.com/css?family=Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <style>
        .msg-box { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-align: center; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <h1>Hostel Tracking Management System<br>(H-Track)</h1>
    <div class=" w3l-login-form">
        <h2>Request Portal Access</h2>
        
        <?php if($msg != ""): ?>
            <div class="msg-box <?php echo $msg_type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div class=" w3l-form-group">
                <label>SSC Hall Ticket Number:</label>
                <div class="group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" class="form-control" name="ssc_hallticket" placeholder="e.g. 1234567890" required="required" />
                </div>
            </div>
            <div class=" w3l-form-group">
                <label>Phone Number (Mobile):</label>
                <div class="group">
                    <i class="fas fa-phone"></i>
                    <input type="text" class="form-control" name="phone" placeholder="e.g. 9876543210" pattern="[0-9]{10}" title="Enter valid 10-digit mobile number" required="required" />
                </div>
            </div>
            <div class=" w3l-form-group">
                <label>Select Hostel:</label>
                <div class="group" style="padding: 10px 5px !important;">
                    <i class="fas fa-building" style="margin-top:5px; color:#fc3955; padding:0 10px;"></i>
                    <select class="form-control" name="hostel" required="required" style="width:100%; border:none; background:transparent; appearance:none;">
                        <option value="" disabled selected>-- Choose your Hostel --</option>
                        <?php
                        $hostel_desc = [
                            'A' => 'Old Boys Hostel',   'B' => 'New Boys Hostel',
                            'C' => 'Girls Hostel',      'D' => 'Degree Boys Hostel',
                            'E' => 'Degree Girls Hostel','F' => 'PG Hostel'
                        ];
                        
                        $h_sql = "SELECT * FROM Hostel WHERE Hostel_name != '0'";
                        $h_res = mysqli_query($conn, $h_sql);
                        while($h_row = mysqli_fetch_assoc($h_res)) {
                            $base_name = $h_row['Hostel_name'];
                            $d_name = strlen($base_name) <= 2 ? "Hostel " . $base_name : $base_name;
                            if (isset($hostel_desc[$base_name])) {
                                $d_name .= " (" . $hostel_desc[$base_name] . ")";
                            }
                            echo "<option style='color:black;' value='".$h_row['Hostel_id']."'>".$d_name."</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" name="signup-submit" style="background:#00BCD4;">Request Approval for Login</button>
        </form>
        <p class=" w3l-register-p">Already have an account?<a href="student_login.php" class="register"> Login Here</a></p>
    </div>
    <footer>
        <p class="copyright-agileinfo"> &copy; H-Track Project. All Rights Reserved | Design by Aravind Sir Team-06</p>
    </footer>
</body>
</html>
