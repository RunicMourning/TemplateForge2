<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (function_exists('run_hook')) run_hook('head_top'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?> | <?php echo htmlspecialchars($page['title']); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --corp-blue: #0d6efd;
            --corp-dark: #212529;
            --corp-gray: #6c757d;
        }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            color: var(--corp-dark);
            background-color: #eeeeee;
        }
        /* Corporate Nav Styling */
        .navbar { transition: all 0.3s ease; }
        .navbar-brand { font-weight: 800; letter-spacing: -0.5px; color: var(--corp-dark) !important; }
        .nav-link { 
            font-size: 0.9rem; 
            font-weight: 500; 
            color: var(--corp-gray) !important; 
            padding: 0.5rem 1rem !important;
            transition: color 0.2s;
        }
        .nav-link:hover { color: var(--corp-blue) !important; }
        .nav-link.active { color: var(--corp-dark) !important; font-weight: 700; }
        
        /* Sidebar Widget Styling */
        .widget-title { 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-weight: 700; 
            color: var(--corp-gray); 
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
        }
        .widget-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #eee;
            margin-left: 10px;
        }
		
    /* Base Corporate Styles */
    body { font-family: 'Inter', sans-serif; }
    
    <?php if (function_exists('run_hook')) run_hook('custom_inline_css'); ?>
</style>

    <?php if (function_exists('run_hook')) run_hook('head_bottom'); ?>
</head>
<body>

<?php
$menu_query = $db->query("SELECT * FROM navigation ORDER BY sort_order ASC");
$menu_items = $menu_query->fetchAll(PDO::FETCH_ASSOC);

$current_page = basename($_SERVER['REQUEST_URI']);
if ($current_page == "" || $current_page == "index.php") $current_page = "home.html";
?>

<nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="bi bi-intersect text-primary me-2"></i>
            <?php echo htmlspecialchars($settings['site_name']); ?>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto me-4">
    <?php foreach ($menu_items as $item): 
        $active_class = ($current_page == $item['url']) ? 'active' : '';
    ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_class; ?>" href="<?php echo $item['url']; ?>">
                <?php echo htmlspecialchars($item['label']); ?>
            </a>
        </li>
    <?php endforeach; ?>

<?php if (function_exists('run_hook')) run_hook('navbar_end'); ?>
</ul>

            <form class="d-flex" action="/search.html" method="GET">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input class="form-control bg-light border-start-0 ps-0" type="search" name="q" placeholder="Search insights..." aria-label="Search">
                </div>
            </form>
        </div>
    </div>
</nav>

<?php if (function_exists('run_hook')) run_hook('content_before'); ?>

<div class="container py-5">
    <div class="row g-lg-5">
        <aside class="col-lg-3 order-2 order-lg-1">
            <div class="" style="top: 100px;">
<?php
// 1. Check for page-specific content first
global $custom_sidebar_content;
if (!empty($custom_sidebar_content)) {
    echo '<div class="mb-5 page-specific-sidebar">';
    echo $custom_sidebar_content;
    echo '</div>';
}

// 2. Load the standard widgets as usual
$sidebar_path = __DIR__ . '/../sidebars/*.php';
$widgets = glob($sidebar_path);
foreach ($widgets as $widget) {
    echo '<div class="widget mb-5">';
    include $widget;
    echo '</div>';
}
?>
            </div>
        </aside>

        <main class="col-lg-9 order-1 order-lg-2 mb-5">
            <?php if (function_exists('run_hook')) run_hook('content_start'); ?>