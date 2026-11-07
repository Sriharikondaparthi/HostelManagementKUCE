<?php
  require 'includes/config.inc.php';

  if(isset($_POST['request_visit'])) {
      $vname = $_POST['visitor_name'];
      $sname = $_POST['student_name'];
      $sid = $_POST['student_id'];
      $relation = $_POST['relation'];
      $hours = (int)$_POST['hours'];
      $overnight = $_POST['staying_overnight'];
      $stay_days = isset($_POST['staying_days']) ? (int)$_POST['staying_days'] : 0;
      if ($overnight == 'No') $stay_days = 0;
      $mobile = $_POST['mobile_no'];
      $vdate = $_POST['visit_date'];
      $vtime = $_POST['visit_time'];
      $date = date('Y-m-d H:i:s');

      // Phase 1: Dynamic Table Initialization to prevent errors
      $create_query = "CREATE TABLE IF NOT EXISTS Visitor_Requests (
          id INT AUTO_INCREMENT PRIMARY KEY,
          visitor_name VARCHAR(255) NOT NULL,
          student_name VARCHAR(255) NOT NULL,
          student_id VARCHAR(50) NOT NULL,
          relation VARCHAR(100) NOT NULL,
          hours INT NOT NULL,
          staying_overnight VARCHAR(10) NOT NULL,
          staying_days INT DEFAULT 0,
          mobile_no VARCHAR(20) NOT NULL,
          visit_date DATE NULL,
          visit_time TIME NULL,
          status VARCHAR(20) DEFAULT 'Pending',
          request_date DATETIME
      )";
      mysqli_query($conn, $create_query);
      
      // Upgrade existing table if needed seamlessly
      try { @mysqli_query($conn, "ALTER TABLE Visitor_Requests ADD COLUMN student_name VARCHAR(255) AFTER visitor_name"); } catch (mysqli_sql_exception $e) {}
      try { @mysqli_query($conn, "ALTER TABLE Visitor_Requests ADD COLUMN staying_days INT DEFAULT 0 AFTER staying_overnight"); } catch (mysqli_sql_exception $e) {}
      try { @mysqli_query($conn, "ALTER TABLE Visitor_Requests ADD COLUMN visit_date DATE AFTER mobile_no"); } catch (mysqli_sql_exception $e) {}
      try { @mysqli_query($conn, "ALTER TABLE Visitor_Requests ADD COLUMN visit_time TIME AFTER visit_date"); } catch (mysqli_sql_exception $e) {}

      // Verify if student exists
      $check = $conn->prepare("SELECT Hostel_id FROM Student WHERE Student_id = ?");
      $check->bind_param("s", $sid);
      $check->execute();
      $res = $check->get_result();

      if($res->num_rows > 0) {
          $row = $res->fetch_assoc();
          $hid = $row['Hostel_id'];
          
          $ins = $conn->prepare("INSERT INTO Visitor_Requests (visitor_name, student_name, student_id, relation, hours, staying_overnight, staying_days, mobile_no, visit_date, visit_time, request_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
          $ins->bind_param("ssssissssss", $vname, $sname, $sid, $relation, $hours, $overnight, $stay_days, $mobile, $vdate, $vtime, $date);
          
          if($ins->execute()) {
               // Find manager for this hostel to send a message
               $mgr_q = $conn->prepare("SELECT Hostel_man_id FROM Hostel_Manager WHERE Hostel_id = ? LIMIT 1");
               $mgr_q->bind_param("i", $hid);
               $mgr_q->execute();
               $mgr_res = $mgr_q->get_result();
               if($mgr_row = $mgr_res->fetch_assoc()) {
                   $mgr_id = $mgr_row['Hostel_man_id'];
                   $msg_subject = "New Visitor Request";
                   $msg_body = "A new visitor request has been received from visitor '$vname' for student '$sname' ($sid). Expected on $vdate at $vtime.";
                   $msg_date = date('Y-m-d');
                   $msg_time = date('h:i A');

                   $ins_msg = $conn->prepare("INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, sender_type, receiver_type, is_read) VALUES (?, ?, ?, ?, ?, ?, ?, 'student', 'manager', 0)");
                   if ($ins_msg) {
                       $ins_msg->bind_param("ssissss", $sid, $mgr_id, $hid, $msg_subject, $msg_body, $msg_date, $msg_time);
                       $ins_msg->execute();
                   }
               }
               
              echo "<script>alert('Your visit request was dispatched to the Hostel Manager securely! You will receive an SMS regarding their decision shortly.'); window.location.href='index.php';</script>";
          } else {
              echo "<script>alert('Internal error filing request.');</script>";
          }
      } else {
          echo "<script>alert('We could not find a target student with that ID or they are not placed in a hostel yet.');</script>";
      }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>HMS - Visitor Portal</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="web/css/style.css" rel="stylesheet" type="text/css" />
    <link href="web/css/fontawesome-all.css" rel="stylesheet" />
    <link href="//fonts.googleapis.com/css?family=Raleway:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <style>
         .w3l-login-form {
             max-width: 500px !important;
         }
         .w3l-form-group.selection-box {
             margin-bottom: 20px;
         }
         input.form-control, select.form-control {
             color: #000 !important; /* Force text to be black so it is always visible while typing */
             background-color: #fff !important; /* Solid white background for the input blanks */
             padding: 12px;
             border: 1px solid #ccc;
             border-radius: 4px;
             width: 100%;
             font-size: 15px;
             text-align: center; /* Center the inputted text inside the box */
         }
         select.form-control option {
             background: #fff;
             color: #000;
         }
         .group i {
             color: #000 !important;
             z-index: 10;
             position: absolute;
             margin-top: 15px;
             margin-left: 15px; /* Move icon slightly away from the left border */
         }
         .group input.form-control {
             padding-left: 50px; /* Give more space so text starts further away from the icon */
             text-transform: capitalize; /* Automatically enforce grammar rule (Capitalize first letters) */
         }
    </style>
    <script>
        function toggleDays() {
            var stayVal = document.getElementById("stay_toggle").value;
            var daysBox = document.getElementById("days_container");
            var displaySpan = document.getElementById("selected_display");
            
            if (stayVal === "Yes") {
                daysBox.style.display = "block";
                updateDisplay(); 
            } else {
                daysBox.style.display = "none";
                displaySpan.innerHTML = "You have selected: <strong>Not staying overnight</strong>";
            }
        }
        
        function updateDisplay() {
            var stayVal = document.getElementById("stay_toggle").value;
            var daysVal = document.getElementById("stay_days_select").value;
            var displaySpan = document.getElementById("selected_display");
            
            if (stayVal === "Yes") {
                displaySpan.innerHTML = "You have selected: <strong>Staying overnight for " + daysVal + " day(s)</strong>";
            }
        }
        
        window.onload = function() { toggleDays(); };
    </script>
</head>

<body>
    <h1>Hostel Tracking Management System</h1>
    <div class="w3l-login-form">
        <h2>Schedule a Visit</h2>
        <form action="apply_visitor.php" method="POST">

            <div class="w3l-form-group">
                <label>Visitor Full Name:</label>
                <div class="group">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" name="visitor_name" placeholder="John Doe" required />
                </div>
            </div>
            
            <div class="w3l-form-group">
                <label>Student Name:</label>
                <div class="group">
                    <i class="fas fa-user-graduate"></i>
                    <input type="text" class="form-control" name="student_name" placeholder="Student Name" required />
                </div>
            </div>

            <div class="w3l-form-group">
                <label>Target Student Hall Ticket Number:</label>
                <div class="group">
                    <i class="fas fa-id-badge"></i>
                    <input type="text" class="form-control" name="student_id" placeholder="Student ID" required />
                </div>
            </div>

            <div class="w3l-form-group">
                <label>Your Relationship to Student:</label>
                <div class="group">
                    <i class="fas fa-users"></i>
                    <input type="text" class="form-control" name="relation" placeholder="Parent / Sibling / Friend" required />
                </div>
            </div>
            
            <div class="w3l-form-group">
                <label>Expected Date of Visit:</label>
                <div class="group">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="date" class="form-control" name="visit_date" required />
                </div>
            </div>

            <div class="w3l-form-group">
                <label>Expected Time of Arrival:</label>
                <div class="group">
                    <i class="fas fa-hourglass-start"></i>
                    <input type="time" class="form-control" name="visit_time" required />
                </div>
            </div>

            <div class="w3l-form-group selection-box">
                <label>Estimated Hours of Visit:</label>
                <div class="group">
                    <i class="fas fa-clock"></i>
                    <input type="number" class="form-control" name="hours" placeholder="Total hours (e.g., 2)" min="1" max="24" required />
                </div>
            </div>

            <div class="w3l-form-group selection-box">
                <label>Will you stay overnight in the hostel?</label>
                <div class="group">
                    <select name="staying_overnight" id="stay_toggle" class="form-control" required onchange="toggleDays()">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
            </div>
            
            <div class="w3l-form-group selection-box" id="days_container" style="display: none;">
                <label>How many days will you stay room?</label>
                <div class="group">
                    <select name="staying_days" id="stay_days_select" class="form-control" onchange="updateDisplay()">
                        <option value="1">1 Day</option>
                        <option value="2">2 Days</option>
                        <option value="3">3 Days</option>
                        <option value="4">4 Days</option>
                        <option value="5">5+ Days</option>
                    </select>
                </div>
            </div>
            
            <p id="selected_display" style="color:#ffc107; margin-bottom: 20px; font-size: 14px; text-align: center; border: 1px dashed #ffc107; padding: 10px;"></p>

            <div class="w3l-form-group">
                <label>Your Mobile Number:</label>
                <div class="group">
                    <i class="fas fa-mobile-alt"></i>
                    <input type="text" class="form-control" name="mobile_no" placeholder="+91 XXXXX XXXXX" required />
                </div>
            </div>

            <button type="submit" name="request_visit">Submit</button>
        </form>
        <p class="w3l-register-p"><a href="index.php" class="register">Back to Login</a></p>
    </div>
    <footer>
        <p class="copyright-agileinfo">&copy; H-Track Project. All Rights Reserved | Design by Aravind Sir Team-06</p>
    </footer>
</body>
</html>
