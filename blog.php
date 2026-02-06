<?php
// 1. Diagnostics & Dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('IS_INSTALLER', true);

session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/hooks.php';
$GLOBALS['registered_hooks'] = [];

// Load CSS Registry early so queue_css() is available to addons
require_once __DIR__ . '/includes/css-registry.php';

// Auto-load Addons
$addons_path = __DIR__ . '/addons';
if (is_dir($addons_path)) {
    foreach (glob($addons_path . "/*.php") as $file) {
        if (basename($file) !== 'index.php') include_once $file;
    }
}

$db = new PDO('sqlite:db/cms.db');

// --- 1. ROUTING & FILTERING LOGIC ---
$category = $_GET['category'] ?? null;
$author   = $_GET['author'] ?? null;

if ($category) {
    // Filter by Category
    $stmt = $db->prepare("SELECT * FROM posts WHERE category = ? AND status = 'published' ORDER BY created_at DESC");
    $stmt->execute([$category]);
    $posts = $stmt->fetchAll();
    $page = ['title' => 'Category: ' . htmlspecialchars($category)];
} elseif ($author) {
    // Filter by Author
    $stmt = $db->prepare("SELECT * FROM posts WHERE author = ? AND status = 'published' ORDER BY created_at DESC");
    $stmt->execute([$author]);
    $posts = $stmt->fetchAll();
    $page = ['title' => 'Posts by ' . htmlspecialchars($author)];
} else {
    // Standard Blog View
    $posts = $db->query("SELECT * FROM posts WHERE status = 'published' ORDER BY created_at DESC")->fetchAll();
    $page = ['title' => 'Blog'];
}

// Fetch site settings
$settings = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. --- START CAPTURING PAGE CONTENT ---
// We run the template first so it can set the $custom_sidebar_content variable
ob_start(); 
include 'templates/blog.php';
$page_content = ob_get_clean(); 
// --- END CAPTURING PAGE CONTENT ---

// 3. Load Header (Sees the captured sidebar and dynamic page title)
include 'templates/header.php';

// 4. Output the filtered content
echo $page_content;

// 5. Load Footer
include 'templates/footer.php';