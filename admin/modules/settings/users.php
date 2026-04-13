<?php
/**
 * Settings > Users
 * Layer B (Application) — user management.
 */

$msg = '';

if (isset($_POST['add_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_add')) { http_response_code(403); die('Forbidden'); }
    $user = trim($_POST['new_username'] ?? '');
    $pass = $_POST['new_password'] ?? '';
    if ($user && $pass) {
        try {
            $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)")
               ->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
            log_activity($db, 'AUTH', 'User Created', $user);
            $msg = "<div class='alert alert-success'><i class='bi bi-check-lg'></i> User <strong>" . htmlspecialchars($user) . "</strong> created.</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'>Username already exists.</div>";
        }
    }
}

if (isset($_POST['delete_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_delete')) { http_response_code(403); die('Forbidden'); }
    $del_id = (int)($_POST['delete_user'] ?? 0);
    if ($del_id && $del_id !== (int)$_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
        log_activity($db, 'AUTH', 'User Deleted', "ID: $del_id");
        $msg = "<div class='alert alert-warning'><i class='bi bi-trash'></i> User removed.</div>";
    }
}

if (isset($_POST['update_me'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_update_me')) { http_response_code(403); die('Forbidden'); }
    $user = trim($_POST['my_username'] ?? '');
    $pass = $_POST['my_password'] ?? '';
    if ($user) {
        if (!empty($pass)) {
            $db->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?")
               ->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        } else {
            $db->prepare("UPDATE users SET username = ? WHERE id = ?")
               ->execute([$user, $_SESSION['user_id']]);
        }
        $_SESSION['username'] = $user;
        $msg = "<div class='alert alert-success'><i class='bi bi-check-lg'></i> Profile updated.</div>";
    }
}

$all_users = $db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">Users</div>
        <div class="page-subtitle">Manage admin accounts and your profile</div>
    </div>
</div>

<?php echo $msg; ?>

<div class="users-layout">

    <!-- Left: My Account + Add User -->
    <div style="display:flex; flex-direction:column; gap:1rem;">

        <div class="a-card">
            <div class="a-card-header">
                <div class="a-card-title"><i class="bi bi-person-circle" style="color:var(--a-accent);"></i> My Account</div>
            </div>
            <div class="a-card-body">
                <form method="POST">
                    <?php echo csrf_input('admin_users_update_me'); ?>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="my_username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="my_password" placeholder="Leave blank to keep current">
                    </div>
                    <button type="submit" name="update_me" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-header">
                <div class="a-card-title"><i class="bi bi-person-plus" style="color:var(--a-accent);"></i> Add User</div>
            </div>
            <div class="a-card-body">
                <form method="POST">
                    <?php echo csrf_input('admin_users_add'); ?>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="new_username" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="new_password" placeholder="Password" required>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Create Account
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Right: User list -->
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-people" style="color:var(--a-accent);"></i> All Users</div>
        </div>
        <div class="a-card-body" style="padding:0;">
            <div class="table-wrap" style="border:none; border-radius:0;">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th style="width:100px; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $u): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                                <span class="badge" style="margin-left:0.5rem;">You</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Remove this user?')">
                                    <?php echo csrf_input('admin_users_delete'); ?>
                                    <input type="hidden" name="delete_user" value="<?php echo (int)$u['id']; ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:0.75rem;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
