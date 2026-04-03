<?php
/**
 * Settings > Site Settings
 */
$msg = '';

if (isset($_POST['update_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_update')) { http_response_code(403); die('Forbidden'); }
    foreach (($_POST['config'] ?? []) as $key => $value) {
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        $stmt->execute([$key, $value]);
    }
    log_activity($db, 'SETTINGS', 'Site Settings Updated', 'General configuration modified');
    $msg = "<div class='alert alert-success'><i class='bi bi-check-all'></i> Settings saved.</div>";
}

$res = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$dynamic_sections = function_exists('get_registered_settings_sections') ? get_registered_settings_sections() : [];
$allowed_types = ['text', 'email', 'password', 'textarea'];
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">Site Settings</div>
        <div class="page-subtitle">General configuration for your site</div>
    </div>
</div>

<?php echo $msg; ?>

<div class="a-card">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-sliders" style="color:var(--a-accent);"></i> General</div>
    </div>
    <div class="a-card-body">
        <form method="POST">
            <?php echo csrf_input('admin_settings_update'); ?>
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="config[site_name]" value="<?php echo htmlspecialchars($res['site_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Footer Text</label>
                <input type="text" name="config[footer_text]" value="<?php echo htmlspecialchars(html_entity_decode($res['footer_text'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>">
                <div class="form-help">HTML entities like &amp;copy; are supported.</div>
            </div>

            <?php if (function_exists('run_hook')) run_hook('admin_settings_ui'); ?>

            <?php foreach ($dynamic_sections as $sectionId => $section):
                $fields = (isset($section['fields']) && is_array($section['fields'])) ? $section['fields'] : [];
                if (empty($fields)) continue;
                $section_title = htmlspecialchars($section['title'] ?? ucfirst((string)$sectionId));
                $section_icon  = htmlspecialchars($section['icon'] ?? 'bi bi-puzzle');
            ?>
                <hr>
                <div class="mb-2" style="display:flex; align-items:center; gap:0.5rem;">
                    <i class="<?php echo $section_icon; ?>" style="color:var(--a-accent);"></i>
                    <strong><?php echo $section_title; ?></strong>
                </div>
                <?php if (!empty($section['description'])): ?>
                <p class="text-muted mb-2" style="font-size:0.8rem;"><?php echo htmlspecialchars($section['description']); ?></p>
                <?php endif; ?>
                <?php foreach ($fields as $key => $field):
                    if (!isset($field['type']) || !in_array($field['type'], $allowed_types)) continue;
                    $type        = $field['type'];
                    $label       = htmlspecialchars($field['label'] ?? $key);
                    $rows        = (int)($field['rows'] ?? 4);
                    $placeholder = htmlspecialchars($field['placeholder'] ?? '');
                    $help        = htmlspecialchars($field['help'] ?? '');
                    $value       = htmlspecialchars((string)($res[$key] ?? $field['default'] ?? ''));
                ?>
                <div class="form-group">
                    <label><?php echo $label; ?></label>
                    <?php if ($type === 'textarea'): ?>
                        <textarea name="config[<?php echo htmlspecialchars($key); ?>]" rows="<?php echo $rows; ?>" placeholder="<?php echo $placeholder; ?>"><?php echo $value; ?></textarea>
                    <?php else: ?>
                        <input type="<?php echo $type; ?>" name="config[<?php echo htmlspecialchars($key); ?>]" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>">
                    <?php endif; ?>
                    <?php if ($help): ?><div class="form-help"><?php echo $help; ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <button type="submit" name="update_settings" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg"></i> Save Settings
            </button>
        </form>
    </div>
</div>

<!-- Categories -->
<?php
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$cat_msg = '';

if (isset($_POST['add_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_add_category')) { http_response_code(403); die('Forbidden'); }
    $cat = trim($_POST['new_category']);
    if (!empty($cat)) {
        try {
            $db->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$cat]);
            log_activity($db, 'CRUD', 'Category Created', $cat);
            $cat_msg = "<div class='alert alert-success'><i class='bi bi-tag'></i> Category added.</div>";
            $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
        } catch (Exception $e) {
            $cat_msg = "<div class='alert alert-danger'>Category already exists.</div>";
        }
    }
}

if (isset($_POST['delete_cat'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_delete_category')) { http_response_code(403); die('Forbidden'); }
    $db->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)($_POST['delete_cat'] ?? 0)]);
    $cat_msg = "<div class='alert alert-warning'>Category removed.</div>";
    $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}
?>

<?php echo $cat_msg; ?>

<div class="a-card mt-3">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-tags" style="color:var(--a-accent);"></i> Post Categories</div>
    </div>
    <div class="a-card-body">
        <form method="POST" class="flex gap-2 mb-3" style="display:flex; gap:0.75rem;">
            <?php echo csrf_input('admin_settings_add_category'); ?>
            <input type="text" name="new_category" placeholder="New category name" style="flex:1;">
            <button type="submit" name="add_category" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add
            </button>
        </form>
        <?php if (empty($categories)): ?>
            <p class="text-muted text-small">No categories yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th style="width:80px; text-align:center;">Action</th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td style="text-align:center;">
                            <form method="POST" onsubmit="return confirm('Remove this category?')">
                                <?php echo csrf_input('admin_settings_delete_category'); ?>
                                <input type="hidden" name="delete_cat" value="<?php echo (int)$c['id']; ?>">
                                <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
