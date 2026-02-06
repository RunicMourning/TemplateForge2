<?php
// sidebars/01_admin_login.php
$is_logged_in = isset($_SESSION['user_id']);
?>

<div class="card mt-3 shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff;">
    <div style="height: 4px; background: linear-gradient(90deg, var(--bs-primary), #6610f2);"></div>
    
    <div class="card-header bg-transparent border-0 pt-4 pb-0">
        <h6 class="text-uppercase fw-bold text-muted tracking-widest mb-1" style="font-size: 0.7rem;">
            System Access
        </h6>
        <h5 class="fw-bold d-flex align-items-center text-dark">
            <i class="bi bi-shield-lock-fill me-2 text-primary"></i> 
            <?php echo $is_logged_in ? 'Session Active' : 'Administrator'; ?>
        </h5>
    </div>

    <div class="card-body">
        <?php if (!$is_logged_in): ?>
            <form method="post" action="admin/index.php">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-light">
                            <i class="bi bi-person-fill text-muted"></i>
                        </span>
                        <input type="text" name="user" class="form-control border-0 bg-light" placeholder="Username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-light">
                            <i class="bi bi-lock-fill text-muted"></i>
                        </span>
                        <input type="password" name="pass" class="form-control border-0 bg-light" placeholder="Password" required>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" name="login" class="btn btn-primary rounded-pill shadow-sm fw-bold">
                        Sign In <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center py-3 bg-light rounded-3 border border-light-subtle">
                <div class="position-relative d-inline-block mb-2">
                    <i class="bi bi-person-circle fs-1 text-primary"></i>
                    <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle">
                        <span class="visually-hidden">Online</span>
                    </span>
                </div>
                <p class="small fw-bold mb-3">Welcome back, Admin</p>
                
                <div class="px-3">
                    <a href="admin/index.php" class="btn btn-sm btn-dark rounded-pill w-100 mb-2 shadow-sm">
                        Go to Dashboard
                    </a>
                    <a href="admin/logout.php" class="text-danger text-decoration-none x-small fw-bold">
                        <i class="bi bi-power me-1"></i> Terminate Session
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
queue_css("
.tracking-widest { letter-spacing: 0.1em; }
.x-small { font-size: 0.75rem; }
.border-light-subtle { border-color: #f1f1f1 !important; }
");
?>