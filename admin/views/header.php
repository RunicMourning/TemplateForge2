<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title'] ?? 'Admin'); ?> &mdash; <?php echo htmlspecialchars($settings['site_name'] ?? 'TemplatForge'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/admin/admin.css">
    <link rel="stylesheet" href="/admin/admin-ui.css">
    <link rel="stylesheet" href="/admin/admin-components.css">
</head>
<body>

<!-- Mobile overlay -->
<div id="a-overlay"></div>

<!-- Sidebar -->
<nav id="a-sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-brand-name">
            <i class="bi bi-intersect"></i>
            <?php echo htmlspecialchars($settings['site_name'] ?? 'TemplatForge'); ?>
        </span>
        <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <?php
    $modules        = glob("modules/*.php");
    $all_slugs      = array_map(fn($m) => basename($m, ".php"), $modules);

    $categories = [
        'Overview'      => ['dashboard', 'analytics'],
        'Content'       => ['pages', 'blog', 'wiki', 'podcast'],
        'Configuration' => ['settings'],
        'System'        => ['logs'],
    ];

    $categorized = array_merge(...array_values($categories));
    $stub_modules = ['navigation', 'users'];
    $orphans     = array_diff($all_slugs, array_merge($categorized, $stub_modules));
    if (!empty($orphans)) $categories['System'] = array_merge($categories['System'], $orphans);

    $icons = [
        'dashboard'  => 'bi-speedometer2',
        'analytics'  => 'bi-graph-up',
        'pages'      => 'bi-files',
        'blog'       => 'bi-journal-text',
        'wiki'       => 'bi-journal-bookmark',
        'podcast'    => 'bi-mic',
        'categories' => 'bi-tags',
        'navigation' => 'bi-list',
        'settings'   => 'bi-gear',
        'users'      => 'bi-people',
        'logs'       => 'bi-terminal',
    ];

    $current_view    = $_GET['view']    ?? 'dashboard';
    $current_section = $_GET['section'] ?? 'site';

    // Settings sub-pages — mirrors $core_sections in settings.php
    $settings_sections = [
        'site'       => ['label' => 'Site Settings', 'icon' => 'bi-sliders'],
        'appearance' => ['label' => 'Appearance',    'icon' => 'bi-palette'],
        'navigation' => ['label' => 'Navigation',    'icon' => 'bi-list-ul'],
        'footer'     => ['label' => 'Footer',        'icon' => 'bi-layout-sidebar-reverse'],
        'podcast'    => ['label' => 'Podcast',       'icon' => 'bi-mic'],
        'wiki'       => ['label' => 'Wiki',          'icon' => 'bi-journal-bookmark'],
        'addons'     => ['label' => 'Addons',        'icon' => 'bi-puzzle'],
        'users'      => ['label' => 'Users',         'icon' => 'bi-people'],
    ];

    echo '<div class="sidebar-nav">';
    foreach ($categories as $group => $slugs):
        $existing = array_intersect($slugs, $all_slugs);
        if (empty($existing)) continue;
        echo '<div class="sidebar-section-label">' . $group . '</div>';
        foreach ($existing as $slug):
            $active = ($current_view === $slug) ? 'active' : '';
            $icon   = $icons[$slug] ?? 'bi-puzzle';
            $label  = ucfirst($slug);

            if ($slug === 'settings') {
                // Settings parent link — always shown, active when in settings
                $in_settings = ($current_view === 'settings');
                echo '<a href="index.php?view=settings&section=site" class="' . ($in_settings ? 'active' : '') . '">'
                   . '<i class="bi bi-gear"></i>Settings'
                   . '<i class="bi bi-chevron-' . ($in_settings ? 'down' : 'right') . ' sidebar-chevron"></i>'
                   . '</a>';

                // Sub-menu — only rendered when in settings view
                if ($in_settings) {
                    echo '<div class="sidebar-submenu">';
                    foreach ($settings_sections as $sec_slug => $sec) {
                        $sub_active = ($current_section === $sec_slug) ? 'active' : '';
                        echo '<a href="index.php?view=settings&section=' . $sec_slug . '" class="sidebar-subitem ' . $sub_active . '">'
                           . '<i class="bi ' . $sec['icon'] . '"></i>'
                           . $sec['label']
                           . '</a>';
                    }
                    echo '</div>';
                }
            } else {
                echo '<a href="index.php?view=' . $slug . '" class="' . $active . '">'
                   . '<i class="bi ' . $icon . '"></i>'
                   . $label
                   . '</a>';
            }
        endforeach;
    endforeach;
    echo '</div>';
    ?>
</nav>

<!-- Topbar -->
<header id="a-topbar">
    <button class="topbar-menu-btn" id="sidebarOpen" aria-label="Open menu">
        <i class="bi bi-list"></i>
    </button>

    <a href="../index.php" target="_blank" class="topbar-site-link">
        View Site <i class="bi bi-box-arrow-up-right"></i>
    </a>

    <div class="topbar-right">
        <div class="topbar-user" id="userMenuToggle">
            <i class="bi bi-person-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            <i class="bi bi-chevron-down" style="font-size:0.7rem; color:var(--a-text-muted);"></i>
            <div class="topbar-dropdown" id="userDropdown">
                <a href="index.php?view=settings&section=users"><i class="bi bi-gear"></i> Security Settings</a>
                <div class="divider"></div>
                <a href="logout.php" class="danger"><i class="bi bi-box-arrow-right"></i> Log out</a>
            </div>
        </div>
    </div>
</header>

<script>
(function(){
    // Sidebar open/close
    var sidebar  = document.getElementById('a-sidebar');
    var overlay  = document.getElementById('a-overlay');
    var openBtn  = document.getElementById('sidebarOpen');
    var closeBtn = document.getElementById('sidebarClose');

    function openSidebar(){
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar(){
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    if(openBtn)  openBtn.addEventListener('click', openSidebar);
    if(closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if(overlay)  overlay.addEventListener('click', closeSidebar);

    // User dropdown
    var userToggle   = document.getElementById('userMenuToggle');
    var userDropdown = document.getElementById('userDropdown');
    if(userToggle && userDropdown){
        userToggle.addEventListener('click', function(e){
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function(){
            userDropdown.classList.remove('open');
        });
    }
})();
</script>

<main id="a-main">
