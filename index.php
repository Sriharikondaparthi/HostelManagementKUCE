<?php
require 'includes/config.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>H-Track | Hostel Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
    <link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
    <link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Open Sans', sans-serif;
            background-color: transparent; 
            color: #fff;
            scroll-behavior: smooth;
        }

        /* Fixed Navigation */
        .navbar {
            background: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(10px);
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 10000;
        }

        .navbar-brand {
            font-family: 'Poiret One', cursive;
            font-size: 2.2em;
            color: #ffc107 !important;
            font-weight: bold;
        }

        .nav-link {
            color: #fff !important;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 15px;
            letter-spacing: 1px;
            cursor: pointer;
        }

        .nav-item.active .nav-link {
            color: #ffc107 !important;
            border-bottom: 2px solid #ffc107;
        }

        /* Fixed Hero Background */
        .hero-section {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: transparent;
        }

        .hero-slider {
            position: fixed; /* Keep it fixed in background */
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
        }

        .hero-slide {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        .hero-slide.active { opacity: 1; }

        .hero-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Slightly darker overlay for better text readability on scroll */
        }

        .hero-text {
            z-index: 1;
            animation: fadeInUp 1.2s ease;
        }

        .hero-text h1 {
            font-family: 'Poiret One', cursive;
            font-size: 5em;
            color: #fff;
            text-shadow: 0 5px 15px rgba(0,0,0,0.5);
            margin-bottom: 10px;
        }

        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            animation: bounce 2s infinite;
            cursor: pointer;
            color: #ffc107;
            font-size: 2em;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0) translateX(-50%);}
            40% {transform: translateY(-10px) translateX(-50%);}
            60% {transform: translateY(-5px) translateX(-50%);}
        }

        /* Content Area (Transparent Background) */
        .info-section {
            padding: 100px 0;
            background: transparent; 
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            padding: 50px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        h2.section-title {
            font-family: 'Poiret One', cursive;
            font-size: 3.5em;
            color: #ffc107;
            margin-bottom: 40px;
            text-align: center;
        }

        /* Custom Cursor */
        #cursor-dot { position: fixed; width: 8px; height: 8px; background: #ffc107; border-radius: 50%; pointer-events: none; z-index: 20000; }
        #cursor-ring { position: fixed; width: 35px; height: 35px; border: 2px solid rgba(255, 193, 7, 0.4); border-radius: 50%; pointer-events: none; z-index: 19999; transition: 0.1s; }

        /* Hostel/Login Grid Styling */
        .grid-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            transition: 0.3s;
            cursor: pointer;
            height: 100%;
            text-align: center;
        }
        .grid-item:hover {
            background: rgba(255,255,255,0.08);
            border-color: #ffc107;
            transform: translateY(-10px);
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div id="cursor-dot"></div>
    <div id="cursor-ring"></div>

    <header>
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="#">H-TRACK</a>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item active" data-tab="home"><a class="nav-link">Home</a></li>
                        <li class="nav-item" data-tab="guidelines"><a class="nav-link">Guidelines</a></li>
                        <li class="nav-item" data-tab="hostels"><a class="nav-link">Hostels</a></li>
                        <li class="nav-item" data-tab="logins"><a class="nav-link">Logins</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- TAB: HOME -->
    <div id="tab-home" class="tab-content active">
        <section class="hero-section">
            <div class="hero-slider" id="slider-home">
                <div class="hero-slide active" style="background-image: url('web_home/images/landing/img1.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/img2.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/img3.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/img4.jpg');"></div>
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-text">
                <h1>H-TRACK</h1>
                <p class="lead">Excellence in Hostel Management</p>
            </div>
            <div class="scroll-indicator" onclick="scrollToInfo()">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>
        <section class="info-section">
            <div class="container">
                <div class="glass-card text-center">
                    <h2 class="section-title">Welcome Home</h2>
                    <p class="lead mb-4">Official Portal of Dr. Manmohan Singh Earth Sciences University of Telangana.</p>
                    <p>Experience a new standard of living with H-Track. Our platform streamlines every aspect of hostel life, from secure registrations to real-time billing and maintenance requests. We are committed to providing a transparent and efficient ecosystem for our students and staff.</p>
                </div>
            </div>
        </section>
    </div>

    <!-- TAB: GUIDELINES -->
    <div id="tab-guidelines" class="tab-content">
        <section class="hero-section">
            <div class="hero-slider" id="slider-guide">
                <div class="hero-slide active" style="background-image: url('web_home/images/landing/guide1.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/guide2.jpg');"></div>
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-text">
                <h1>CAMPUS RULES</h1>
                <p class="lead">Safe & Disciplined Environment</p>
            </div>
            <div class="scroll-indicator" onclick="scrollToInfo()">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>
        <section class="info-section">
            <div class="container">
                <div class="glass-card">
                    <h2 class="section-title">University Guidelines</h2>
                    <div class="row">
                        <div class="col-12">
                            <h4 class="text-warning mb-4"><i class="fas fa-scroll mr-2"></i> Code of Conduct</h4>
                            <div class="lead">
                                <p>1. <strong>Identity:</strong> Carrying a University ID card is mandatory at all times.</p>
                                <p>2. <strong>Timings:</strong> Hostel in-time is strictly 9:00 PM. No exceptions without prior permission.</p>
                                <p>3. <strong>Anti-Ragging:</strong> Strict zero-tolerance policy. Expulsion is the immediate result of ragging.</p>
                                <p>4. <strong>Prohibitions:</strong> Alcohol, tobacco, and unauthorized gadgets are banned on campus.</p>
                                <p>5. <strong>Cleanliness:</strong> Maintain hygiene in your room and common areas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- TAB: HOSTELS -->
    <div id="tab-hostels" class="tab-content">
        <section class="hero-section">
            <div class="hero-slider" id="slider-hostel">
                <div class="hero-slide active" style="background-image: url('web_home/images/landing/hostel1.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/hostel2.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/hostel3.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/hostel4.jpg');"></div>
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-text">
                <h1>OUR HOSTELS</h1>
                <p class="lead">Modern Living Spaces</p>
            </div>
            <div class="scroll-indicator" onclick="scrollToInfo()">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>
        <section class="info-section">
            <div class="container">
                <h2 class="section-title">Explore Your Home</h2>
                <div class="row">
                    <div class="col-md-4 mb-4"><div class="grid-item"><h3>A-Hostel</h3><p>Old Boys Hostel</p></div></div>
                    <div class="col-md-4 mb-4"><div class="grid-item"><h3>B-Hostel</h3><p>New Boys Hostel</p></div></div>
                    <div class="col-md-4 mb-4"><div class="grid-item"><h3>C-Hostel</h3><p>Girls Hostel</p></div></div>
                    <div class="col-md-4 mb-4"><div class="grid-item"><h3>D-Hostel</h3><p>Degree Boys</p></div></div>
                    <div class="col-md-4 mb-4"><div class="grid-item"><h3>E-Hostel</h3><p>Degree Girls</p></div></div>
                    <div class="col-md-4 mb-4"><div class="grid-item"><h3>F-Hostel</h3><p>PG Hostel</p></div></div>
                </div>
            </div>
        </section>
    </div>

    <!-- TAB: LOGINS -->
    <div id="tab-logins" class="tab-content">
        <section class="hero-section">
            <div class="hero-slider" id="slider-login">
                <div class="hero-slide active" style="background-image: url('web_home/images/landing/img1.jpg');"></div>
                <div class="hero-slide" style="background-image: url('web_home/images/landing/img4.jpg');"></div>
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-text">
                <h1>PORTAL ACCESS</h1>
                <p class="lead">Connect with H-Track</p>
            </div>
            <div class="scroll-indicator" onclick="scrollToInfo()">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>
        <section class="info-section">
            <div class="container">
                <div class="glass-card" style="max-width: 800px;">
                    <h2 class="section-title">Sign In</h2>
                    <div class="row">
                        <div class="col-md-6 mb-4"><a href="student_login.php" class="grid-item d-block text-decoration-none text-white"><h3>Student</h3><p>Access your profile</p></a></div>
                        <div class="col-md-6 mb-4"><a href="login-hostel_manager.php" class="grid-item d-block text-decoration-none text-white"><h3>Admin/Manager</h3><p>Management portal</p></a></div>
                        <div class="col-12"><a href="apply_visitor.php" class="grid-item d-block text-decoration-none text-white"><h3>Visitor</h3><p>Request entry</p></a></div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="web_home/js/jquery-2.2.3.min.js"></script>
    <script>
        // Tab Switching Logic
        $(".nav-item").click(function() {
            const tab = $(this).data("tab");
            $(".tab-content").removeClass("active");
            $("#tab-" + tab).addClass("active");
            $(".nav-item").removeClass("active");
            $(this).addClass("active");
            window.scrollTo(0, 0);
        });

        function scrollToInfo() {
            const currentSection = $(".tab-content.active .info-section");
            $('html, body').animate({ scrollTop: currentSection.offset().top }, 800);
        }

        // Slider Logic
        function initSlider(id, interval) {
            let cur = 0;
            const slides = $("#" + id + " .hero-slide");
            setInterval(() => {
                slides.eq(cur).removeClass("active");
                cur = (cur + 1) % slides.length;
                slides.eq(cur).addClass("active");
            }, interval);
        }

        $(document).ready(function() {
            initSlider("slider-home", 5000);
            initSlider("slider-guide", 4000);
            initSlider("slider-hostel", 5000);
            initSlider("slider-login", 6000);
        });

        // Cursor Effects
        $(document).on("mousemove", function(e) {
            $("#cursor-dot").css({ left: e.clientX, top: e.clientY });
            setTimeout(() => {
                $("#cursor-ring").css({ left: e.clientX - 14, top: e.clientY - 14 });
            }, 50);
        });
    </script>
</body>
</html>
