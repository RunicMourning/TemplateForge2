<?php
$is_logged_in = isset($_SESSION['user_id']);
?>

<div class="widget-card">
    <div class="card-accent"></div>
    <div class="p-3">
        <div class="widget-title"><i class="bi bi-shield-lock-fill"></i> <?php echo $is_logged_in ? 'Session Active' : 'Administrator'; ?></div>

        <?php if (!$is_logged_in): ?>
            <form method="post" action="admin/index.php">
                <div class="form-group">
                    <div style="position: relative;">
                        <i class="bi bi-person-fill" style="position:absolute; left:0.7rem; top:50%; transform:translateY(-50%); color:var(--tf-text-muted); font-size:0.85rem;"></i>
                        <input type="text" name="user" placeholder="Username" required style="padding-left: 2rem;">
                    </div>
                </div>
                <div class="form-group">
                    <div style="position: relative;">
                        <i class="bi bi-lock-fill" style="position:absolute; left:0.7rem; top:50%; transform:translateY(-50%); color:var(--tf-text-muted); font-size:0.85rem;"></i>
                        <input type="password" name="pass" placeholder="Password" required style="padding-left: 2rem;">
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-full">
                    Sign In <i class="bi bi-arrow-right-short"></i>
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 1rem; background: var(--tf-surface-2); border: 1px solid var(--tf-border); border-radius: var(--tf-radius);">
                <i class="bi bi-person-circle" style="font-size: 2.5rem; color: var(--tf-accent); display: block; margin-bottom: 0.5rem;"></i>
                <p class="text-small fw-semibold mb-3">Welcome back, Admin</p>
                <a href="admin/index.php" class="btn btn-dark btn-sm w-full mb-2">Go to Dashboard</a>
                <a href="admin/logout.php" class="link-muted text-small"><i class="bi bi-power" style="margin-right:0.25rem;"></i> Sign Out</a>
            </div>
        <?php endif; ?>
    </div>
</div>
