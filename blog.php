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

$db = new PDO('sqlite:' . __DIR__ . '/db/cms.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$settings = get_site_settings($db);
load_modules($db, __DIR__ . '/modules');

// Routing & filtering
$category = $_GET['category'] ?? null;
$author   = $_GET['author']   ?? null;

if ($category) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE category = ? AND status = 'published' ORDER BY created_at DESC");
    $stmt->execute([$category]);
    $posts = $stmt->fetchAll();
    $page  = ['title' => 'Category: ' . htmlspecialchars($category)];
} elseif ($author) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE author = ? AND status = 'published' ORDER BY created_at DESC");
    $stmt->execute([$author]);
    $posts = $stmt->fetchAll();
    $page  = ['title' => 'Posts by ' . htmlspecialchars($author)];
} else {
    $posts = $db->query("SELECT * FROM posts WHERE status = 'published' ORDER BY created_at DESC")->fetchAll();
    $page  = ['title' => 'Blog'];
}

ob_start();
include 'templates/blog.php';
$page_content = ob_get_clean();

include 'templates/header.php';
echo $page_content;
include 'templates/footer.php';
