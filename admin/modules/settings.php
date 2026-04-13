<?php
/**
 * Settings — Sub-page Router
 * Dispatches to admin/modules/settings/*.php
 * Sub-page navigation lives in the main sidebar (admin/views/header.php).
 * Addons register sub-pages via: add_hook('settings_sections', function(&$sections){ ... });
 */

// ── Core sub-pages ───────────────────────────────────────────────
$core_sections = [
    'site' => [
        'label' => 'Site Settings',
        'icon'  => 'bi-sliders',
        'file'  => __DIR__ . '/settings/site.php',
    ],
    'appearance' => [
        'label' => 'Appearance',
        'icon'  => 'bi-palette',
        'file'  => __DIR__ . '/settings/appearance.php',
    ],
    'navigation' => [
        'label' => 'Navigation',
        'icon'  => 'bi-list-ul',
        'file'  => __DIR__ . '/settings/navigation.php',
    ],
    'footer' => [
        'label' => 'Footer',
        'icon'  => 'bi-layout-sidebar-reverse',
        'file'  => __DIR__ . '/settings/footer.php',
    ],
    'podcast' => [
        'label' => 'Podcast',
        'icon'  => 'bi-mic',
        'file'  => __DIR__ . '/settings/podcast.php',
    ],
    'wiki' => [
        'label' => 'Wiki',
        'icon'  => 'bi-journal-bookmark',
        'file'  => __DIR__ . '/settings/wiki.php',
    ],
    'addons' => [
        'label' => 'Addons',
        'icon'  => 'bi-puzzle',
        'file'  => __DIR__ . '/settings/addons.php',
    ],
    'users' => [
        'label' => 'Users',
        'icon'  => 'bi-people',
        'file'  => __DIR__ . '/settings/users.php',
    ],
];

// ── Addon-registered sub-pages ───────────────────────────────────
$addon_sections = [];
if (function_exists('run_hook')) {
    run_hook('settings_sections', $addon_sections);
}

$all_sections = array_merge($core_sections, $addon_sections);

// ── Resolve active section ───────────────────────────────────────
$section = $_GET['section'] ?? 'site';
$section = preg_replace('/[^a-z0-9_\-]/', '', $section);
if (!isset($all_sections[$section])) $section = 'site';

$active_file  = $all_sections[$section]['file'];
$active_label = $all_sections[$section]['label'];
$active_icon  = $all_sections[$section]['icon'];
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">
            <i class="bi <?php echo htmlspecialchars($active_icon); ?>" style="color:var(--a-accent); margin-right:0.4rem;"></i>
            <?php echo htmlspecialchars($active_label); ?>
        </div>
        <div class="page-subtitle">Settings &rsaquo; <?php echo htmlspecialchars($active_label); ?></div>
    </div>
</div>

<?php if (file_exists($active_file)): ?>
    <?php include $active_file; ?>
<?php else: ?>
    <div class="empty-state">
        <span class="empty-icon"><i class="bi bi-tools"></i></span>
        <h5><?php echo htmlspecialchars($active_label); ?></h5>
        <p class="text-small">This settings section is coming soon.</p>
    </div>
<?php endif; ?>
