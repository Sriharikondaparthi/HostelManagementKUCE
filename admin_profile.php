<?php 
require 'admin_header.php'; 
?>

<style>
    .profile-tabs .nav-link {
        border: none;
        padding: 15px 25px;
        color: #666;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 1px;
        transition: 0.3s;
        border-bottom: 3px solid transparent;
    }
    .profile-tabs .nav-link.active {
        background: transparent !important;
        color: #00bcd4 !important;
        border-bottom: 3px solid #00bcd4;
    }
    .profile-tabs .nav-link:hover {
        color: #00bcd4;
    }
    .profile-card {
        border-radius: 20px;
        background: #fff;
        border: none;
    }
    .stat-box {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        border: 1px solid #eee;
        transition: 0.3s;
    }
    .stat-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
</style>

<div class="glass-module shadow-lg animated fadeIn">
    <div class="bg-light px-4 pt-4 border-bottom">
        <div class="d-flex align-items-center mb-4">
            <div class="p-3 bg-info rounded-circle text-white shadow-sm mr-4">
                <i class="fas fa-user-shield fa-2x"></i>
            </div>
            <div>
                <h2 class="h4 mb-1 font-weight-bold text-dark">Admin Settings Hub</h2>
                <p class="text-muted mb-0 small uppercase font-weight-bold" style="letter-spacing: 1px;">Manage your identity and account security</p>
            </div>
        </div>

        <ul class="nav nav-tabs profile-tabs border-0" id="profileTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab"><i class="fas fa-id-card mr-2"></i> My Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="security-tab" data-toggle="tab" href="#security" role="tab"><i class="fas fa-key mr-2"></i> Security Updates</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" id="logout-tab" data-toggle="tab" href="#logout" role="tab"><i class="fas fa-power-off mr-2"></i> System Exit</a>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="myTabContent">
        <!-- TAB 1: PROFILE INFO -->
        <div class="tab-pane fade show active p-5" id="info" role="tabpanel">
            <div class="row align-items-center">
                <div class="col-md-4 text-center mb-4 mb-md-0 border-right">
                    <div class="mb-4 d-inline-block p-4 rounded-circle bg-light shadow-sm" style="border: 2px solid #00bcd4;">
                        <i class="fas fa-user-secret fa-4x text-info"></i>
                    </div>
                    <h3 class="h4 font-weight-bold mb-1 text-dark"><?php echo htmlspecialchars($_SESSION['fname']." ".$_SESSION['lname']); ?></h3>
                    <span class="badge badge-pill badge-info px-3 py-2 text-uppercase font-weight-bold">System Administrator</span>
                </div>
                <div class="col-md-8 px-md-5">
                    <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2">Administrative Identity</h5>
                    <div class="row">
                        <div class="col-sm-6 mb-4">
                            <div class="stat-box">
                                <label class="small text-muted font-weight-bold uppercase mb-1">Username</label>
                                <div class="h6 font-weight-bold text-dark mb-0"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <div class="stat-box">
                                <label class="small text-muted font-weight-bold uppercase mb-1">Assigned Role</label>
                                <div class="h6 font-weight-bold text-info mb-0">Root Admin</div>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <div class="stat-box">
                                <label class="small text-muted font-weight-bold uppercase mb-1">Access Level</label>
                                <div class="h6 font-weight-bold text-dark mb-0">Full Authorization</div>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <div class="stat-box">
                                <label class="small text-muted font-weight-bold uppercase mb-1">Member Since</label>
                                <div class="h6 font-weight-bold text-dark mb-0">H-Track Pilot Period</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: SECURITY UPDATES -->
        <div class="tab-pane fade p-5" id="security" role="tabpanel">
            <div class="max-width: 800px; margin: 0 auto;">
                <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2"><i class="fas fa-shield-alt text-warning mr-2"></i> Credential Management</h5>
                <form action="../includes/admin_update.php" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-muted text-uppercase small">Update Username</label>
                                <input type="text" name="new_uname" class="form-control form-control-lg bg-light" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-muted text-uppercase small">Current Password (Verify)</label>
                                <input type="password" name="old_pass" class="form-control form-control-lg" placeholder="Type password to save changes" required>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded border bg-light mb-4">
                        <h6 class="font-weight-bold mb-3 small uppercase text-info">Update Password (Optional)</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold">New Password</label>
                                    <input type="password" name="new_pass" class="form-control" placeholder="Leave blank to keep same">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold">Repeat Password</label>
                                    <input type="password" name="conf_new_pass" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="admin_update_submit" class="btn btn-info btn-lg px-5 font-weight-bold shadow-sm">
                        SAVE ACCOUNT CHANGES
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB 3: LOGOUT -->
        <div class="tab-pane fade p-5 text-center" id="logout" role="tabpanel">
            <div class="py-5">
                <div class="mb-4">
                    <i class="fas fa-sign-out-alt fa-5x text-danger opacity-2"></i>
                </div>
                <h3 class="font-weight-bold mb-3">Terminate Administrative Session?</h3>
                <p class="text-muted mb-5">By clicking below, you will be immediately signed out of the H-Track Admin Panel. <br>All unsaved progress in other modules might be lost.</p>
                
                <a href="../includes/logout.inc.php" class="btn btn-outline-danger btn-lg px-5 font-weight-bold">
                    YES, LOGOUT NOW
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
require 'admin_footer.php'; 
?>
