<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page['title'] ?? 'Admin Panel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --topbar-height: 60px; }
        body { background-color: #f8f9fa; overflow-x: hidden; }
        #sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; background-color: #212529; transition: all 0.3s; }
        .nav-link { color: #adb5bd; font-weight: 500; }
        .nav-link:hover, .nav-link.active { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
        .sidebar-heading { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1rem; color: #6c757d; padding: 1.5rem 1rem 0.5rem; }
        main { margin-left: var(--sidebar-width); padding-top: var(--topbar-height); min-height: 100vh; }
        .top-bar { height: var(--topbar-height); margin-left: var(--sidebar-width); background: #fff; border-bottom: 1px solid #dee2e6; z-index: 99; }
        
        /* Icon Colors */
        #sidebar .bi-speedometer2 { color: #0dcaf0; }
        #sidebar .bi-files        { color: #ffca28; }
        #sidebar .bi-journal-text { color: #42a5f5; }
        #sidebar .bi-list         { color: #ab47bc; }
        #sidebar .bi-gear         { color: #9e9e9e; }
        #sidebar .bi-people       { color: #66bb6a; }
        #sidebar .bi-terminal     { color: #ef5350; }
        #sidebar i { width: 1.5rem; display: inline-block; text-align: center; margin-right: 8px; }
    </style>
</head>
<body>

<nav id="sidebar" class="d-flex flex-column">
    <div class="p-3">
        <a href="index.php" class="text-white text-decoration-none fs-4 fw-bold"><?php echo htmlspecialchars($settings['site_name']); ?> Admin</a>
    </div>
    <div class="nav flex-column flex-nowrap">
        <?php
        // 1. Scan for actual files
        $modules = glob("modules/*.php");
        $all_slugs = array_map(fn($m) => basename($m, ".php"), $modules);

        // 2. Define known categories
        $categories = [
            'Overview'      => ['dashboard', 'analytics'], // Added analytics here
            'Content'       => ['pages', 'blog', 'categories'],
            'Configuration' => ['navigation', 'settings'],
            'System'        => ['users', 'logs']
        ];

        // 3. Auto-Discovery: Find modules not in any category
        $categorized_slugs = [];
        foreach ($categories as $list) {
            $categorized_slugs = array_merge($categorized_slugs, $list);
        }
        
        $orphans = array_diff($all_slugs, $categorized_slugs);
        if (!empty($orphans)) {
            // Append orphan modules to the 'System' group
            $categories['System'] = array_merge($categories['System'], $orphans);
        }

        $icons = [
            'dashboard'  => 'bi-speedometer2', 
            'analytics'  => 'bi-graph-up', 
            'pages'      => 'bi-files', 
            'blog'       => 'bi-journal-text',
            'categories' => 'bi-tags',
            'navigation' => 'bi-list', 
            'settings'   => 'bi-gear', 
            'users'      => 'bi-people', 
            'logs'       => 'bi-terminal'
        ];

        // 4. Render the Navigation
        foreach ($categories as $groupName => $groupSlugs):
            // Only show the group if at least one module file exists
            $existingInGroup = array_intersect($groupSlugs, $all_slugs);
            if (empty($existingInGroup)) continue;

            echo '<div class="sidebar-heading">' . $groupName . '</div>';
            foreach ($existingInGroup as $slug):
                $isActive = (isset($_GET['view']) && $_GET['view'] == $slug) || (!isset($_GET['view']) && $slug == 'dashboard');
                $icon = $icons[$slug] ?? 'bi-puzzle'; // Default icon for unknown modules
                ?>
                <a href="index.php?view=<?php echo $slug; ?>" class="nav-link px-3 py-2 <?php echo $isActive ? 'active' : ''; ?>">
                    <i class="bi <?php echo $icon; ?> me-2"></i> <?php echo ucfirst($slug); ?>
                </a>
            <?php endforeach;
        endforeach; ?>
    </div>
</nav>

<header class="top-bar fixed-top d-flex align-items-center px-4 justify-content-between">
    <div>
        <a href="../index.php" target="_blank" class="btn btn-outline-secondary btn-sm">
            View Site <i class="bi bi-box-arrow-up-right ms-1"></i>
        </a>
    </div>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-dark text-decoration-none text-capitalize dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle pe-2"></i> <strong><?php echo $_SESSION['username'] ?? 'Admin'; ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><a class="dropdown-item" href="index.php?view=users"><i class="bi bi-gear me-2"></i> Security Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log out</a></li>
        </ul>
    </div>
</header>

<main>
    <div class="container-fluid p-4">