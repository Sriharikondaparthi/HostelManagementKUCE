<?php 
require 'admin_header.php'; 
?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="glass-card shadow-lg h-100 animated fadeInLeft">
            <div class="section-header bg-info text-white px-4 py-3">
                <h3 class="h5 mb-0 font-weight-bold"><i class="fas fa-user-plus mr-2"></i> Appoint Manager</h3>
            </div>
            <div class="section-body p-4">
                <form action="../includes/hm_signup.php" method="POST">
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-muted text-uppercase">Username</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fas fa-at text-info"></i></span></div>
                            <input type="text" name="hm_uname" class="form-control border-left-0" placeholder="Unique username" required>
                        </div>
                    </div>
                    <div class="form-row mb-3">
                        <div class="col">
                            <label class="small font-weight-bold text-muted text-uppercase">First Name</label>
                            <input type="text" name="hm_fname" class="form-control" placeholder="Jane" required>
                        </div>
                        <div class="col">
                            <label class="small font-weight-bold text-muted text-uppercase">Last Name</label>
                            <input type="text" name="hm_lname" class="form-control" placeholder="Doe" required>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-muted text-uppercase">Assign to Hostel</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fas fa-building text-info"></i></span></div>
                            <input type="text" name="hostel_name" class="form-control border-left-0" placeholder="Hostel Name" required>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-muted text-uppercase">Official Email</label>
                        <input type="email" name="Email" class="form-control" placeholder="manager@university.edu" required>
                    </div>
                    <div class="form-row mb-4">
                        <div class="col">
                            <label class="small font-weight-bold text-muted text-uppercase">Access Password</label>
                            <input type="password" name="pass" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col">
                            <label class="small font-weight-bold text-muted text-uppercase">Confirm Password</label>
                            <input type="password" name="confpass" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" name="hm_signup_submit" class="btn btn-info btn-block py-3 font-weight-bold shadow-sm">
                        <i class="fas fa-check-circle mr-2"></i> CONFIRM APPOINTMENT
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="glass-card shadow-lg h-100 animated fadeInRight">
            <div class="section-header bg-danger text-white px-4 py-3">
                <h3 class="h5 mb-0 font-weight-bold"><i class="fas fa-user-minus mr-2"></i> Remove Manager</h3>
            </div>
            <div class="section-body p-4">
                <form action="../includes/hm_remove.php" method="POST">
                    <div class="alert alert-warning small border-warning">
                        <i class="fas fa-shield-alt mr-1 text-warning"></i> <strong>Critical Action:</strong> This will revoke all manager access and unassign them from their hostel immediately.
                    </div>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-muted text-uppercase">Manager Username</label>
                        <input type="text" name="hm_uname" class="form-control" placeholder="Enter username correctly" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-muted text-uppercase">Confirm Hostel Name</label>
                        <input type="text" name="hostel_name" class="form-control" placeholder="Hostel assignment" required>
                    </div>
                    <div class="form-group mb-4">
                        <label class="small font-weight-bold text-danger text-uppercase">Admin Security Password</label>
                        <input type="password" name="pass" class="form-control border-danger" placeholder="Confirm with your password" required>
                    </div>
                    <button type="submit" name="hm_remove_submit" class="btn btn-danger btn-block py-3 font-weight-bold shadow-sm">
                        <i class="fas fa-trash-alt mr-2"></i> AUTHORIZE REMOVAL
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
require 'admin_footer.php'; 
?>
