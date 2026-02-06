<?php
$msg = "";

// 1. Handle New User
if (isset($_POST['add_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_add')) { http_response_code(403); die('Forbidden'); }
    $user = $_POST['new_username'];
    $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$user, $pass]);
        log_activity($db, 'AUTH', 'User Created', $user);
        $msg = "<div class='alert alert-success'>User <strong>$user</strong> created!</div>";
    } catch (Exception $e) { $msg = "<div class='alert alert-danger'>Username exists.</div>"; }
}

// 2. Handle Deletion
if (isset($_POST['delete_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_delete')) { http_response_code(403); die('Forbidden'); }
    $delete_user_id = (int) ($_POST['delete_user'] ?? 0);
    if ($delete_user_id != $_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_user_id]);
        $msg = "<div class='alert alert-warning'>User removed.</div>";
    }
}

// 3. Update Self
if (isset($_POST['update_me'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_update_me')) { http_response_code(403); die('Forbidden'); }
    $user = $_POST['my_username'];
    $pass = $_POST['my_password'];
    if (!empty($pass)) {
        $stmt = $db->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
        $stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$user, $_SESSION['user_id']]);
    }
    $_SESSION['username'] = $user;
    $msg = "<div class='alert alert-success'>Profile updated!</div>";
}

$all_users = $db->query("SELECT id, username FROM users")->fetchAll();
?>

<div class="container py-4">
    <h2 class="fw-bold mb-4">User Management</h2>
    <?php echo $msg; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="fw-bold">My Account</h5>
                    <form method="POST">
                        <?php echo csrf_input('admin_users_update_me'); ?>
                        <div class="mb-2">
                            <label class="small fw-bold">Username</label>
                            <input type="text" name="my_username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">New Password</label>
                            <input type="password" name="my_password" class="form-control" placeholder="Leave blank to keep">
                        </div>
                        <button type="submit" name="update_me" class="btn btn-dark w-100">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="fw-bold">Add User</h5>
                    <form method="POST">
                        <?php echo csrf_input('admin_users_add'); ?>
                        <input type="text" name="new_username" class="form-control mb-2" placeholder="Username" required>
                        <input type="password" name="new_password" class="form-control mb-2" placeholder="Password" required>
                        <button type="submit" name="add_user" class="btn btn-success w-100">Create Account</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">User</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_users as $u): ?>
                            <tr>
                                <td class="ps-3">
                                    <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                    <?php if($u['id'] == $_SESSION['user_id']) echo '<span class="badge bg-primary ms-2">You</span>'; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" action="index.php?view=users" class="d-inline" onsubmit="return confirm('Delete user?')">
                                            <?php echo csrf_input('admin_users_delete'); ?>
                                            <input type="hidden" name="delete_user" value="<?php echo (int) $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
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
</div>