<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (function_exists('run_hook')) run_hook('head_top'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?> | <?php echo htmlspecialchars($page['title'] ?? 'Home'); ?></title>

    <?php
    require_once __DIR__ . '/../includes/theme-registry.php';
    $active_theme  = $settings['active_theme'] ?? 'broadsheet-light';
    // Migrate legacy theme slugs (no -light/-dark suffix) to light variant
    if (!preg_match('/-(light|dark)$/', $active_theme)) {
        $active_theme .= '-light';
    }
    if (!tf_is_valid_theme($active_theme)) $active_theme = 'broadsheet-light';
    $tf_registry   = tf_get_theme_registry();
    ?>

    <!-- Bootstrap Icons (no Bootstrap CSS/JS) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Core: base reset, typography, layout primitives -->
    <link rel="stylesheet" href="/themes/core-base.css">
    <!-- Core: components (nav, cards, buttons, forms, hero) -->
    <link rel="stylesheet" href="/themes/core-components.css">
    <!-- Core: content (footer, pagination, alerts, tables, utilities, modal) -->
    <link rel="stylesheet" href="/themes/core-content.css">
    <!-- Active theme (variables + layout overrides) -->
    <link id="tf-theme-stylesheet" rel="stylesheet" href="/themes/<?php echo $active_theme; ?>.css">

    <style>
        <?php if (function_exists('run_hook')) run_hook('custom_inline_css'); ?>
    </style>

    <?php if (function_exists('run_hook')) run_hook('head_bottom'); ?>
</head>
<body>

<?php
$menu_query = $db->query("SELECT * FROM navigation ORDER BY sort_order ASC");
$menu_items = $menu_query->fetchAll(PDO::FETCH_ASSOC);
$current_page = basename(strtok($_SERVER['REQUEST_URI'], '?'));
if ($current_page === '' || $current_page === 'index.php') $current_page = 'home.html';
?>

<nav class="site-nav">
    <div class="container">
        <div class="nav-inner">
            <a class="nav-brand" href="index.php">
                <i class="bi bi-intersect"></i>
                <?php echo htmlspecialchars($settings['site_name']); ?>
            </a>

            <ul class="nav-links">
                <?php foreach ($menu_items as $item):
                    $active = ($current_page === $item['url']) ? 'active' : '';
                ?>
                    <li><a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $active; ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
                <?php endforeach; ?>
                <?php if (function_exists('run_hook')) run_hook('navbar_end'); ?>
            </ul>

            <div class="nav-search">
                <i class="bi bi-search nav-search-icon"></i>
                <form action="/search.html" method="GET">
                    <input type="search" name="q" placeholder="Search&hellip;" aria-label="Search">
                </form>
            </div>

            <button class="nav-theme-btn" id="themeToggle" aria-label="Change theme" title="Change theme">
                <i class="bi bi-palette"></i>
            </button>

            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>
    <div class="nav-drawer" id="navDrawer">
        <?php foreach ($menu_items as $item):
            $active = ($current_page === $item['url']) ? 'active' : '';
        ?>
            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $active; ?>"><?php echo htmlspecialchars($item['label']); ?></a>
        <?php endforeach; ?>
        <div class="nav-drawer-search">
            <form action="/search.html" method="GET">
                <input type="search" name="q" placeholder="Search&hellip;" aria-label="Search">
            </form>
        </div>
    </div>
</nav>

<script>
(function(){
    var t=document.getElementById('navToggle'), d=document.getElementById('navDrawer');
    if(t&&d){ t.addEventListener('click',function(){ var o=d.classList.toggle('open'); t.setAttribute('aria-expanded',o); t.querySelector('i').className=o?'bi bi-x-lg':'bi bi-list'; }); }
})();
</script>


<?php include __DIR__ . '/../includes/theme-switcher-modal.php'; ?>


<?php if (function_exists('run_hook')) run_hook('content_before'); ?>

<div class="container">
    <div class="site-layout">

        <aside class="site-aside">
<?php
global $custom_sidebar_content;
if (!empty($custom_sidebar_content)) {
    echo '<div class="widget">' . $custom_sidebar_content . '</div>';
}
$sidebar_path = __DIR__ . '/../sidebars/*.php';
$widgets = glob($sidebar_path);
foreach ($widgets as $widget) {
    echo '<div class="widget">';
    include $widget;
    echo '</div>';
}
?>
        </aside>

        <main class="site-main">
            <?php if (function_exists('run_hook')) run_hook('content_start'); ?>
