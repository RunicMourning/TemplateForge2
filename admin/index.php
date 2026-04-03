<?php
session_start();
require_once '../functions.php';
require_once '../includes/hooks.php';
$GLOBALS['registered_hooks'] = [];
$db = new PDO('sqlite:../db/cms.db'); // Path must be correct relative to index.php
$settings = get_site_settings($db); // Now $settings is available everywhere!

$addons_path = __DIR__ . '/../addons';
if (is_dir($addons_path)) {
    foreach (glob($addons_path . '/*.php') as $file) {
        if (basename($file) !== 'index.php') include_once $file;
    }
}

// 1. Simple Login Check (Logic only)
if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['login'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_login')) {
            http_response_code(403);
            $error = 'Invalid request token. Please refresh and try again.';
            include 'views/login.php';
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$_POST['user']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($_POST['pass'], $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            // ADD THIS LINE:
            $_SESSION['username'] = $user['username']; 
            
            // LOG THE LOGIN
            log_activity($db, 'AUTH', 'Admin Login', "User: " . $user['username']);
            
            header("Location: index.php"); exit;
        }
        $error = "Invalid credentials";
    }
    // Show login form if not logged in
    include 'views/login.php'; 
    exit;
}

// 2. Module Routing
$view = $_GET['view'] ?? 'dashboard';
$allowed_views = [
    'dashboard',
    'pages',
    'blog',
    'settings',
    'navigation', // redirects to settings/navigation
    'users',      // redirects to settings/users
    'logs',
    'analytics'
];

if (!preg_match('/^[a-z_]+$/', $view) || !in_array($view, $allowed_views, true)) {
    $view = 'dashboard';
}

$module_path = "modules/{$view}.php";

include 'views/header.php'; // Admin look and feel

include $module_path;

include 'views/footer.php';
?>
