<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/hooks.php';
require_once __DIR__ . '/includes/css-registry.php';

// Initialize Global Hook Storage
if (!isset($GLOBALS['registered_hooks'])) {
    $GLOBALS['registered_hooks'] = [];
}

// Auto-load Addons
$addons_path = __DIR__ . '/addons';
if (is_dir($addons_path)) {
    foreach (glob($addons_path . "/*.php") as $file) {
        if (basename($file) !== 'index.php') include_once $file;
    }
}

// Database Connection
$db = new PDO('sqlite:' . __DIR__ . '/db/cms.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get slug from URL
$slug = $_GET['slug'] ?? '';

// Fetch the post
$stmt = $db->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch settings for the header
$settings = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    $page = ['title' => 'Post Not Found'];
    
    // Pre-render 404 Content
    ob_start();
    if (file_exists('templates/page-404.php')) {
        include 'templates/page-404.php';
    } else {
        echo "<div class='container py-5'><h1>404</h1><p>The post you are looking for does not exist.</p></div>";
    }
    $page_content = ob_get_clean();

} else {
    // Post exists: Prepare data
    $page = ['title' => $post['title']];

    // --- START CAPTURING POST CONTENT ---
    // This allows the template to set sidebars/CSS via functions before header.php runs
    ob_start();
    include 'templates/post-single.php';
    $page_content = ob_get_clean();
    // --- END CAPTURING POST CONTENT ---
}

// 1. Load Header (Now has access to anything set inside the ob_start block)
include 'templates/header.php';

// 2. Output the captured content
echo $page_content;

// 3. Load Footer
include 'templates/footer.php';