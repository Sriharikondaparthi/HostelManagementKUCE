<?php
require 'includes/config.inc.php'; 
date_default_timezone_set('Asia/Kolkata'); 

$hall_ticket = $_SESSION['hall_ticket'] ?? '';

// Defensive: ensure we have a roll to look up messages for
if ($hall_ticket === '') {
    header("Location: index.php");
    exit();
}

// Validate Manager Assignment
if (!isset($manager_assigned) || !$manager_assigned) {
    echo "<!DOCTYPE html><html lang='en'><head><title>Access Denied</title><link rel='stylesheet' href='web_home/css_home/bootstrap.css'><style>body{background:#f8f9fa;display:flex;align-items:center;justify-content:center;height:100vh;}</style></head><body>
    <div class='container text-center'><div class='card shadow p-5'><h1 class='text-danger mb-4'>Access Restricted</h1><p class='lead'>Your attendance records have not been activated yet. Please wait until a manager is assigned to your hostel.</p><a href='home.php' class='btn btn-primary mt-3'>Return to Home</a></div></div>
    </body></html>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>H-Track - Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
    <link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
    <style type="text/css">
        .card-header { padding: 15px; font-size: 30px; }
        .card-body { padding: 15px; }
        .card-footer { text-align: left; padding: 15px; }
    </style>
</head>

<body>
<div class="inner-page-banner" id="home">
    <header>
        <div class="container agile-banner_nav">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <h1><a class="navbar-brand" href="home.php">H-Track</a></h1>
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="services.php">Hostels</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="message_user.php">Message Received <?php if(isset($unread_msg_count) && $unread_msg_count > 0) { echo '<span class="badge badge-danger">'.$unread_msg_count.'</span>'; } ?></a>
                        </li>
                        <?php if (isset($manager_assigned) && $manager_assigned) { ?>
                        <li class="nav-item"><a class="nav-link" href="hostel_bills.php">Hostel Bills</a></li>
                        <li class="nav-item"><a class="nav-link" href="student_payment.php">Payments</a></li>
                        <li class="nav-item active"><a class="nav-link" href="attendance_user.php">Attendance</a></li>
                        <?php } ?>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['hall_ticket'] ?? ''); ?>
                                <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu agile_short_dropdown">
                                <li><a href="profile.php">My Profile</a></li>
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
    <h3 style="margin-bottom: 20px;">Monthly Attendance Summaries</h3>
    <table class="table table-bordered table-hover" style="background:#fff;">
      <thead style="background:#007bff; color: white;">
        <tr>
            <th>Month & Year</th>
            <th>Total Days Logged</th>
            <th>Days Present</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $sum_sql = "SELECT DATE_FORMAT(date, '%M %Y') AS month_year, 
                         SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days,
                         COUNT(date) AS total_logged
                  FROM attendance 
                  WHERE Student_id = ? 
                  GROUP BY DATE_FORMAT(date, '%M %Y')
                  ORDER BY MAX(date) DESC";
      $sum_stmt = $conn->prepare($sum_sql);
      if ($sum_stmt) {
          $sum_stmt->bind_param("s", $hall_ticket);
          $sum_stmt->execute();
          $sum_res = $sum_stmt->get_result();
          if ($sum_res && $sum_res->num_rows > 0) {
              while ($sum_row = $sum_res->fetch_assoc()) {
                  echo "<tr>";
                  echo "<td><strong>".htmlspecialchars($sum_row['month_year'])."</strong></td>";
                  echo "<td>".htmlspecialchars($sum_row['total_logged'])."</td>";
                  echo "<td><span style='color:green; font-weight:bold;'>".htmlspecialchars($sum_row['present_days'])." </span></td>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='3' style='text-align:center;'>No monthly summaries available.</td></tr>";
          }
          $sum_stmt->close();
      }
      ?>
      </tbody>
    </table>

    <br>
    <h3 style="margin-bottom: 20px;">Daily Attendance Log</h3>
    <table class="table table-bordered table-striped" style="background:#fff;">
      <thead class="thead-dark">
        <tr><th>Date</th><th>Status</th></tr>
      </thead>
      <tbody>
      <?php
      $att_sql = "SELECT date, status FROM attendance WHERE Student_id = ? ORDER BY date DESC LIMIT 30";
      $att_stmt = $conn->prepare($att_sql);
      if ($att_stmt) {
          $att_stmt->bind_param("s", $hall_ticket);
          $att_stmt->execute();
          $att_res = $att_stmt->get_result();
          if ($att_res && $att_res->num_rows > 0) {
              while ($att_row = $att_res->fetch_assoc()) {
                  $st_color = ($att_row['status'] == 'Present') ? 'green' : 'red';
                  echo "<tr><td>".htmlspecialchars($att_row['date'])."</td><td style='color:{$st_color}; font-weight:bold;'>".htmlspecialchars($att_row['status'])."</td></tr>";
              }
          } else {
              echo "<tr><td colspan='2' style='text-align:center;'>No recent attendance logs.</td></tr>";
          }
          $att_stmt->close();
      } else {
          echo "<tr><td colspan='2' style='text-align:center;'>Unable to load attendance at this time.</td></tr>";
      }
      ?>
      </tbody>
    </table>
</div>

<br><br><br>

<footer class="py-5">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center"><a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT</a></div>
        <div class="footer-grid">
            <div class="list-footer">
                <ul class="footer-nav text-center">
                    <li><a href="home.php">Home</a></li>
                    <li><a href="services.php">Hostels</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
<script src="web_home/js/SmoothScroll.min.js"></script>
<script type="text/javascript" src="web_home/js/move-top.js"></script>
<script type="text/javascript" src="web_home/js/easing.js"></script>
</body>
</html>
