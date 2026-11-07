<?php
require 'includes/config.inc.php';

// Check if user is logged in
if (!isset($_SESSION['hall_ticket'])) {
    header("Location: index.php");
    exit();
}

$hall_ticket = $_SESSION['hall_ticket'];
$error = "";
$success = "";

// Security Check: If profile is already completed, redirect to home
$check_sql = "SELECT profile_completed FROM Student WHERE Student_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $hall_ticket);
$check_stmt->execute();
$check_res = $check_stmt->get_result();
if ($row = $check_res->fetch_assoc()) {
    if ($row['profile_completed'] == 1) {
        header("Location: home.php");
        exit();
    }
}
$check_stmt->close();

// Handle form submission
if (isset($_POST['setup-submit'])) {
    $fname = strtoupper(trim($_POST['fname']));
    $lname = strtoupper(trim($_POST['lname']));
    $father_name = strtoupper(trim($_POST['father_name']));
    $mother_name = strtoupper(trim($_POST['mother_name']));
    $address = strtoupper(trim($_POST['address']));
    $dept = strtoupper(trim($_POST['dept']));
    $semester = trim($_POST['semester']);
    $year_of_study = trim($_POST['year_of_study']);
    $dob = $_POST['dob'];
    $whatsapp = trim($_POST['whatsapp']);
    $parent_mob = trim($_POST['parent_mob']);
    $email = strtolower(trim($_POST['email']));
    $pwd = $_POST['pwd'];
    $conf_pwd = $_POST['conf_pwd'];
    $declaration = isset($_POST['declaration']) ? 1 : 0;

    if (empty($fname) || empty($lname) || empty($father_name) || empty($mother_name) || empty($address) || empty($dept) || empty($semester) || empty($year_of_study) || empty($dob) || empty($whatsapp) || empty($parent_mob) || empty($email) || empty($pwd)) {
        $error = "All fields except photo/signature are required.";
    } elseif ($pwd !== $conf_pwd) {
        $error = "Passwords do not match.";
    } elseif (!$declaration) {
        $error = "You must agree to the declaration.";
    } else {
        
        $photo_path = null;
        $signature_path = null;

        if (!is_dir('uploads/student_photos')) { mkdir('uploads/student_photos', 0777, true); }
        if (!is_dir('uploads/student_signatures')) { mkdir('uploads/student_signatures', 0777, true); }

        if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg'])){
                $photo_path = 'uploads/student_photos/' . $hall_ticket . '_photo_' . time() . '.' . $ext;
                if(!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)){
                    $error = "Failed to upload photo.";
                }
            } else {
                $error = "Only JPG/JPEG allowed for photo.";
            }
        } else {
            $error = "Please provide a valid photograph.";
        }
        
        if(isset($_FILES['signature']) && $_FILES['signature']['error'] == 0){
            $ext = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg'])){
                $signature_path = 'uploads/student_signatures/' . $hall_ticket . '_sig_' . time() . '.' . $ext;
                if(!move_uploaded_file($_FILES['signature']['tmp_name'], $signature_path)){
                    $error = "Failed to upload signature.";
                }
            } else {
                $error = "Only JPG/JPEG allowed for signature.";
            }
        } else {
            $error = "Please provide a valid signature.";
        }

        if(empty($error)){
            $hashed_pwd = password_hash($pwd, PASSWORD_DEFAULT);
            $sql = "UPDATE Student SET Fname=?, Lname=?, Father_name=?, Mother_name=?, Address=?, Dept=?, Semester=?, Academic_Year=?, DOB=?, Mob_no=?, Parent_mob_no=?, email=?, Pwd=?, Photo=?, Signature=?, Declaration_agreed=?, profile_completed=1 WHERE Student_id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // 17 params: 15s + 1i + 1s -> sssssssssssssssis
                $stmt->bind_param("sssssssssssssssis", $fname, $lname, $father_name, $mother_name, $address, $dept, $semester, $year_of_study, $dob, $whatsapp, $parent_mob, $email, $hashed_pwd, $photo_path, $signature_path, $declaration, $hall_ticket);
                if ($stmt->execute()) {
                    $_SESSION['fname'] = $fname;
                    $_SESSION['lname'] = $lname;
                    $_SESSION['department'] = $dept;
                    $_SESSION['semester'] = $semester;
                    $_SESSION['father_name'] = $father_name;
                    $_SESSION['mother_name'] = $mother_name;
                    $_SESSION['dob'] = $dob;
                    $_SESSION['mob_no'] = $whatsapp;
                    header("Location: home.php?setup=success");
                    exit();
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Prepare error: " . $conn->error;
            }
        }
    }
}

// Fetch current basic info to pre-fill if available
$pre_fname = $_SESSION['fname'] ?? "";
$pre_dept = $_SESSION['department'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Student Profile Setup - Dr. Manmohan Singh Earth Science University of Telangana</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
<link href="//fonts.googleapis.com/css?family=Montserrat:400,600,700,800&amp;subset=cyrillic,latin-ext" rel="stylesheet">
<style>
    body { 
        background-color: #f4f6f9;
        background-image: url('web/images/cover4.jpg'); 
        background-blend-mode: multiply;
        background-color: rgba(0, 32, 96, 0.4); /* Dark blue overlay for college vibe */
        background-size: cover; 
        background-attachment: fixed; 
        background-position: center;
        font-family: 'Montserrat', sans-serif;
    }
    .top-bar { display: flex; justify-content: space-between; align-items: center; background: rgba(0, 32, 96, 0.9); padding: 15px 30px; border-bottom: 3px solid #ffcc00; }
    .top-bar .college-title { margin: 0; color: #fff; font-weight: 800; font-size: 24px; text-transform: uppercase; letter-spacing: 1.5px; }
    .top-bar .btn-abort { background: transparent; border: 2px solid #ffcc00; color: #ffcc00; padding: 6px 20px; font-weight: 600; border-radius: 4px; transition: 0.3s; text-decoration: none; }
    .top-bar .btn-abort:hover { background: #ffcc00; color: #002060; }
    .setup-container { max-width: 800px; margin: 40px auto 60px; background: rgba(255, 255, 255, 0.98); padding: 40px; border-radius: 8px; box-shadow: 0 15px 40px rgba(0,0,0,0.5); border-top: 6px solid #002060; }
    .setup-header { border-bottom: 2px solid #eee; margin-bottom: 30px; padding-bottom: 15px; color: #002060; font-weight: 800; text-transform: uppercase; font-size: 22px; text-align: center; }
    .form-group label { font-weight: 700; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
    .btn-setup { background: #002060; color: #fff; font-weight: 700; padding: 14px; transition: all 0.3s; border-radius: 4px; border: none; text-transform: uppercase; letter-spacing: 1px; }
    .btn-setup:hover { background: #001540; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,32,96,0.3); }
    .btn-preview { background: #ffcc00; color: #002060; font-weight: 800; padding: 14px; transition: all 0.3s; border-radius: 4px; border: none; text-transform: uppercase; letter-spacing: 1px; }
    .btn-preview:hover { background: #e6b800; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,204,0,0.3); }
    .preview-box { border: 2px dashed #b0bec5; padding: 10px; text-align: center; border-radius: 4px; margin-top: 10px; min-height: 150px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; }
    .preview-box img { max-width: 100%; max-height: 200px; border-radius: 4px; display: none; }
    .declaration-box { background: #fff; border: 1px solid #ddd; border-left: 5px solid #002060; padding: 20px; margin: 20px 0; border-radius: 0 4px 4px 0; }
    .declaration-box h5 { color: #002060; font-weight: 800; text-transform: uppercase; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
    .step-section { display: none; }
    .step-section.active { display: block; animation: fadeIn 0.5s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .summary-card { background: #f8f9fa; border: 1px solid #ddd; padding: 25px; border-radius: 4px; margin-bottom: 25px; }
    .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .summary-item { font-size: 15px; }
    .summary-item strong { display: block; color: #002060; font-weight: 700; text-transform: uppercase; font-size: 12px; margin-bottom: 4px; }
    .summary-item span { text-transform: uppercase; font-weight: 600; color: #111; }
    #sum-email { text-transform: lowercase !important; }
    .form-control { border-radius: 4px; border: 1px solid #ccc; padding: 10px; font-size: 15px; }
    .form-control:focus { border-color: #002060; box-shadow: 0 0 0 0.2rem rgba(0,32,96,0.15); }
    input[type="text"], textarea { text-transform: uppercase; }
    #email { text-transform: lowercase; }
    hr { border-top: 2px solid #eee; margin: 30px 0; }
</style>
</head>
<body>

<div class="top-bar">
    <h1 class="college-title"><i class="fas fa-university mr-2"></i> Dr. Manmohan Singh Earth Science University of Telangana</h1>
    <a href="includes/logout.inc.php" class="btn-abort"><i class="fas fa-sign-out-alt mr-2"></i> Back to Login</a>
</div>

<div class="container">
    <div class="setup-container">
        <h2 class="setup-header">Student Profile Initialization</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="setup_profile.php" method="POST" enctype="multipart/form-data" id="setupForm">
            
            <!-- STEP 1: Main Form -->
            <div id="step1" class="step-section active">
                <p class="text-muted text-center mb-4">Welcome! Since this is your first login, please provide your details to activate your account. <strong>Note:</strong> These details (except password) can only be set once.</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" id="fname" name="fname" class="form-control" value="<?php echo htmlspecialchars($pre_fname); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" id="lname" name="lname" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" id="father_name" name="father_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" id="mother_name" name="mother_name" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Residential Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3" required placeholder="Enter your full permanent address..."></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Branch / Department</label>
                            <input type="text" id="dept" name="dept" class="form-control" value="<?php echo htmlspecialchars($pre_dept); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Year of Study</label>
                            <select id="year_of_study" name="year_of_study" class="form-control" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Semester</label>
                            <select id="semester" name="semester" class="form-control" required>
                                <option value="">Select Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                                <option value="7">Semester 7</option>
                                <option value="8">Semester 8</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email ID</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="yourname@gmail.com" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Student WhatsApp Number</label>
                            <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="e.g. 9876543210" pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number" required>
                            <small class="text-muted">For automated bill notifications.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Parents' Phone Number</label>
                            <input type="text" id="parent_mob" name="parent_mob" class="form-control" placeholder="e.g. 9876543210" pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number" required>
                            <small class="text-muted">For emergency/verification.</small>
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3" style="color: #00bcd4; font-weight: bold;">Documents Upload (JPEG only)</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Passport Size Photograph</label>
                            <input type="file" id="photo" name="photo" class="form-control-file" accept=".jpg, .jpeg" required>
                            <div class="preview-box">
                                <span class="text-muted id-placeholder-text">Photo Preview</span>
                                <img id="photo-preview" src="" alt="Photo Preview">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Student Signature</label>
                            <input type="file" id="signature" name="signature" class="form-control-file" accept=".jpg, .jpeg" required>
                            <div class="preview-box">
                                <span class="text-muted id-placeholder-text-sig">Signature Preview</span>
                                <img id="signature-preview" src="" alt="Signature Preview">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="declaration-box">
                    <h5 style="color: #333; font-weight: bold;">Declaration</h5>
                    <p class="text-muted text-justify" style="font-size: 14px;">I hereby declare that all the particulars and information provided by me in this application form are true, correct and complete to the best of my knowledge and belief. I understand that in the event of any information being found false or incorrect at any stage, my registration/admission shall be liable to cancellation without notice.</p>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="declaration" name="declaration" required>
                        <label class="form-check-label font-weight-bold" for="declaration">I accept the declaration and terms.</label>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Set New Password</h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" id="pwd" name="pwd" class="form-control" minlength="6" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" id="conf_pwd" name="conf_pwd" class="form-control" required>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-preview btn-block mt-4" id="btn-preview-step">Review Provided Details</button>
            </div>

            <!-- STEP 2: Preview Summary -->
            <div id="step2" class="step-section">
                <h4 class="text-center mb-4" style="color: #ff9800;">Review Your Details</h4>
                
                <div class="row mb-4">
                    <div class="col-md-6 text-center">
                        <h6 style="color: #002060; font-weight: 700; text-transform: uppercase;">Profile Photo</h6>
                        <img id="sum-photo" src="" alt="No Photo" style="max-height: 140px; border-radius: 4px; border: 1px solid #ccc; padding: 5px; background: #fff;">
                    </div>
                    <div class="col-md-6 text-center">
                        <h6 style="color: #002060; font-weight: 700; text-transform: uppercase;">Signature</h6>
                        <img id="sum-sig" src="" alt="No Signature" style="max-height: 140px; border-radius: 4px; border: 1px solid #ccc; padding: 5px; background: #fff;">
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-grid">
                        <div class="summary-item"><strong>First Name:</strong> <span id="sum-fname"></span></div>
                        <div class="summary-item"><strong>Last Name:</strong> <span id="sum-lname"></span></div>
                        <div class="summary-item"><strong>Father's Name:</strong> <span id="sum-father"></span></div>
                        <div class="summary-item"><strong>Mother's Name:</strong> <span id="sum-mother"></span></div>
                        <div class="summary-item"><strong>Branch / Dept:</strong> <span id="sum-dept"></span></div>
                        <div class="summary-item"><strong>Year of Study:</strong> <span id="sum-year"></span></div>
                        <div class="summary-item"><strong>Semester:</strong> <span id="sum-sem"></span></div>
                        <div class="summary-item"><strong>Date of Birth:</strong> <span id="sum-dob"></span></div>
                        <div class="summary-item"><strong>Email ID:</strong> <span id="sum-email"></span></div>
                        <div class="summary-item"><strong>WhatsApp No:</strong> <span id="sum-whatsapp"></span></div>
                        <div class="summary-item"><strong>Parents' Phone:</strong> <span id="sum-parent"></span></div>
                        <div class="summary-item" style="grid-column: 1 / -1;"><strong>Residential Address:</strong> <span id="sum-addr"></span></div>
                    </div>
                </div>
                <div class="alert alert-info">Please confirm that your photo and signature above are clear. Passwords are set securely.</div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary px-4" id="btn-back" style="font-weight: 700; text-transform: uppercase;"><i class="fas fa-edit mr-2"></i>Modify</button>
                    <button type="submit" name="setup-submit" class="btn btn-setup px-4"><i class="fas fa-save mr-2"></i>Save</button>
                </div>
            </div>

        </form>
    </div>
</div>

<script src="web_home/js/jquery-2.2.3.min.js"></script>
<script src="web_home/js/bootstrap.js"></script>
<script>
    // File inputs preview logic
    function readURL(input, imgElement, placeholderSelector) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $(imgElement).attr('src', e.target.result).show();
                $(placeholderSelector).hide();
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            $(imgElement).hide();
            $(placeholderSelector).show();
        }
    }

    $("#photo").change(function() {
        readURL(this, '#photo-preview', '.id-placeholder-text');
    });

    $("#signature").change(function() {
        readURL(this, '#signature-preview', '.id-placeholder-text-sig');
    });

    // Step navigation
    $("#btn-preview-step").click(function() {
        // Simple HTML5 validation trigger
        var form = $("#setupForm")[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Passwords match check
        if ($("#pwd").val() !== $("#conf_pwd").val()) {
            alert("Passwords do not match!");
            return;
        }

        // Fill Summary
        $("#sum-fname").text($("#fname").val());
        $("#sum-lname").text($("#lname").val());
        $("#sum-father").text($("#father_name").val());
        $("#sum-mother").text($("#mother_name").val());
        $("#sum-dept").text($("#dept").val());
        $("#sum-year").text($("#year_of_study").val());
        $("#sum-sem").text($("#semester").val());
        $("#sum-dob").text($("#dob").val());
        $("#sum-email").text($("#email").val());
        $("#sum-whatsapp").text($("#whatsapp").val());
        $("#sum-parent").text($("#parent_mob").val());
        $("#sum-addr").text($("#address").val());
        
        // Copy image src
        $("#sum-photo").attr("src", $("#photo-preview").attr("src"));
        $("#sum-sig").attr("src", $("#signature-preview").attr("src"));

        // Switch to preview step
        $("#step1").removeClass("active");
        $("#step2").addClass("active");
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    $("#btn-back").click(function() {
        $("#step2").removeClass("active");
        $("#step1").addClass("active");
    });
</script>
</body>
</html>

