<?php 
require 'admin_header.php'; 

// Academic Structure Configuration
$academic_groups = [
    'Engineering Programs' => [
        'branches' => ['CSE', 'IT', 'EEE', 'ECE', 'MIN'],
        'semesters' => 8
    ],
    'BSc Programs' => [
        'branches' => ['BSc(Geology)', 'BSc(ENS)'],
        'semesters' => 6
    ],
    'MSc Programs' => [
        'branches' => ['MSc(Geology)', 'MSc(ENS)'],
        'semesters' => 4
    ],
    'Other Programs' => [
        'branches' => ['MTech', 'MBA'],
        'semesters' => 4
    ]
];

// Helper to determine max semesters for a branch
function getMaxSemesters($branch, $groups) {
    $clean_search = strtolower(str_replace(' ', '', $branch));
    foreach ($groups as $title => $info) {
        foreach ($info['branches'] as $b) {
            if (strtolower(str_replace(' ', '', $b)) === $clean_search) {
                return $info['semesters'];
            }
        }
    }
    return 8; // Default
}

// Student Count Helper: Using the Student table exclusively with robust matching
function getStudentCount($conn, $branch, $semester = null) {
    $clean_branch = strtolower(str_replace(' ', '', $branch));
    if ($semester === null) {
        $q = "SELECT COUNT(*) as cnt FROM Student WHERE LOWER(REPLACE(Dept, ' ', '')) = ?";
        $stmt = $conn->prepare($q);
        $stmt->bind_param("s", $clean_branch);
    } else {
        $q = "SELECT COUNT(*) as cnt FROM Student WHERE LOWER(REPLACE(Dept, ' ', '')) = ? AND Semester = ?";
        $stmt = $conn->prepare($q);
        $stmt->bind_param("ss", $clean_branch, $semester);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return $row['cnt'] ?? 0;
}

// Stats fetching
function getCount($conn, $table) {
    $q = "SELECT COUNT(*) as count FROM $table";
    $res = mysqli_query($conn, $q);
    return ($res && $row = mysqli_fetch_assoc($res)) ? $row['count'] : 0;
}
$count_students = getCount($conn, 'Student');
$count_hostels = getCount($conn, 'Hostel');
$count_managers = getCount($conn, 'Hostel_Manager');
$count_rooms = getCount($conn, 'Room');

$selected_branch = $_GET['branch'] ?? 'CSE';
$selected_semester = $_GET['semester'] ?? '';
$max_sems = getMaxSemesters($selected_branch, $academic_groups);
?>

<!-- Functional Workplace: Course Center -->
<div class="row mb-5">
    <div class="col-lg-9">
        <div class="glass-module p-4 shadow-lg animated fadeInDown">
            <h4 class="font-weight-bold mb-4 text-info"><i class="fas fa-university mr-2"></i> Academic Registry & Filters</h4>
            
            <?php foreach($academic_groups as $title => $data): ?>
            <div class="mb-4">
                <h6 class="text-muted small text-uppercase font-weight-bold mb-3" style="letter-spacing: 1px;">
                    <i class="fas fa-caret-right mr-1"></i> <?php echo $title; ?> (<?php echo $data['semesters']; ?> Sems)
                </h6>
                <div class="d-flex flex-wrap p-2 rounded" style="background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05);">
                    <?php foreach($data['branches'] as $b): 
                        $active = ($b == $selected_branch);
                        $b_count = getStudentCount($conn, $b);
                    ?>
                    <a href="admin_home.php?branch=<?php echo urlencode($b); ?>" 
                       class="btn <?php echo $active ? 'btn-info shadow' : 'btn-outline-info'; ?> mr-2 mb-2 px-3 py-2 font-weight-bold d-flex justify-content-between align-items-center" 
                       style="min-width: 180px; border-width: 2px;">
                        <span><?php echo $b; ?></span>
                        <span class="badge <?php echo $active ? 'badge-light text-info' : 'badge-info'; ?> ml-2"><?php echo $b_count; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="mt-4 pt-4 border-top">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted small text-uppercase font-weight-bold mb-0">Select Term / Semester for <?php echo htmlspecialchars($selected_branch); ?></h6>
                    <span class="badge badge-light border px-3">Max Limit: <?php echo $max_sems; ?> Semesters</span>
                </div>
                <div class="btn-group btn-group-lg d-flex shadow-sm">
                    <?php $global_count = getStudentCount($conn, $selected_branch); ?>
                    <a href="admin_home.php?branch=<?php echo urlencode($selected_branch); ?>" 
                       class="btn btn-light border flex-fill <?php if($selected_semester == '') echo 'active bg-secondary text-white'; ?>">
                       Global View <span class="badge badge-pill badge-dark ml-1"><?php echo $global_count; ?></span>
                    </a>
                    <?php for($i=1; $i<=$max_sems; $i++): 
                        $sem_count = getStudentCount($conn, $selected_branch, $i);
                    ?>
                        <a href="admin_home.php?branch=<?php echo urlencode($selected_branch); ?>&semester=<?php echo $i; ?>" 
                           class="btn btn-light border flex-fill <?php if($selected_semester == (string)$i) echo 'active bg-info text-white'; ?>">
                           Sem <?php echo $i; ?> <span class="badge badge-pill <?php echo $selected_semester == (string)$i ? 'badge-light text-info' : 'badge-secondary'; ?> ml-1"><?php echo $sem_count; ?></span>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="glass-module p-4 shadow-lg h-100 animated fadeInRight" style="background: rgba(255,255,255,0.9);">
            <h5 class="font-weight-bold mb-4 border-bottom pb-2"><i class="fas fa-chart-line text-info mr-2"></i> System Stats</h5>
            <div class="mb-4">
                <div class="small text-muted font-weight-bold text-uppercase xsmall">Enrolled Students</div>
                <div class="h4 font-weight-bold text-dark"><?php echo $count_students; ?></div>
            </div>
            <div class="mb-4">
                <div class="small text-muted font-weight-bold text-uppercase xsmall">Active Hostels</div>
                <div class="h4 font-weight-bold text-success"><?php echo $count_hostels; ?></div>
            </div>
            <div class="mb-4">
                <div class="small text-muted font-weight-bold text-uppercase xsmall">Office Managers</div>
                <div class="h4 font-weight-bold text-warning"><?php echo $count_managers; ?></div>
            </div>
            <div>
                <div class="small text-muted font-weight-bold text-uppercase xsmall">Housing Units</div>
                <div class="h4 font-weight-bold text-danger"><?php echo $count_rooms; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Student Registry Table -->
<div class="glass-module shadow-lg animated fadeInUp mb-5">
    <div class="px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h4 class="h6 mb-0 text-dark font-weight-bold">
            <i class="fas fa-address-book text-info mr-2"></i> 
            Student Database Preview: <?php echo htmlspecialchars($selected_branch); ?> 
            <?php if($selected_semester) echo " (Semester ".htmlspecialchars($selected_semester).")"; ?>
        </h4>
        <span class="badge badge-info p-2 px-3 shadow-sm"><?php echo htmlspecialchars($selected_branch); ?></span>
    </div>
    
    <div class="p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="py-3 px-4">Hall Ticket</th>
                        <th class="py-3 px-4">Student Name</th>
                        <th class="py-3 px-4">Course</th>
                        <th class="py-3 px-4 text-center">Semester</th>
                        <th class="py-3 px-4">Reg. Date</th>
                    </tr>
                </thead>
                <tbody style="background: #fff;">
                    <?php 
                    $clean_branch = strtolower(str_replace(' ', '', $selected_branch));
                    $q = "SELECT * FROM Student WHERE LOWER(REPLACE(Dept, ' ', '')) = ?";
                    if ($selected_semester) $q .= " AND Semester = ?";
                    $q .= " ORDER BY Fname";
                    
                    $stmt = $conn->prepare($q);
                    if ($selected_semester) {
                        $stmt->bind_param("ss", $clean_branch, $selected_semester);
                    } else {
                        $stmt->bind_param("s", $clean_branch);
                    }
                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            $reg_date = $row['Reg_Date'] ? date('M d, Y', strtotime($row['Reg_Date'])) : 'N/A';
                            echo "<tr>
                                    <td class='font-weight-bold px-4 py-3 text-info'>".htmlspecialchars($row['Student_id'])."</td>
                                    <td class='px-4 py-3'>".htmlspecialchars($row['Fname'])."</td>
                                    <td class='px-4 py-3'><span class='text-muted small font-weight-bold'>".htmlspecialchars($row['Dept'])."</span></td>
                                    <td class='text-center px-4 py-3'><span class='badge badge-pill badge-light border'>Sem ".htmlspecialchars($row['Semester'] ?? '1')."</span></td>
                                    <td class='text-muted xsmall px-4 py-3'>".$reg_date."</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-5 text-muted'>
                                <i class='fas fa-search fa-3x mb-3 opacity-2'></i><br>
                                No matching records for this course and semester combination (0 Students Found).
                              </td></tr>";
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
