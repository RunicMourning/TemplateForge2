<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/hooks.php';
require_once __DIR__ . '/includes/module-loader.php';
require_once __DIR__ . '/includes/css-registry.php';

$GLOBALS['registered_hooks']   = [];
$GLOBALS['registered_filters'] = [];

// Legacy addons
$addons_path = __DIR__ . '/addons';
if (is_dir($addons_path)) {
    foreach (glob($addons_path . "/*.php") as $file) {
        if (basename($file) !== 'index.php') include_once $file;
    }
}

// Database + modules
$db = new PDO('sqlite:' . __DIR__ . '/db/cms.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$settings = get_site_settings($db);
load_modules($db, __DIR__ . '/modules');

// Routing
$slug = $_GET['slug'] ?? '';
$stmt = $db->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    $page = ['title' => 'Post Not Found'];
    ob_start();
    if (file_exists('templates/page-404.php')) {
        include 'templates/page-404.php';
    } else {
        echo "<div class='container'><h1>404</h1><p>Post not found.</p></div>";
    }
    $page_content = ob_get_clean();
} else {
    $page = ['title' => $post['title']];
    ob_start();
    include 'templates/post-single.php';
    $page_content = ob_get_clean();
}

include 'templates/header.php';
echo $page_content;
include 'templates/footer.php';
