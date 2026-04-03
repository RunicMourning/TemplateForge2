<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title'] ?? 'Admin'); ?> &mdash; <?php echo htmlspecialchars($settings['site_name'] ?? 'TemplatForge'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/admin/admin.css">
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
        'Content'       => ['pages', 'blog'],
        'Configuration' => ['settings'],
        'System'        => ['logs'],
    ];

    $categorized = array_merge(...array_values($categories));
    $orphans     = array_diff($all_slugs, $categorized);
    if (!empty($orphans)) $categories['System'] = array_merge($categories['System'], $orphans);

    $icons = [
        'dashboard'  => 'bi-speedometer2',
        'analytics'  => 'bi-graph-up',
        'pages'      => 'bi-files',
        'blog'       => 'bi-journal-text',
        'categories' => 'bi-tags',
        'navigation' => 'bi-list',
        'settings'   => 'bi-gear',
        'users'      => 'bi-people',
        'logs'       => 'bi-terminal',
    ];

    $current_view = $_GET['view'] ?? 'dashboard';
    $current_section = $_GET['section'] ?? '';

    echo '<div class="sidebar-nav">';
    foreach ($categories as $group => $slugs):
        $existing = array_intersect($slugs, $all_slugs);
        if (empty($existing)) continue;
        echo '<div class="sidebar-section-label">' . $group . '</div>';
        foreach ($existing as $slug):
            $active = ($current_view === $slug) ? 'active' : '';
            $icon   = $icons[$slug] ?? 'bi-puzzle';
            $label  = ucfirst($slug);
            // Settings shows sub-section hint
            $suffix = '';
            if ($slug === 'settings' && $current_view === 'settings' && $current_section) {
                $section_labels = [
                    'site'       => 'Site',
                    'appearance' => 'Appearance',
                    'navigation' => 'Navigation',
                    'footer'     => 'Footer',
                    'podcast'    => 'Podcast',
                    'addons'     => 'Addons',
                    'users'      => 'Users',
                ];
                $suffix = isset($section_labels[$current_section])
                    ? ' <span class="sidebar-subsection">' . $section_labels[$current_section] . '</span>'
                    : '';
            }
            echo '<a href="index.php?view=' . $slug . '" class="' . $active . '">'
               . '<i class="bi ' . $icon . '"></i>'
               . $label . $suffix
               . '</a>';
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
