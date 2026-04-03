<?php
/**
 * Settings > Appearance
 * Theme switcher — moved from old monolithic settings.php
 */
$msg = '';

if (isset($_POST['update_theme'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_settings_theme')) { http_response_code(403); die('Forbidden'); }
    require_once __DIR__ . '/../../../includes/theme-registry.php';
    $theme = $_POST['active_theme'] ?? 'broadsheet-light';
    if (tf_is_valid_theme($theme)) {
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES ('active_theme', ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        $stmt->execute([$theme]);
        log_activity($db, 'SETTINGS', 'Theme Changed', "Theme set to: $theme");
        $msg = "<div class='alert alert-success'><i class='bi bi-palette'></i> Theme updated to <strong>" . htmlspecialchars($theme) . "</strong>.</div>";
    }
}

require_once __DIR__ . '/../../../includes/theme-registry.php';
$res          = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$active_theme = $res['active_theme'] ?? 'broadsheet-light';
if (!preg_match('/-(light|dark)$/', $active_theme)) $active_theme .= '-light';

$registry = tf_get_theme_registry();
$groups   = [];
foreach ($registry as $slug => $t) {
    $groups[$t['group']][$t['variant']] = array_merge(['slug' => $slug], $t);
}
ksort($groups);
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">Appearance</div>
        <div class="page-subtitle">Choose the visual theme for your site</div>
    </div>
</div>

<?php echo $msg; ?>

<div class="a-card">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-palette" style="color:var(--a-accent);"></i> Site Theme</div>
    </div>
    <div class="a-card-body">
        <p class="text-muted mb-3" style="font-size:0.875rem;">
            Drop a <code>name-light.css</code> or <code>name-dark.css</code> into <code>/themes/</code> with the required metadata header to register it automatically.
        </p>
        <form method="POST">
            <?php echo csrf_input('admin_settings_theme'); ?>
            <div class="admin-theme-list mb-3">
                <?php foreach ($groups as $group_name => $variants):
                    $ordered = [];
                    if (isset($variants['Light'])) $ordered[] = $variants['Light'];
                    if (isset($variants['Dark']))  $ordered[] = $variants['Dark'];
                    foreach ($ordered as $t):
                        $slug     = $t['slug'];
                        $is_active = ($active_theme === $slug);
                        $colors   = $t['colors'];
                        $c1 = $colors[0] ?? '#888'; $c2 = $colors[1] ?? '#555';
                        $c3 = $colors[2] ?? '#333'; $c4 = $colors[3] ?? '#111';
                        $gradient = "linear-gradient(135deg,{$c1} 0%,{$c1} 30%,{$c2} 30%,{$c2} 55%,{$c3} 55%,{$c3} 78%,{$c4} 78%)";
                        $vl       = strtolower($t['variant']);
                ?>
                <label class="atl-row <?php echo $is_active ? 'atl-active' : ''; ?>">
                    <input type="radio" name="active_theme" value="<?php echo htmlspecialchars($slug); ?>" <?php echo $is_active ? 'checked' : ''; ?> style="position:absolute;opacity:0;pointer-events:none;">
                    <div class="atl-icon" style="background:<?php echo $gradient; ?>;"></div>
                    <div class="atl-info">
                        <span class="atl-name"><?php echo htmlspecialchars($t['label']); ?></span>
                        <span class="atl-badge atl-badge--<?php echo $vl; ?>"><?php echo htmlspecialchars($t['variant']); ?></span>
                        <span class="atl-desc"><?php echo htmlspecialchars($t['layout']); ?></span>
                    </div>
                    <?php if ($is_active): ?>
                    <span class="atl-check"><i class="bi bi-check-circle-fill"></i></span>
                    <?php endif; ?>
                </label>
                <?php endforeach; endforeach; ?>
            </div>
            <button type="submit" name="update_theme" class="btn btn-primary btn-sm">
                <i class="bi bi-palette"></i> Apply Theme
            </button>
        </form>
    </div>
</div>

<style>
.admin-theme-list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
@media (max-width: 700px) { .admin-theme-list { grid-template-columns: 1fr; } }
.atl-row { display:flex; align-items:center; gap:0.75rem; border:1.5px solid var(--a-border); border-radius:8px; padding:0.5rem 0.75rem; cursor:pointer; transition:border-color 0.13s,background 0.13s; position:relative; }
.atl-row:hover { border-color:var(--a-accent); background:rgba(0,0,0,0.02); }
.atl-row.atl-active { border-color:var(--a-accent); background:rgba(0,0,0,0.03); }
.atl-icon { width:75px; height:75px; border-radius:6px; flex-shrink:0; border:1px solid rgba(0,0,0,0.1); }
.atl-info { flex:1; display:flex; flex-direction:column; gap:0.2rem; min-width:0; }
.atl-name { font-size:0.88rem; font-weight:700; color:var(--a-text); }
.atl-badge { font-size:0.65rem; font-weight:600; padding:0.1em 0.5em; border-radius:100px; letter-spacing:0.04em; text-transform:uppercase; display:inline-block; width:fit-content; }
.atl-badge--light { background:#fff8e1; color:#b45309; border:1px solid #fde68a; }
.atl-badge--dark  { background:#1e1b4b; color:#a5b4fc; border:1px solid #312e81; }
.atl-desc { font-size:0.72rem; color:var(--a-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.atl-check { color:var(--a-accent); font-size:1.1rem; flex-shrink:0; }
</style>

<script>
document.querySelectorAll('.atl-row').forEach(function(row) {
    row.addEventListener('click', function() {
        document.querySelectorAll('.atl-row').forEach(function(r) { r.classList.remove('atl-active'); });
        this.classList.add('atl-active');
        this.querySelector('input[type="radio"]').checked = true;
    });
});
</script>
