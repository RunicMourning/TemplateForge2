<?php
/**
 * Main Index Controller - Corporate Version (Hardened)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Check for Installation first
if (!file_exists(__DIR__ . '/db/cms.db')) {
    header("Location: Install.php");
    exit;
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/hooks.php';
$GLOBALS['registered_hooks'] = [];

// 2. Load Addons
$addons_path = __DIR__ . '/addons';
if (is_dir($addons_path)) {
    foreach (glob($addons_path . "/*.php") as $file) {
        if (basename($file) !== 'index.php') include_once $file;
    }
}

try {
    // 3. Database Connection
    $db = new PDO('sqlite:' . __DIR__ . '/db/cms.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Load Settings (Safely)
    $settings = get_site_settings($db);

    if (!isset($_SESSION['username'])) track_visit($db);

    // 5. Routing Logic
    $slug = $_GET['pageslug'] ?? 'home';
    $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If table doesn't exist yet (Step 3 race condition), redirect to installer
    header("Location: Install.php?error=db_not_ready");
    exit;
}

$custom_template = __DIR__ . "/templates/page-{$slug}.php";

// 6. --- START CAPTURING PAGE CONTENT ---
ob_start(); 

if (!$page && !file_exists($custom_template)) {
    header("HTTP/1.0 404 Not Found");
    $page = ['title' => 'Page Not Found'];
    if (file_exists(__DIR__ . '/templates/page-404.php')) {
        include __DIR__ . '/templates/page-404.php';
    } else {
        echo "<div class='container py-5'><h1>404</h1><p>The requested page could not be found.</p></div>";
    }
} else {
    if (file_exists($custom_template)) {
        include $custom_template;
    } else {
        // Ensure page.php exists
        $default_template = __DIR__ . '/templates/page.php';
        if (file_exists($default_template)) {
            include $default_template;
        } else {
            echo "<div class='container py-5'><h1>" . htmlspecialchars($page['title'] ?? '') . "</h1>" . ($page['content'] ?? '') . "</div>";
        }
    }
}

$page_content = ob_get_clean(); 
// --- END CAPTURING PAGE CONTENT ---

// 7. Load Header
if (file_exists(__DIR__ . '/templates/header.php')) {
    include __DIR__ . '/templates/header.php';
}

// 8. Output content
echo $page_content;

// 9. Load Footer
if (file_exists(__DIR__ . '/templates/footer.php')) {
    include __DIR__ . '/templates/footer.php';
}
?>