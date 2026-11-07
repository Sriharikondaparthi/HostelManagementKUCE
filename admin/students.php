<?php 
require 'admin_header.php'; 

// helper to safely echo values 
if(!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

// Global Vacate Action by Admin
if(isset($_POST['vacate_student'])) {
    $hall_ticket = mysqli_real_escape_string($conn, $_POST['student_id']);
    
    $query = "SELECT s.Fname, s.Lname, s.Hostel_id, s.Room_id, s.Join_date FROM Student s WHERE s.Student_id = '$hall_ticket'";
    $result = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result)) {
        $v_hostel_id = $row['Hostel_id'];
        $v_room_id = $row['Room_id'];
        
        if($v_hostel_id && $v_room_id) {
            $fname = $row['Fname'];
            $lname = $row['Lname'];
            $join_date = $row['Join_date'] ? $row['Join_date'] : date('Y-m-d');
            $vacate_date = date('Y-m-d');
            
            $ins_arch = $conn->prepare("INSERT INTO Vacated_Students (Student_id, Fname, Lname, Hostel_id, Room_id, Join_date, Vacate_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins_arch->bind_param("sssiiss", $hall_ticket, $fname, $lname, $v_hostel_id, $v_room_id, $join_date, $vacate_date);
            $ins_arch->execute();
            
            $upd_stu = $conn->prepare("UPDATE Student SET Hostel_id = NULL, Room_id = NULL, Join_date = NULL WHERE Student_id = ?");
            $upd_stu->bind_param("s", $hall_ticket);
            $upd_stu->execute();
            
            $upd_room = $conn->prepare("UPDATE Room SET Allocated = '0' WHERE Room_id = ?");
            $upd_room->bind_param("i", $v_room_id);
            $upd_room->execute();
            
            $upd_hostel = $conn->prepare("UPDATE Hostel SET current_no_of_rooms = current_no_of_rooms + 1, No_of_students = No_of_students - 1 WHERE Hostel_id = ?");
            $upd_hostel->bind_param("i", $v_hostel_id);
            $upd_hostel->execute();
            
            $del_app = $conn->prepare("DELETE FROM Application WHERE Student_id = ?");
            $del_app->bind_param("s", $hall_ticket);
            $del_app->execute();
            
            echo "<script>alert('Student successfully vacated!'); window.location.href='students.php';</script>";
            exit();
        }
    }
}

// Global Delete Action
if(isset($_POST['delete_student'])) {
    $hall_ticket = mysqli_real_escape_string($conn, $_POST['student_id']);
    mysqli_query($conn, "DELETE FROM Student WHERE Student_id = '$hall_ticket'");
    echo "<script>alert('Student account permanently deleted.'); window.location.href='students.php';</script>";
    exit();
}
?>

<div class="glass-card shadow-lg animated fadeIn">
    <div class="section-header px-4 py-3 bg-light" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h4 mb-0 text-dark font-weight-bold"><i class="fas fa-users mr-2 text-info"></i> Student Management</h2>
    </div>
    
    <div class="section-body p-4">
        <!-- Search Section -->
        <div class="mb-5 p-4 rounded" style="background: rgba(0,0,0,0.03); border: 1px solid #eee;">
            <form action="students.php" method="post" class="form-row">
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-lg" placeholder="Search by Student ID / Hall Ticket Number" name="search_box" value="<?php echo isset($_POST['search_box']) ? h($_POST['search_box']) : ''; ?>">
                </div>
                <div class="col-md-3 mt-3 mt-md-0">
                    <button type="submit" class="btn btn-info btn-lg btn-block font-weight-bold" name="search"><i class="fas fa-search mr-1"></i> Search</button>
                </div>
            </form>
        </div>

        <?php
        if (isset($_POST['search'])) {
            $search_box = trim($_POST['search_box'] ?? '');
            $sql_search = "SELECT S.*, R.Room_No, H.Hostel_name FROM Student S LEFT JOIN Room R ON S.Room_id = R.Room_id LEFT JOIN Hostel H ON S.Hostel_id = H.Hostel_id WHERE S.Student_id LIKE ? ORDER BY S.Fname LIMIT 50";
            $stmt = mysqli_prepare($conn, $sql_search);
            $param = $search_box === '' ? '%' : $search_box . '%';
            mysqli_stmt_bind_param($stmt, "s", $param);
            mysqli_stmt_execute($stmt);
            $result_search = mysqli_stmt_get_result($stmt);
            ?>
            <div class="table-responsive mb-5 border rounded overflow-hidden shadow-sm">
                <table class="table table-hover mb-0">
                    <thead class="bg-dark text-white">
                        <tr><th>Name</th><th>ID</th><th>Contact</th><th>Hostel</th><th>Room</th><th>Action</th></tr>
                    </thead>
                    <tbody style="background: #fff;">
                    <?php
                    if (mysqli_num_rows($result_search) === 0) echo '<tr><td colspan="6" class="text-center py-4">No results found.</td></tr>';
                    else {
                        while ($row = mysqli_fetch_assoc($result_search)) {
                            $is_allocated = ($row['Hostel_id'] && $row['Room_id']);
                            echo "<tr>
                                    <td class='font-weight-bold'>".h($row['Fname']." ".$row['Lname'])."</td>
                                    <td>".h($row['Student_id'])."</td>
                                    <td>".h($row['Mob_no'])."</td>
                                    <td>".h($row['Hostel_name'] ?? 'None')."</td>
                                    <td>".h($row['Room_No'] ?? 'None')."</td>
                                    <td>";
                            if($is_allocated) echo "<form action='students.php' method='POST' class='m-0'><input type='hidden' name='student_id' value='".h($row['Student_id'])."'><button type='submit' name='vacate_student' class='btn btn-outline-danger btn-sm'>Vacate</button></form>";
                            else echo "<span class='badge badge-light'>Unallocated</span>";
                            echo "</td></tr>";
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <!-- List Rooms Allotted -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h5 text-secondary font-weight-bold mb-0">Currently Allotted Rooms</h4>
            <span class="badge badge-pill badge-light border">Live Allocation List</span>
        </div>
        <div class="table-responsive border rounded overflow-hidden shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                     <tr><th>Name</th><th>ID</th><th>Hostel</th><th>Room</th><th>Action</th></tr>
                </thead>
                <tbody style="background: #fff;">
                <?php
                if ($stmt = mysqli_prepare($conn, "SELECT S.*, R.Room_No, H.Hostel_name FROM Student S LEFT JOIN Room R ON S.Room_id = R.Room_id LEFT JOIN Hostel H ON S.Hostel_id = H.Hostel_id WHERE S.Hostel_id IS NOT NULL ORDER BY S.Fname LIMIT 100")) {
                    mysqli_stmt_execute($stmt);
                    $res1 = mysqli_stmt_get_result($stmt);
                    while ($row1 = mysqli_fetch_assoc($res1)) {
                        echo "<tr>
                                <td>".h($row1['Fname']." ".$row1['Lname'])."</td>
                                <td class='font-weight-bold'>".h($row1['Student_id'])."</td>
                                <td><span class='badge badge-light border'>".h($row1['Hostel_name'])."</span></td>
                                <td>".h($row1['Room_No'])."</td>
                                <td><form action='students.php' method='POST' class='m-0'><input type='hidden' name='student_id' value='".h($row1['Student_id'])."'><button type='submit' name='vacate_student' class='btn btn-danger btn-sm px-3'>Vacate</button></form></td>
                              </tr>";
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require 'admin_footer.php'; 
?>
