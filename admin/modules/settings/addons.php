<?php
/**
 * Settings > Addons
 * Shows active addons and any addon-registered settings sections
 */

$addons_path = __DIR__ . '/../../../../addons';
$addon_files = glob($addons_path . '/*.php') ?: [];
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">Addons</div>
        <div class="page-subtitle">Active addons are loaded automatically from <code>/addons/</code></div>
    </div>
</div>

<div class="a-card mb-3">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-puzzle" style="color:var(--a-accent);"></i> Active Addons</div>
    </div>
    <div class="a-card-body" style="padding:0;">
        <?php if (empty($addon_files)): ?>
        <p class="text-muted text-small" style="padding:1.5rem;">No addons found in <code>/addons/</code>.</p>
        <?php else: ?>
        <div class="table-wrap" style="border:none; border-radius:0;">
            <table>
                <thead><tr><th>File</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($addon_files as $file): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars(basename($file)); ?></code></td>
                        <td><span class="badge badge-accent"><i class="bi bi-check-circle" style="margin-right:0.3rem;"></i> Active</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Render any addon-registered settings sections
$addon_setting_sections = function_exists('get_registered_settings_sections') ? get_registered_settings_sections() : [];
if (!empty($addon_setting_sections)):
?>
<div class="a-card">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-gear" style="color:var(--a-accent);"></i> Addon Settings</div>
    </div>
    <div class="a-card-body">
        <?php if (function_exists('run_hook')) run_hook('admin_settings_ui'); ?>
    </div>
</div>
<?php endif; ?>
