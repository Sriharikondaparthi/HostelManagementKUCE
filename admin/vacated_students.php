<?php
  require '../includes/config.inc.php';
  
  if(isset($_POST['clear_all'])){
      $conn->query("TRUNCATE TABLE Vacated_Students");
      echo "<script>alert('All vacated student records cleared successfully!'); window.location.href='vacated_students.php';</script>";
  }
  
  if(isset($_POST['clear_single'])){
      $id = (int)$_POST['record_id'];
      $conn->query("DELETE FROM Vacated_Students WHERE id = $id");
      echo "<script>alert('Record cleared successfully!'); window.location.href='vacated_students.php';</script>";
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title> Admin Archives | Vacated Students </title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"> 
	<link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" /> 
	<link rel="stylesheet" href="../web_home/css_home/fontawesome-all.css"> 
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet">
</head>

<body>

<div class="inner-page-banner" id="home"> 	   
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">
				<h1><a class="navbar-brand" href="admin_home.php"><span class="display">Admin Panel</span></a></h1>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item"><a class="nav-link" href="admin_home.php">Home</a></li>
						<li class="nav-item active"><a class="nav-link" href="vacated_students.php">Vacations Archive</a></li>
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							  Logout
							</a>
							<div class="dropdown-menu" aria-labelledby="navbarDropdown">
							  <a class="dropdown-item" href="../includes/logout.inc.php">Logout</a>
							</div>
						</li>
					</ul>
				</div>
			</nav>
		</div>
	</header>
</div>

<br><br>
<div class="container">
	<h2 class="heading text-capitalize mb-sm-5 mb-4"> Secure Historical Data Archive </h2>
    
    <div class="row">
        <div class="col-md-12 text-right mb-3">
            <form action="vacated_students.php" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete ALL history records?');">
                <button type="submit" name="clear_all" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Clear All Historical Data</button>
            </form>
        </div>
    </div>
    
    <table class="table table-bordered table-striped table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Record ID</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Hostel ID</th>
                <th>Room ID</th>
                <th>Join Date</th>
                <th>Vacated On</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $q = "SELECT * FROM Vacated_Students ORDER BY Vacate_date DESC";
            $res = mysqli_query($conn, $q);
            if(mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['Student_id']}</td>
                            <td>{$row['Fname']} {$row['Lname']}</td>
                            <td>{$row['Hostel_id']}</td>
                            <td>{$row['Room_id']}</td>
                            <td>{$row['Join_date']}</td>
                            <td>{$row['Vacate_date']}</td>
                            <td>
                                <form action='vacated_students.php' method='POST' style='margin:0;'>
                                    <input type='hidden' name='record_id' value='{$row['id']}'>
                                    <button type='submit' name='clear_single' class='btn btn-outline-danger btn-sm' onclick=\"return confirm('Clear this record forever?');\">Delete</button>
                                </form>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='8' class='text-center'>No vacated records archived yet.</td></tr>";
            }
        ?>
        </tbody>
    </table>
</div>

<br><br><br>

<footer class="py-5">
	<div class="container py-md-5">
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand" href="https://dresut.vercel.app/">ESUT<span class="display"></span></a>
		</div>
	</div>
</footer>

<script type="text/javascript" src="../web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="../web_home/js/bootstrap.js"></script>
</body>
</html>
