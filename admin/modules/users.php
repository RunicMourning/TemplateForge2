<?php
$msg = "";
ensure_user_schema($db);
$permission_options = available_permissions();

// 1. Handle New User
if (isset($_POST['add_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_add')) { http_response_code(403); die('Forbidden'); }
    $user = trim((string) ($_POST['new_username'] ?? ''));
    $display_name = trim((string) ($_POST['new_display_name'] ?? ''));
    $raw_password = (string) ($_POST['new_password'] ?? '');
    $permissions = sanitize_permissions($_POST['new_permissions'] ?? []);

    if ($user === '' || $raw_password === '') {
        $msg = "<div class='alert alert-danger'>Username and password are required.</div>";
    } else {
        if ($display_name === '') {
            $display_name = $user;
        }

        $pass = password_hash($raw_password, PASSWORD_DEFAULT);
        try {
            $stmt = $db->prepare("INSERT INTO users (username, display_name, password, permissions) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user, $display_name, $pass, json_encode($permissions)]);
            log_activity($db, 'AUTH', 'User Created', $user);
            $msg = "<div class='alert alert-success'>User <strong>" . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . "</strong> created!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'>Username exists.</div>";
        }
    }
}

// 2. Handle Deletion
if (isset($_POST['delete_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_delete')) { http_response_code(403); die('Forbidden'); }
    $delete_user_id = (int) ($_POST['delete_user'] ?? 0);
    if ($delete_user_id !== (int) $_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_user_id]);
        $msg = "<div class='alert alert-warning'>User removed.</div>";
    }
}

// 3. Update Self
if (isset($_POST['update_me'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_update_me')) { http_response_code(403); die('Forbidden'); }
    $user = trim((string) ($_POST['my_username'] ?? ''));
    $display_name = trim((string) ($_POST['my_display_name'] ?? ''));
    $pass = (string) ($_POST['my_password'] ?? '');

    if ($user === '') {
        $msg = "<div class='alert alert-danger'>Username is required.</div>";
    } else {
        if ($display_name === '') {
            $display_name = $user;
        }

        if ($pass !== '') {
            $stmt = $db->prepare("UPDATE users SET username = ?, display_name = ?, password = ? WHERE id = ?");
            $stmt->execute([$user, $display_name, password_hash($pass, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, display_name = ? WHERE id = ?");
            $stmt->execute([$user, $display_name, $_SESSION['user_id']]);
        }

        $_SESSION['username'] = $user;
        $_SESSION['display_name'] = $display_name;
        $msg = "<div class='alert alert-success'>Profile updated!</div>";
    }
}

// 4. Update User Permissions
if (isset($_POST['update_permissions'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_users_update_permissions')) { http_response_code(403); die('Forbidden'); }
    $target_user_id = (int) ($_POST['target_user_id'] ?? 0);
    $permissions = sanitize_permissions($_POST['permissions'] ?? []);

    if ($target_user_id > 0) {
        $stmt = $db->prepare("UPDATE users SET permissions = ? WHERE id = ?");
        $stmt->execute([json_encode($permissions), $target_user_id]);

        if ($target_user_id === (int) $_SESSION['user_id']) {
            $_SESSION['permissions'] = $permissions;
        }

        $msg = "<div class='alert alert-success'>Permissions updated.</div>";
    }
}

$all_users = $db->query("SELECT id, username, display_name, permissions FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$self_user = null;
foreach ($all_users as $candidate_user) {
    if ((int) $candidate_user['id'] === (int) $_SESSION['user_id']) {
        $self_user = $candidate_user;
        break;
    }
}
$self_display_name = $self_user['display_name'] ?? ($_SESSION['display_name'] ?? $_SESSION['username']);
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
                        <div class="mb-2">
                            <label class="small fw-bold">Display Name</label>
                            <input type="text" name="my_display_name" value="<?php echo htmlspecialchars($self_display_name); ?>" class="form-control" required>
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
                        <input type="text" name="new_display_name" class="form-control mb-2" placeholder="Display Name" required>
                        <input type="password" name="new_password" class="form-control mb-2" placeholder="Password" required>
                        <label class="small fw-bold mt-2 mb-1">Starter Permissions</label>
                        <div class="border rounded p-2 mb-2" style="max-height: 180px; overflow-y: auto;">
                            <?php foreach ($permission_options as $permission_key => $permission_label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="new_permissions[]" value="<?php echo htmlspecialchars($permission_key); ?>" id="new_perm_<?php echo htmlspecialchars($permission_key); ?>">
                                    <label class="form-check-label small" for="new_perm_<?php echo htmlspecialchars($permission_key); ?>"><?php echo htmlspecialchars($permission_label); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
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
                                <th>Permissions</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_users as $u): ?>
                            <?php $user_permissions = sanitize_permissions(json_decode((string) ($u['permissions'] ?? '[]'), true) ?: []); ?>
                            <tr>
                                <td class="ps-3">
                                    <strong><?php echo htmlspecialchars($u['display_name'] ?: $u['username']); ?></strong>
                                    <div class="small text-muted">@<?php echo htmlspecialchars($u['username']); ?></div>
                                    <?php if((int) $u['id'] === (int) $_SESSION['user_id']) echo '<span class="badge bg-primary ms-0 mt-1">You</span>'; ?>
                                </td>
                                <td>
                                    <form method="POST" action="index.php?view=users">
                                        <?php echo csrf_input('admin_users_update_permissions'); ?>
                                        <input type="hidden" name="target_user_id" value="<?php echo (int) $u['id']; ?>">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($permission_options as $permission_key => $permission_label): ?>
                                                <div class="form-check me-2">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($permission_key); ?>" id="perm_<?php echo (int) $u['id']; ?>_<?php echo htmlspecialchars($permission_key); ?>" <?php echo in_array($permission_key, $user_permissions, true) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" for="perm_<?php echo (int) $u['id']; ?>_<?php echo htmlspecialchars($permission_key); ?>"><?php echo htmlspecialchars($permission_label); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="submit" name="update_permissions" class="btn btn-sm btn-outline-primary mt-2">Save Permissions</button>
                                    </form>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
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
