<?php
/**
 * Settings — Sub-page Router
 * Dispatches to admin/modules/settings/*.php
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

$active_file = $all_sections[$section]['file'];
?>
<div class="settings-layout">

    <!-- Secondary sidebar -->
    <nav class="settings-nav">
        <div class="settings-nav-label">Settings</div>
        <?php foreach ($all_sections as $slug => $sec):
            $is_active   = ($section === $slug);
            $file_exists = isset($sec['file']) && file_exists($sec['file']);
        ?>
        <a href="index.php?view=settings&section=<?php echo $slug; ?>"
           class="settings-nav-item <?php echo $is_active ? 'active' : ''; ?> <?php echo !$file_exists ? 'coming-soon' : ''; ?>">
            <i class="bi <?php echo htmlspecialchars($sec['icon']); ?>"></i>
            <?php echo htmlspecialchars($sec['label']); ?>
            <?php if (!$file_exists): ?>
            <span class="settings-nav-soon">Soon</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Section content -->
    <div class="settings-content">
        <?php if (file_exists($active_file)): ?>
            <?php include $active_file; ?>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-tools"></i></span>
                <h5><?php echo htmlspecialchars($all_sections[$section]['label']); ?></h5>
                <p class="text-small">This settings section is coming soon.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<style>
.settings-layout {
    display: grid;
    grid-template-columns: 210px 1fr;
    gap: 0;
    margin: -1.5rem;
    min-height: calc(100vh - 60px);
}

.settings-nav {
    border-right: 1px solid var(--a-border);
    padding: 1.25rem 0;
    background: var(--a-sidebar-bg, #f8f9fa);
    position: sticky;
    top: 0;
    align-self: start;
    max-height: 100vh;
    overflow-y: auto;
}

.settings-nav-label {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: var(--a-text-muted);
    padding: 0 1.25rem 0.6rem;
}

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.6rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--a-text-muted);
    text-decoration: none;
    transition: background 0.12s, color 0.12s;
    border-left: 3px solid transparent;
    position: relative;
}
.settings-nav-item:hover {
    background: rgba(0,0,0,0.03);
    color: var(--a-text);
    text-decoration: none;
}
.settings-nav-item.active {
    background: rgba(0,0,0,0.04);
    color: var(--a-accent);
    border-left-color: var(--a-accent);
    font-weight: 600;
}
.settings-nav-item.coming-soon {
    opacity: 0.55;
}
.settings-nav-item i {
    font-size: 0.9rem;
    width: 1rem;
    text-align: center;
    flex-shrink: 0;
}
.settings-nav-soon {
    margin-left: auto;
    font-size: 0.6rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    background: var(--a-border);
    color: var(--a-text-muted);
    padding: 0.1em 0.45em;
    border-radius: 100px;
}

.settings-content {
    padding: 2rem 2.5rem;
    min-width: 0;
}

@media (max-width: 768px) {
    .settings-layout {
        grid-template-columns: 1fr;
        margin: -1rem;
    }
    .settings-nav {
        border-right: none;
        border-bottom: 1px solid var(--a-border);
        padding: 0.75rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        position: static;
    }
    .settings-nav-label { display: none; }
    .settings-nav-item {
        border-left: none;
        border-radius: 6px;
        padding: 0.4rem 0.7rem;
        font-size: 0.8rem;
    }
    .settings-nav-item.active {
        border-left: none;
        background: rgba(0,0,0,0.06);
    }
    .settings-content { padding: 1.5rem 1rem; }
}
</style>
