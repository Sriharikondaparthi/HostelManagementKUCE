<?php 
require 'admin_header.php'; 

if (!isset($_GET['branch'])) {
    echo "<script>window.location.href='admin_home.php';</script>";
    exit();
}

$branch = mysqli_real_escape_string($conn, $_GET['branch']);
$semester = isset($_GET['semester']) ? mysqli_real_escape_string($conn, $_GET['semester']) : '';

if(!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="glass-card shadow-lg animated fadeIn">
    <div class="section-header px-4 py-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #00bcd4;">
        <h2 class="h4 mb-0 text-dark font-weight-bold">
            <i class="fas fa-university mr-2 text-info"></i> <?php echo h($branch); ?> Department 
            <?php if($semester) echo " - Semester ".h($semester); ?>
        </h2>
        <a href="admin_home.php" class="btn btn-outline-info btn-sm px-3 shadow-sm font-weight-bold"><i class="fas fa-arrow-left mr-1"></i> Dashboard</a>
    </div>
    
    <div class="section-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="background: #fff;">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="py-3 px-4">Serial</th>
                        <th class="py-3 px-4">Hall Ticket</th>
                        <th class="py-3 px-4">Full Student Name</th>
                        <th class="py-3 px-4">Course</th>
                        <th class="py-3 px-4 text-center">Semester</th>
                        <th class="py-3 px-4">Registry Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Using the central Student table
                    $sql = "SELECT * FROM Student WHERE Dept = '$branch'";
                    if ($semester != '') $sql .= " AND Semester = '$semester'";
                    $sql .= " ORDER BY Fname";
                    $res = mysqli_query($conn, $sql);
                    $i = 1;
                    if ($res && mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $reg_date = $row['Reg_Date'] ? date('M d, Y', strtotime($row['Reg_Date'])) : 'N/A';
                            echo "<tr>
                                    <td class='text-muted px-4 py-3'>".str_pad($i, 2, '0', STR_PAD_LEFT)."</td>
                                    <td class='font-weight-bold px-4 py-3 text-info'>".h($row['Student_id'])."</td>
                                    <td class='px-4 py-3'>".h($row['Fname'])."</td>
                                    <td class='px-4 py-3'><span class='badge badge-light border'>".h($row['Dept'])."</span></td>
                                    <td class='text-center px-4 py-3'><span class='badge badge-pill badge-light px-3 py-1 border'>Sem ".h($row['Semester'] ?? '1')."</span></td>
                                    <td class='small text-muted px-4 py-3'>".$reg_date."</td>
                                  </tr>";
                            $i++;
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center py-5 text-muted'>
                                <i class='fas fa-search fa-3x mb-3 text-muted opacity-2'></i><br>
                                No records matched this specific department filter in the central registry.
                              </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4 text-center">
    <p class="text-white-50 small"><i class="fas fa-print mr-1"></i> Data automatically synchronized with central registry (Student Table).</p>
</div>

<?php 
require 'admin_footer.php'; 
?>
