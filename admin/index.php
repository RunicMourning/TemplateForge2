<?php
session_start();
require_once '../functions.php';
require_once '../includes/hooks.php';
$GLOBALS['registered_hooks'] = [];
$db = new PDO('sqlite:../db/cms.db'); // Path must be correct relative to index.php
$settings = get_site_settings($db); // Now $settings is available everywhere!
ensure_user_schema($db);

$addons_path = __DIR__ . '/../addons';
if (is_dir($addons_path)) {
    foreach (glob($addons_path . '/*.php') as $file) {
        if (basename($file) !== 'index.php') include_once $file;
    }
}

// 1. Simple Login Check (Logic only)
if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['login'])) {
        $submitted_user = trim((string)($_POST['user'] ?? ''));

        if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_login')) {
            log_activity($db, 'SECURITY', 'Admin Login CSRF Blocked', "Username: " . ($submitted_user !== '' ? $submitted_user : '[empty]'));
            http_response_code(403);
            $error = 'Invalid request token. Please refresh and try again.';
            include 'views/login.php';
            exit;
        }
        $users_table_exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (!$users_table_exists || !$users_table_exists->fetchColumn()) {
            $error = 'User accounts are not initialized yet. Please run the installer.';
            include 'views/login.php';
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$submitted_user]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($_POST['pass'], $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            // ADD THIS LINE:
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'] ?? $user['username'];
            $_SESSION['permissions'] = json_decode((string)($user['permissions'] ?? '[]'), true) ?: [];
            $_SESSION['show_dashboard_alerts'] = true;

            // LOG THE LOGIN
            log_activity($db, 'AUTH', 'Admin Login', "User: " . $user['username']);
            
            header("Location: index.php"); exit;
        }
        log_activity($db, 'AUTH', 'Admin Login Failed', "Username: " . ($submitted_user !== '' ? $submitted_user : '[empty]'));
        $error = "Invalid credentials";
    }
    // Show login form if not logged in
    include 'views/login.php'; 
    exit;
}


// Hydrate display/permission session data for legacy sessions.
if (!isset($_SESSION['display_name']) || !isset($_SESSION['permissions'])) {
    $session_user = $db->prepare("SELECT username, display_name, permissions FROM users WHERE id = ?");
    $session_user->execute([$_SESSION['user_id']]);
    $session_row = $session_user->fetch(PDO::FETCH_ASSOC);
    if ($session_row) {
        $_SESSION['username'] = $session_row['username'];
        $_SESSION['display_name'] = $session_row['display_name'] ?? $session_row['username'];
        $_SESSION['permissions'] = json_decode((string)($session_row['permissions'] ?? '[]'), true) ?: [];
    }
}

// 2. Module Routing
$view = $_GET['view'] ?? 'dashboard';
$allowed_views = [
    'dashboard',
    'pages',
    'blog',
    'users',
    'settings',
    'navigation',
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
