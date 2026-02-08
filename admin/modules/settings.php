<?php
$msg = "";

// 1. Handle Settings Update
if (isset($_POST['update_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_update')) { http_response_code(403); die('Forbidden'); }
    foreach (($_POST['config'] ?? []) as $key => $value) {
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        $stmt->execute([$key, $value]);
    }
    log_activity($db, 'SETTINGS', 'Configuration Updated', "Site settings modified");
    $msg = "<div class='alert alert-success border-0 shadow-sm'>Site settings updated!</div>";
}

// 2. Handle Category Addition
if (isset($_POST['add_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_add_category')) { http_response_code(403); die('Forbidden'); }
    $cat = trim($_POST['new_category']);
    if (!empty($cat)) {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$cat]);
            log_activity($db, 'CRUD', 'Category Created', $cat);
            $msg = "<div class='alert alert-success border-0 shadow-sm'>Category <strong>$cat</strong> added!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger border-0 shadow-sm'>Category already exists.</div>";
        }
    }
}

// 3. Handle Category Deletion
if (isset($_POST['delete_cat'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_delete_category')) { http_response_code(403); die('Forbidden'); }
    $delete_cat_id = (int) ($_POST['delete_cat'] ?? 0);
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$delete_cat_id]);
    $msg = "<div class='alert alert-warning border-0 shadow-sm'>Category removed.</div>";
}

// Fetch Data
$res = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$dynamic_sections = function_exists('get_registered_settings_sections')
    ? get_registered_settings_sections()
    : [];

$allowed_types = ['text', 'email', 'password', 'textarea'];
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">System Settings</h2>
        <a href="index.php?view=users" class="btn btn-outline-dark btn-sm shadow-sm">
            <i class="bi bi-people me-2"></i>Manage Users
        </a>
    </div>
    
    <?php echo $msg; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="card-title mb-0">General Configuration</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="row g-3">
                        <?php echo csrf_input('admin_settings_update'); ?>
                        <div class="col-12">
                            <label class="form-label fw-bold">Site Name</label>
                            <input type="text" name="config[site_name]" value="<?php echo htmlspecialchars($res['site_name'] ?? ''); ?>" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Footer Text</label>
                            <input type="text" name="config[footer_text]" value="<?php echo htmlspecialchars($res['footer_text'] ?? ''); ?>" class="form-control">
                        </div>

                        <?php if (function_exists('run_hook')) run_hook('admin_settings_ui'); ?>

                        <?php foreach ($dynamic_sections as $sectionId => $section): ?>
                            <?php
                                $section_title = htmlspecialchars($section['title'] ?? ucfirst((string) $sectionId));
                                $section_description = htmlspecialchars($section['description'] ?? '');
                                $section_icon = htmlspecialchars($section['icon'] ?? 'bi bi-puzzle');
                                $fields = (isset($section['fields']) && is_array($section['fields'])) ? $section['fields'] : [];
                            ?>
                            <?php if (!empty($fields)): ?>
                                <div class="card shadow-sm border-0 mb-4 border-start border-primary border-5">
                                    <div class="card-header bg-white py-3">
                                        <h5 class="card-title mb-0 text-primary fw-bold">
                                            <i class="<?php echo $section_icon; ?> me-2"></i><?php echo $section_title; ?>
                                        </h5>
                                        <?php if (!empty($section_description)): ?>
                                            <p class="text-muted small mb-0 mt-2"><?php echo $section_description; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-4">
                                        <?php foreach ($fields as $field): ?>
                                            <?php
                                                $key = $field['key'] ?? '';
                                                if (!is_string($key) || $key === '') {
                                                    continue;
                                                }

                                                $label = htmlspecialchars($field['label'] ?? $key);
                                                $type = strtolower((string) ($field['type'] ?? 'text'));
                                                if (!in_array($type, $allowed_types, true)) {
                                                    $type = 'text';
                                                }

                                                $placeholder = htmlspecialchars($field['placeholder'] ?? '');
                                                $help = htmlspecialchars($field['help'] ?? '');
                                                $default_value = (string) ($field['default'] ?? '');
                                                $value = htmlspecialchars((string) ($res[$key] ?? $default_value));
                                                $rows = max(2, (int) ($field['rows'] ?? 4));
                                            ?>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold"><?php echo $label; ?></label>
                                                <?php if ($type === 'textarea'): ?>
                                                    <textarea name="config[<?php echo htmlspecialchars($key); ?>]" class="form-control" rows="<?php echo $rows; ?>" placeholder="<?php echo $placeholder; ?>"><?php echo $value; ?></textarea>
                                                <?php else: ?>
                                                    <input type="<?php echo $type; ?>" name="config[<?php echo htmlspecialchars($key); ?>]" value="<?php echo $value; ?>" class="form-control" placeholder="<?php echo $placeholder; ?>">
                                                <?php endif; ?>
                                                <?php if (!empty($help)): ?>
                                                    <div class="form-text"><?php echo $help; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="col-12 pt-2">
                            <button type="submit" name="update_settings" class="btn btn-primary px-4">Save Configuration</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="card-title mb-0">Blog Categories</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="input-group mb-3">
                        <?php echo csrf_input('admin_settings_add_category'); ?>
                        <input type="text" name="new_category" class="form-control" placeholder="New category name..." required>
                        <button class="btn btn-success" type="submit" name="add_category">Add</button>
                    </form>

                    <ul class="list-group list-group-flush">
                        <?php foreach($categories as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><?php echo htmlspecialchars($c['name']); ?></span>
                            <form method="POST" action="index.php?view=settings" class="d-inline" onsubmit="return confirm('Remove this category?')">
                                <?php echo csrf_input('admin_settings_delete_category'); ?>
                                <input type="hidden" name="delete_cat" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="btn btn-link text-danger p-0">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
