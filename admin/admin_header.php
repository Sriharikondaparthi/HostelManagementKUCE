<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.inc.php';

if (!isset($_SESSION['isadmin'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>H-Track | Functional Admin Panel</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
    
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"> 
	<link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" /> 
	<link rel="stylesheet" href="../web_home/css_home/fontawesome-all.css"> 
    <link href="../web_home/css_home/slider.css" type="text/css" rel="stylesheet" media="all">
	<link rel="stylesheet" href="../web_home/css_home/flexslider.css" type="text/css" media="screen" property="" />
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">

    <style>
        body { font-family: 'Open Sans', sans-serif; height: 100vh; overflow: hidden; }
        
        /* Full Screen Background Wrapper */
        .banner { position: absolute; top:0; left:0; width:100%; height:100%; z-index: -1; }
        .cd-radial-slider-wrapper { height: 100vh !important; }

        /* Dashboard Layout Structure */
        .dashboard-layout { display: flex; flex-direction: column; height: 100vh; position: relative; z-index: 10; }
        
        /* Premium Top Navbar */
        .top-navbar { 
            background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(15px);
            padding: 10px 40px; border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-between; align-items: center;
            position: relative; z-index: 9999;
        }
        .top-navbar .navbar-brand { font-weight: 800; color: #fff !important; font-size: 1.5em; letter-spacing: 1px; }
        .top-navbar .nav-links { display: flex; align-items: center; }
        .top-navbar .nav-item { margin: 0 10px; list-style: none; position: relative; }
        .top-navbar .nav-link { color: rgba(255,255,255,0.85) !important; font-weight: 600; font-size: 0.9em; text-transform: uppercase; padding: 10px 15px !important; transition: 0.3s; }
        .top-navbar .nav-link:hover { color: #00bcd4 !important; }
        
        /* Functional Sidebar and Main Content */
        .dashboard-body { display: flex; flex: 1; overflow: hidden; position: relative; z-index: 1; }
        
        .sidebar { 
            width: 250px; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255,255,255,0.1); padding: 30px 20px;
            display: flex; flex-direction: column; overflow-y: auto;
        }
        .sidebar-title { color: #00bcd4; font-size: 0.75em; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px; border-bottom: 2px solid rgba(0,188,212,0.3); padding-bottom: 8px; }
        
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-item { margin-bottom: 15px; }
        .sidebar-link { 
            display: flex; align-items: center; padding: 12px 15px; color: rgba(255,255,255,0.8);
            text-decoration: none; border-radius: 12px; transition: 0.3s; background: rgba(255,255,255,0.05);
            font-weight: 500; font-size: 0.9em;
        }
        .sidebar-link i { width: 20px; margin-right: 10px; font-size: 1.1em; color: #00bcd4; opacity: 0.8; }
        .sidebar-link:hover { background: rgba(0,188,212,0.15); color: #fff; text-decoration: none; }
        .sidebar-link.active { background: #00bcd4; color: #fff; box-shadow: 0 4px 15px rgba(0,188,212,0.3); }
        .sidebar-link.active i { color: #fff; }

        /* Main Content Viewport */
        .content-view { flex: 1; overflow-y: auto; padding: 40px; background: rgba(255, 255, 255, 0.02); }
        
        /* Global Glassmorphism Elements */
        .glass-module { 
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2); overflow: hidden;
            margin-bottom: 30px;
        }
        
        .dropdown-menu { background: rgba(0,0,0,0.85); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; min-width: 220px; margin-top: 0; position: absolute; z-index: 10000; }
        .nav-item.dropdown:hover .dropdown-menu { display: block; animation: fadeIn 0.3s ease; }
        
        .dropdown-item { color: rgba(255,255,255,0.8) !important; padding: 12px 20px; font-weight: 500; font-size: 0.9em; }
        .dropdown-item:hover { background: rgba(0,188,212,0.2); color: #fff !important; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<!-- Global Background Slider -->
<div class="banner">
    <div class="cd-radial-slider-wrapper">
        <ul class="cd-radial-slider" data-radius1="60" data-radius2="1364" data-centerx1="110" data-centerx2="1290">
            <li class="visible"><div class="svg-wrapper"><svg viewBox="0 0 1400 800"><defs><clipPath id="cd-image-1"><circle id="cd-circle-1" cx="110" cy="400" r="1364"/></clipPath></defs><image height='800px' width="1400px" clip-path="url(#cd-image-1)" xlink:href="../web_home/images/1.png"></image></svg></div></li>
            <li class="next-slide"><div class="svg-wrapper"><svg viewBox="0 0 1400 800"><defs><clipPath id="cd-image-2"><circle id="cd-circle-2" cx="1290" cy="400" r="60"/></clipPath></defs><image height='800px' width="1400px" clip-path="url(#cd-image-2)" xlink:href="../web_home/images/2.png"></image></svg></div></li>
            <li><div class="svg-wrapper"><svg viewBox="0 0 1400 800"><defs><clipPath id="cd-image-3"><circle id="cd-circle-3" cx="110" cy="400" r="60"/></clipPath></defs><image height='800px' width="1400px" clip-path="url(#cd-image-3)" xlink:href="../web_home/images/3.png"></image></svg></div></li>
        </ul>
    </div>
</div>

<div class="dashboard-layout">
    <!-- Horizontal Navbar -->
    <nav class="top-navbar">
        <a class="navbar-brand" href="<?php echo ($_SESSION['isadmin'] == 1) ? 'admin_home.php' : '../home_manager.php'; ?>">H-Track <span><?php echo ($_SESSION['isadmin'] == 1) ? 'Admin' : 'Manager'; ?></span></a>
        
        <ul class="nav-links mb-0">
            <li class="nav-item"><a class="nav-link" href="<?php echo ($_SESSION['isadmin'] == 1) ? 'admin_home.php' : '../home_manager.php'; ?>">Home</a></li>
            <?php if($_SESSION['isadmin'] == 1): ?>
            <li class="nav-item"><a class="nav-link" href="create_hm.php">Managers</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
            
            <?php if($_SESSION['isadmin'] == 1): ?>
            <!-- Reports Section -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="headReports" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Reports
                </a>
                <div class="dropdown-menu" aria-labelledby="headReports">
                    <a class="dropdown-item" href="student_reports.php"><i class="fas fa-chart-pie mr-2"></i> Student Reports</a>
                    <a class="dropdown-item" href="firm_reports.php"><i class="fas fa-chart-bar mr-2"></i> Firms Report</a>
                    <div class="dropdown-divider border-secondary"></div>
                    <a class="dropdown-item" href="admin_inbox.php"><i class="fas fa-inbox mr-2"></i> Communications Inbox</a>
                    <a class="dropdown-item" href="admin_contact.php"><i class="fas fa-paper-plane mr-2"></i> Contact Center</a>
                    <a class="dropdown-item" href="vacated_students.php"><i class="fas fa-user-times mr-2"></i> Vacated Records</a>
                </div>
            </li>
            <?php endif; ?>

            <!-- Admin Section -->
            <li class="nav-item">
                <a class="nav-link font-weight-bold text-info" href="<?php echo ($_SESSION['isadmin'] == 1) ? 'admin_profile.php' : 'manager_profile.php'; ?>">
                    <i class="fas fa-user-cog mr-1"></i> <?php echo ($_SESSION['isadmin'] == 1) ? 'ADMIN' : 'MANAGER'; ?>
                </a>
            </li>
            <li class="nav-item ml-2">
                <a class="nav-link text-danger font-weight-bold" href="../includes/logout.inc.php" title="Quick Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
        </ul>
    </nav>

    <div class="dashboard-body">
        <!-- Vertical Sidebar -->
        <aside class="sidebar">
            <h5 class="sidebar-title">Operations</h5>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="pending_signups.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'pending_signups.php') echo 'active'; ?>">
                        <i class="fas fa-user-clock"></i> Pending Signups
                        <?php if(isset($pending_signup_count) && $pending_signup_count > 0): ?>
                            <span class="badge badge-danger ml-auto animated pulse infinite" style="padding: 2px 6px; font-size: 0.75em;"><?php echo $pending_signup_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo ($_SESSION['isadmin'] == 1) ? 'admin_home.php' : '../home_manager.php'; ?>" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'admin_home.php') echo 'active'; ?>">
                        <i class="fas fa-graduation-cap"></i> <?php echo ($_SESSION['isadmin'] == 1) ? 'Courses' : 'Home'; ?>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="students.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'students.php') echo 'active'; ?>">
                        <i class="fas fa-users"></i> Students
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="upload_student_entries.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'upload_student_entries.php') echo 'active'; ?>">
                        <i class="fas fa-user-plus"></i> Student Entry
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="upload_mandates.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'upload_mandates.php') echo 'active'; ?>">
                        <i class="fas fa-file-contract"></i> Mandates
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="upload_firms.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'upload_firms.php') echo 'active'; ?>">
                        <i class="fas fa-building"></i> Firms
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="generate_bills.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'generate_bills.php') echo 'active'; ?>">
                        <i class="fas fa-file-invoice-dollar"></i> Bill Generation
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="view_final_bills.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'view_final_bills.php') echo 'active'; ?>">
                        <i class="fas fa-receipt"></i> Final Bills
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="update_challans.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'update_challans.php') echo 'active'; ?>">
                        <i class="fas fa-edit"></i> Fee Challans Updates
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="firm_payments.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'firm_payments.php') echo 'active'; ?>">
                        <i class="fas fa-money-check-alt"></i> Firms Payments
                    </a>
                </li>
            </ul>
            
            <?php if($_SESSION['isadmin'] == 1): ?>
            <h5 class="sidebar-title mt-4 pt-2">Analytics & Reports</h5>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="student_reports.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'student_reports.php') echo 'active'; ?>">
                        <i class="fas fa-chart-pie"></i> Student Reports
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="firm_reports.php" class="sidebar-link <?php if(basename($_SERVER['PHP_SELF']) == 'firm_reports.php') echo 'active'; ?>">
                        <i class="fas fa-chart-bar"></i> Firms Report
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </aside>

        <!-- Main Workspace -->
        <main class="content-view">
