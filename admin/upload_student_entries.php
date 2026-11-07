<?php 
require 'admin_header.php'; 

$message = "";
$message_type = "";

if (isset($_POST['upload_csv'])) {
    if (isset($_FILES['student_csv']) && $_FILES['student_csv']['error'] == 0) {
        set_time_limit(0); 
        $filename = $_FILES['student_csv']['tmp_name'];
        $file = fopen($filename, "r");
        fgetcsv($file); // Skip header
        $count = 0;
        
        // Single Table logic: Using Student table exclusively
        $stmt_student = $conn->prepare("INSERT INTO Student (Student_id, Fname, Dept, Academic_Year, Semester, Pwd, profile_completed, Reg_Date) VALUES (?, ?, ?, ?, ?, ?, 0, NOW()) ON DUPLICATE KEY UPDATE Fname=VALUES(Fname), Dept=VALUES(Dept), Academic_Year=VALUES(Academic_Year), Semester=VALUES(Semester)");
        
        $current_course = "Unknown";
        while (($column = fgetcsv($file)) !== FALSE) {
            if (count($column) < 1) continue;
            
            $col0 = trim($column[0] ?? '');
            $col1 = trim($column[1] ?? '');
            $col2 = trim($column[2] ?? '');
            
            // Detect Course Section Header
            if (!empty($col0) && empty($col1) && empty($col2)) {
                $current_course = $col0;
                continue;
            }

            // Skip CSV Header/Label rows
            if (strtoupper($col0) === 'S.NO' || stripos($col0, 'HALL TICKET') !== false || strtoupper($col1) === 'HT NO') {
                continue;
            }

            $hall_ticket = '';
            $name = '';
            $course = '';
            $semester = '--'; // User specified semester should be strictly '--' initially since CSV provides no semester data

            // Determine if the CSV is the 5-column explicit format
            if (preg_match('/^[a-zA-Z0-9.-]{4,30}$/', $col1)) {
                $hall_ticket = strtoupper($col1);
                $name = $col2;
                
                // If col3 and col4 are definitively Year and Branch
                if (!empty($col3) && !empty($col4)) {
                    $academic_year = trim($col3);
                    $branch = trim($col4);
                } else {
                    // Fallback to older header-based regex if it's the old format
                    if (preg_match('/^([IVX]+(?:\/[IVX]+)?)(?:\s+|-)*(.*)$/i', trim($current_course), $matches)) {
                        $academic_year = explode('/', $matches[1])[0];
                        $branch = trim($matches[2]);
                    } else {
                        $academic_year = '-';
                        $branch = trim($current_course);
                    }
                }
            } else if (preg_match('/^[a-zA-Z0-9.-]{4,30}$/', $col0) && !empty($col1)) {
                $hall_ticket = strtoupper($col0);
                $name = $col1;
                // Try old regex parsing
                $course = !empty($col2) ? $col2 : $current_course;
                if (preg_match('/^([IVX]+(?:\/[IVX]+)?)(?:\s+|-)*(.*)$/i', trim($course), $matches)) {
                    $academic_year = explode('/', $matches[1])[0];
                    $branch = trim($matches[2]);
                } else {
                    $academic_year = '-';
                    $branch = trim($course);
                }
            } else {
                continue; // Skip unrecognizable or blank rows
            }
            
            if (!empty($hall_ticket)) {
                if (empty($branch)) {
                    $branch = 'UNKNOWN';
                }
                
                if (empty($branch)) {
                    $branch = 'UNKNOWN';
                }
                
                $final_semester = !empty($semester) ? $semester : '--';
                
                $hashed_pwd = password_hash($hall_ticket, PASSWORD_DEFAULT);
                $branch_normalized = strtoupper($branch);
                
                $stmt_student->bind_param("ssssss", $hall_ticket, $name, $branch_normalized, $academic_year, $final_semester, $hashed_pwd);
                $stmt_student->execute();
                
                $count++;
                if ($count % 100 == 0) $conn->ping();
            }
        }
        $stmt_student->close(); 
        fclose($file);
        $message = "Successfully processed $count student accounts into the central registry."; 
        $message_type = "success";
    }
}

if (isset($_POST['clear_entries'])) {
    // Safety check: Only delete students who have not been allocated rooms (i.e. entries from the registry that haven't become residents)
    if (mysqli_query($conn, "DELETE FROM Student WHERE Room_id IS NULL AND Hostel_id IS NULL")) { 
        $message = "Registry cleared. Active hostel residents were preserved."; 
        $message_type = "warning"; 
    } else {
        $message = "Error clearing registry: " . mysqli_error($conn);
        $message_type = "danger";
    }
}

if (isset($_POST['advanced_clear'])) {
    $filter_branch = isset($_POST['filter_branch']) ? trim($_POST['filter_branch']) : '';
    $filter_semester = isset($_POST['filter_semester']) ? trim($_POST['filter_semester']) : '';
    $filter_id = isset($_POST['filter_id']) ? trim($_POST['filter_id']) : '';

    $where_clauses = [];
    $params = [];
    $types = "";

    if (!empty($filter_id)) {
        $where_clauses[] = "Student_id = ?";
        $params[] = $filter_id;
        $types .= "s";
    } else {
        if (!empty($filter_branch)) {
            $where_clauses[] = "Dept = ?";
            $params[] = strtoupper($filter_branch);
            $types .= "s";
        }
        if (!empty($filter_semester)) {
            $where_clauses[] = "Academic_Year = ?";
            $params[] = $filter_semester;
            $types .= "s";
        }
    }

    if (empty($where_clauses)) {
        $message = "Please select at least one filter for advanced deletion.";
        $message_type = "warning";
    } else {
        $where_sql = implode(" AND ", $where_clauses);
        
        // Find all students matching to properly vacate them first
        $find_stmt = $conn->prepare("SELECT Student_id, Fname, Lname, Join_date, Hostel_id, Room_id FROM Student WHERE " . $where_sql);
        if ($types != "") {
             $find_stmt->bind_param($types, ...$params);
        }
        $find_stmt->execute();
        $res = $find_stmt->get_result();
        
        $deleted_count = 0;
        $vacated_count = 0;
        
        while ($s_row = $res->fetch_assoc()) {
            $sid = $s_row['Student_id'];
            
            // If they are in a room, vacate them
            if (!empty($s_row['Room_id']) && !empty($s_row['Hostel_id'])) {
                $rid = $s_row['Room_id'];
                $hid = $s_row['Hostel_id'];
                $jdate = empty($s_row['Join_date']) ? date('Y-m-d') : $s_row['Join_date'];
                
                // Decrement Room allocation
                $conn->query("UPDATE Room SET Allocated = GREATEST(0, Allocated - 1) WHERE Room_id = '$rid'");
                
                // Archive into Vacated_Students
                $v_stmt = $conn->prepare("INSERT INTO Vacated_Students (Student_id, Fname, Lname, Join_date, Vacate_date, Hostel_id, Room_id) VALUES (?, ?, ?, ?, CURRENT_DATE, ?, ?)");
                $v_stmt->bind_param("ssssss", $sid, $s_row['Fname'], $s_row['Lname'], $jdate, $hid, $rid);
                $v_stmt->execute();
                
                $vacated_count++;
            }
            
            // Delete related applications
            $conn->query("DELETE FROM Application WHERE Student_id = '$sid'");
            
            // Finally, delete the student
            $conn->query("DELETE FROM Student WHERE Student_id = '$sid'");
            $deleted_count++;
        }
        
        $message = "Successfully deleted $deleted_count student(s). ($vacated_count were forcefully vacated from their active rooms).";
        $message_type = "success";
    }
}
?>

<div class="glass-module shadow-lg animated fadeIn">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h5 mb-0 text-dark font-weight-bold"><i class="fas fa-file-import mr-2 text-info"></i> Central Student Enrollment</h2>
        <span class="badge badge-info p-2 px-3 shadow-sm">Single Table Persistence</span>
    </div>
    
    <div class="section-body p-4">
        <?php if ($message != ""): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4 shadow-sm border-<?php echo $message_type; ?>">
                <i class="fas fa-info-circle mr-2"></i> <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="p-4 rounded border h-100" style="background: rgba(0,0,0,0.02);">
                    <form action="upload_student_entries.php" method="post" enctype="multipart/form-data">
                        <label class="font-weight-bold text-muted text-uppercase small mb-4 d-block">Select Source File (.CSV)</label>
                        <div class="custom-file mb-5">
                            <input type="file" class="custom-file-input" name="student_csv" accept=".csv" required id="csvUploadInput">
                            <label class="custom-file-label font-weight-bold" for="csvUploadInput">Choose student data file...</label>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <button type="submit" name="upload_csv" class="btn btn-info btn-lg px-5 font-weight-bold shadow-sm">
                                <i class="fas fa-play-circle mr-2"></i> START SYSTEM IMPORT
                            </button>
                            <button type="submit" name="clear_entries" class="btn btn-outline-danger btn-lg px-4 ml-3" onclick="return confirm('Note: This will delete ALL students who are NOT yet assigned to a hostel. Proceed?')">
                                <i class="fas fa-trash"></i> Clear Unallocated
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Advanced Deletion Filters -->
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="glass-module p-4 border rounded" style="background: rgba(220, 53, 69, 0.05); border-left: 4px solid #dc3545 !important;">
                    <h5 class="text-danger font-weight-bold mb-3"><i class="fas fa-exclamation-triangle mr-2"></i> Advanced Force Deletion</h5>
                    <p class="text-muted small mb-4">Use these filters to systematically delete and vacate students. <strong>Warning: If an affected student is currently assigned to a room, they will be forcefully vacated and their account permanently deleted.</strong></p>
                    
                    <form action="upload_student_entries.php" method="post" id="advancedClearForm">
                        <div class="form-row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label class="small font-weight-bold text-muted">Filter by Branch</label>
                                <select class="form-control bg-light" name="filter_branch" id="filter_branch">
                                    <option value="">Any Branch</option>
                                    <?php
                                    $branches_sql = "SELECT DISTINCT Dept FROM Student WHERE Dept IS NOT NULL AND Dept != '' ORDER BY Dept";
                                    $b_res = mysqli_query($conn, $branches_sql);
                                    while($b_row = mysqli_fetch_assoc($b_res)) {
                                        echo "<option value='".htmlspecialchars($b_row['Dept'])."'>".htmlspecialchars($b_row['Dept'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="small font-weight-bold text-muted">Filter by Academic Year</label>
                                <select class="form-control bg-light" name="filter_semester" id="filter_semester">
                                    <option value="">Any Year</option>
                                    <?php
                                    $sem_sql = "SELECT DISTINCT Academic_Year FROM Student WHERE Academic_Year IS NOT NULL AND Academic_Year != '' AND Academic_Year != '-' ORDER BY Academic_Year";
                                    $s_res = mysqli_query($conn, $sem_sql);
                                    while($s_row = mysqli_fetch_assoc($s_res)) {
                                        echo "<option value='".htmlspecialchars($s_row['Academic_Year'])."'>Year ".htmlspecialchars($s_row['Academic_Year'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-1 mb-3 text-center">
                                <span class="text-muted font-weight-bold">OR</span>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="small font-weight-bold text-muted">Individual Hall Ticket</label>
                                <input type="text" class="form-control bg-light" name="filter_id" id="filter_id" placeholder="e.g. HT001">
                            </div>
                            <div class="col-md-2 mb-3">
                                <button type="submit" name="advanced_clear" class="btn btn-danger btn-block font-weight-bold shadow-sm" onclick="return confirmAdvancedDeletion();">
                                    <i class="fas fa-fire mr-1"></i> DELETE
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function confirmAdvancedDeletion() {
            var branch = document.getElementById('filter_branch').value;
            var sem = document.getElementById('filter_semester').value;
            var id = document.getElementById('filter_id').value;
            
            if (!branch && !sem && !id) {
                alert("Please select at least one filter before deleting.");
                return false;
            }
            
            return confirm("CAUTION! You are about to FORCE DELETE students matching these criteria. Any matching students living in a room will be automatically vacated. This action cannot be undone. Are you absolutely sure?");
        }
        </script>

        <hr class="my-5 opacity-2">

        <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-search text-muted mr-2"></i> Registry Search & Filter</h5>
        <div class="glass-module p-3 mb-4 border rounded shadow-sm bg-light">
            <form action="upload_student_entries.php" method="GET" id="searchForm">
                <div class="form-row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold text-muted">Hall Ticket Number</label>
                        <input type="text" class="form-control" name="search_roll" value="<?php echo htmlspecialchars($_GET['search_roll'] ?? ''); ?>" placeholder="Enter Roll Number...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold text-muted">Academic Year</label>
                        <select class="form-control" name="search_year">
                            <option value="">All Years</option>
                            <?php
                            $y_sql = "SELECT DISTINCT Academic_Year FROM Student WHERE Academic_Year IS NOT NULL AND Academic_Year != '' AND Academic_Year != '-' ORDER BY Academic_Year";
                            $y_res = mysqli_query($conn, $y_sql);
                            while($y_row = mysqli_fetch_assoc($y_res)) {
                                $sel = (isset($_GET['search_year']) && $_GET['search_year'] == $y_row['Academic_Year']) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($y_row['Academic_Year'])."' $sel>Year ".htmlspecialchars($y_row['Academic_Year'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="small font-weight-bold text-muted">Branch / Dept</label>
                        <div class="d-flex">
                            <select class="form-control mr-3" name="search_branch">
                                <option value="">All Branches</option>
                                <?php
                                $b_sql = "SELECT DISTINCT Dept FROM Student WHERE Dept IS NOT NULL AND Dept != '' ORDER BY Dept";
                                $b_res = mysqli_query($conn, $b_sql);
                                while($b_row = mysqli_fetch_assoc($b_res)) {
                                    $sel = (isset($_GET['search_branch']) && $_GET['search_branch'] == $b_row['Dept']) ? 'selected' : '';
                                    echo "<option value='".htmlspecialchars($b_row['Dept'])."' $sel>".htmlspecialchars($b_row['Dept'])."</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" class="btn btn-info font-weight-bold shadow-sm px-4">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <a href="upload_student_entries.php" class="btn btn-outline-secondary ml-2">Clear</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <h5 class="font-weight-bold text-dark mb-4"><i class="fas fa-database text-muted mr-2"></i> System Account Preview</h5>
        <div class="table-responsive border rounded shadow-sm" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr><th>S.No</th><th>Hall Ticket Number</th><th>Student Name</th><th class="text-center">Year</th><th>Branch</th><th class="text-center">Sem</th><th>Status</th></tr>
                </thead>
                <tbody style="background:#fff;">
                    <?php
                    $where_conditions = [];
                    $q_params = [];
                    $q_types = "";
                    
                    if (!empty($_GET['search_roll'])) {
                        $search_val = "%" . trim($_GET['search_roll']) . "%";
                        $where_conditions[] = "Student_id LIKE ?";
                        $q_params[] = $search_val;
                        $q_types .= "s";
                    }
                    if (!empty($_GET['search_year'])) {
                        $where_conditions[] = "Academic_Year = ?";
                        $q_params[] = $_GET['search_year'];
                        $q_types .= "s";
                    }
                    if (!empty($_GET['search_branch'])) {
                        $where_conditions[] = "Dept = ?";
                        $q_params[] = $_GET['search_branch'];
                        $q_types .= "s";
                    }
                    
                    $sql_query = "SELECT * FROM Student";
                    if (!empty($where_conditions)) {
                        $sql_query .= " WHERE " . implode(" AND ", $where_conditions);
                    }
                    // Sort by internal row insertion ID to physically match CSV order
                    $sql_query .= " ORDER BY internal_id ASC";
                    
                    if (!empty($q_params)) {
                        $stmt_preview = $conn->prepare($sql_query);
                        $stmt_preview->bind_param($q_types, ...$q_params);
                        $stmt_preview->execute();
                        $res = $stmt_preview->get_result();
                    } else {
                        $res = mysqli_query($conn, $sql_query);
                    }
                    
                    if (mysqli_num_rows($res) > 0) {
                        $sno = 1;
                        while ($row = mysqli_fetch_assoc($res)) {
                            $status = ($row['Hostel_id']) ? "<span class='badge badge-success'>Resident</span>" : "<span class='badge badge-light border'>Unallocated</span>";
                            echo "<tr>
                                    <td class='font-weight-bold text-muted'>{$sno}</td>
                                    <td class='font-weight-bold text-info'>{$row['Student_id']}</td>
                                    <td class='uppercase'>{$row['Fname']}</td>
                                    <td class='text-center font-weight-bold'>{$row['Academic_Year']}</td>
                                    <td><span class='small font-weight-bold text-secondary'>{$row['Dept']}</span></td>
                                    <td class='text-center'>{$row['Semester']}</td>
                                    <td>$status</td>
                                  </tr>";
                            $sno++;
                        }
                    } else echo "<tr><td colspan='5' class='text-center py-5 text-muted'>System empty.</td></tr>";
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('csvUploadInput').onchange = function() {
        var fn = this.value.split('\\').pop();
        this.nextElementSibling.innerHTML = fn;
    };
</script>

<?php 
require 'admin_footer.php'; 
?>
