<?php
$msg = "";

// Handle Settings Update
if (isset($_POST['update_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_update')) { http_response_code(403); die('Forbidden'); }
    foreach (($_POST['config'] ?? []) as $key => $value) {
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        $stmt->execute([$key, $value]);
    }
    log_activity($db, 'SETTINGS', 'Configuration Updated', "Site settings modified");
    $msg = "<div class='alert alert-success'><i class='bi bi-check-all'></i> Site settings updated successfully.</div>";
}

// Handle Theme Update
if (isset($_POST['update_theme'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_theme')) { http_response_code(403); die('Forbidden'); }
    $allowed_themes = ['broadsheet', 'inkwell', 'blueprint', 'fieldnotes', 'terminal', 'magazine'];
    $theme = $_POST['active_theme'] ?? 'broadsheet';
    if (in_array($theme, $allowed_themes)) {
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES ('active_theme', ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        $stmt->execute([$theme]);
        log_activity($db, 'SETTINGS', 'Theme Changed', "Theme set to: $theme");
        $msg = "<div class='alert alert-success'><i class='bi bi-palette'></i> Theme updated to <strong>" . htmlspecialchars(ucfirst($theme)) . "</strong>.</div>";
    }
}

// Handle Category Addition
if (isset($_POST['add_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_add_category')) { http_response_code(403); die('Forbidden'); }
    $cat = trim($_POST['new_category']);
    if (!empty($cat)) {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$cat]);
            log_activity($db, 'CRUD', 'Category Created', $cat);
            $msg = "<div class='alert alert-success'><i class='bi bi-tag'></i> Category <strong>" . htmlspecialchars($cat) . "</strong> added.</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle'></i> Category already exists.</div>";
        }
    }
}

// Handle Category Deletion
if (isset($_POST['delete_cat'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_delete_category')) { http_response_code(403); die('Forbidden'); }
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([(int)($_POST['delete_cat'] ?? 0)]);
    $msg = "<div class='alert alert-warning'><i class='bi bi-trash'></i> Category removed.</div>";
}

// Fetch data
$res           = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$categories    = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$active_theme  = $res['active_theme'] ?? 'broadsheet';
$dynamic_sections = function_exists('get_registered_settings_sections') ? get_registered_settings_sections() : [];
$allowed_types = ['text', 'email', 'password', 'textarea'];

$themes = [
    'broadsheet' => ['label' => 'Broadsheet', 'desc' => 'Journal palette — sidebar right'],
    'inkwell'    => ['label' => 'Inkwell',    'desc' => 'Darkly palette — sidebar left'],
    'blueprint'  => ['label' => 'Blueprint',  'desc' => 'Flatly palette — app shell panel'],
    'fieldnotes' => ['label' => 'Fieldnotes', 'desc' => 'Sandstone palette — full-width'],
    'terminal'   => ['label' => 'Terminal',   'desc' => 'Cyborg palette — no sidebar'],
    'magazine'   => ['label' => 'Magazine',   'desc' => 'Vapor palette — narrow left rail'],
];
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">System Settings</div>
        <div class="page-subtitle">Configure site options, appearance, and categories</div>
    </div>
    <a href="index.php?view=users" class="btn btn-outline btn-sm">
        <i class="bi bi-people"></i> Manage Users
    </a>
</div>

<?php echo $msg; ?>

<!-- Theme Selector -->
<div class="a-card mb-3">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-palette" style="color: var(--a-accent);"></i> Site Theme</div>
    </div>
    <div class="a-card-body">
        <form method="POST">
            <?php echo csrf_input('admin_settings_theme'); ?>
            <p class="text-muted mb-3" style="font-size:0.875rem;">Choose the visual style for the public-facing site. Changes take effect immediately.</p>
            <div class="theme-grid mb-3">
                <?php foreach ($themes as $slug => $theme): ?>
                <label class="theme-option theme-<?php echo $slug; ?> <?php echo ($active_theme === $slug) ? 'selected' : ''; ?>">
                    <input type="radio" name="active_theme" value="<?php echo $slug; ?>" <?php echo ($active_theme === $slug) ? 'checked' : ''; ?>>
                    <div class="theme-preview">
                        <div class="theme-preview-nav"></div>
                        <div class="theme-preview-body">
                            <div class="theme-preview-main"></div>
                            <div class="theme-preview-side"></div>
                        </div>
                    </div>
                    <div class="theme-label">
                        <?php echo $theme['label']; ?>
                        <div style="font-size:0.68rem; font-weight:400; color:var(--a-text-muted); margin-top:0.15rem;"><?php echo $theme['desc']; ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" name="update_theme" class="btn btn-primary btn-sm">
                <i class="bi bi-palette"></i> Apply Theme
            </button>
        </form>
    </div>
</div>

<script>
// Make theme cards clickable and highlight selected
document.querySelectorAll('.theme-option').forEach(function(opt) {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.theme-option').forEach(function(o){ o.classList.remove('selected'); });
        this.classList.add('selected');
    });
});
</script>

<div class="a-grid-2">
    <!-- General Config -->
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-sliders" style="color:var(--a-accent);"></i> General Configuration</div>
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
                    <input type="text" name="config[footer_text]" value="<?php echo htmlspecialchars($res['footer_text'] ?? ''); ?>">
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
                        <strong style="font-size:0.9rem;"><?php echo $section_title; ?></strong>
                    </div>
                    <?php if (!empty($section['description'])): ?>
                        <p class="text-muted mb-2" style="font-size:0.8rem;"><?php echo htmlspecialchars($section['description']); ?></p>
                    <?php endif; ?>
                    <?php foreach ($fields as $field):
                        $key   = $field['key'] ?? '';
                        if (!is_string($key) || $key === '') continue;
                        $label = htmlspecialchars($field['label'] ?? $key);
                        $type  = strtolower((string)($field['type'] ?? 'text'));
                        if (!in_array($type, $allowed_types, true)) $type = 'text';
                        $placeholder = htmlspecialchars($field['placeholder'] ?? '');
                        $help        = htmlspecialchars($field['help'] ?? '');
                        $value       = htmlspecialchars((string)($res[$key] ?? $field['default'] ?? ''));
                        $rows        = max(2, (int)($field['rows'] ?? 4));
                    ?>
                    <div class="form-group">
                        <label><?php echo $label; ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea name="config[<?php echo htmlspecialchars($key); ?>]" rows="<?php echo $rows; ?>" placeholder="<?php echo $placeholder; ?>"><?php echo $value; ?></textarea>
                        <?php else: ?>
                            <input type="<?php echo $type; ?>" name="config[<?php echo htmlspecialchars($key); ?>]" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>">
                        <?php endif; ?>
                        <?php if (!empty($help)): ?><div class="form-help"><?php echo $help; ?></div><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <button type="submit" name="update_settings" class="btn btn-primary btn-sm">
                    <i class="bi bi-save"></i> Save Configuration
                </button>
            </form>
        </div>
    </div>

    <!-- Blog Categories -->
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-tags" style="color:var(--a-warning);"></i> Blog Categories</div>
        </div>
        <div class="a-card-body">
            <form method="POST" class="mb-3">
                <?php echo csrf_input('admin_settings_add_category'); ?>
                <div class="input-group">
                    <input type="text" name="new_category" placeholder="New category name&hellip;" required>
                    <button class="btn btn-success" type="submit" name="add_category">Add</button>
                </div>
            </form>

            <?php if (empty($categories)): ?>
                <div class="empty-state" style="padding: 1.5rem;">
                    <span class="empty-icon"><i class="bi bi-tags"></i></span>
                    <p class="text-muted text-sm">No categories yet.</p>
                </div>
            <?php else: ?>
            <div class="a-table-wrap">
                <table>
                    <thead>
                        <tr><th>Category</th><th style="width:50px;"></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Remove this category?');">
                                    <?php echo csrf_input('admin_settings_delete_category'); ?>
                                    <input type="hidden" name="delete_cat" value="<?php echo (int)$c['id']; ?>">
                                    <button type="submit" class="btn-link danger btn-sm"><i class="bi bi-x-circle"></i></button>
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
</div>
