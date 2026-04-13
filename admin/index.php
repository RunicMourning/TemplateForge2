<?php
session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();
require_once '../functions.php';
require_once '../includes/hooks.php';
require_once '../includes/module-loader.php';
$GLOBALS['registered_hooks']   = [];
$GLOBALS['registered_filters'] = [];
$db = new PDO('sqlite:../db/cms.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$settings = get_site_settings($db);

// Legacy addons (flat PHP files in /addons/)
$addons_path = __DIR__ . '/../addons';
if (is_dir($addons_path)) {
    foreach (glob($addons_path . '/*.php') as $file) {
        if (basename($file) !== 'index.php') include_once $file;
    }
}

// Load registered modules
load_modules($db, __DIR__ . '/../modules');

// 1. Simple Login Check (Logic only)
if (!isset($_SESSION['user_id'])) {
    $error = '';
    if (isset($_POST['login'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_login')) {
            session_regenerate_id(true);
            unset($_SESSION['_csrf_tokens']['admin_login']);
            $error = 'Your session expired. Please try again.';
            include 'views/login.php';
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$_POST['user']]);
        $user = $stmt->fetch();

        if ($user && password_verify($_POST['pass'], $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['user_password_hash'] = $user['password']; // bind session to credential state
            log_activity($db, 'AUTH', 'Admin Login', "User: " . $user['username']);
            header("Location: index.php"); exit;
        }
        $error = "Invalid credentials.";
    }
    include 'views/login.php';
    exit;
}

// 1b. Validate session against current DB state — prevents stale sessions surviving
//     DB resets, password changes, or user ID reuse after deletion.
$_session_user = null;
try {
    $stmt = $db->prepare("SELECT id, username, password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $_session_user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception) {}

$_session_invalid =
    !$_session_user ||
    $_session_user['username'] !== ($_SESSION['username'] ?? '') ||
    (isset($_SESSION['user_password_hash']) && !hash_equals($_session_user['password'], $_SESSION['user_password_hash']));

if ($_session_invalid) {
    // Wipe the stale session entirely and force re-login
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    $error = 'Your session has expired or is no longer valid. Please log in again.';
    include 'views/login.php';
    exit;
}

// 2. Module Routing
$view = $_GET['view'] ?? 'dashboard';
$allowed_views = [
    'dashboard',
    'pages',
    'blog',
    'wiki',
    'podcast',
    'settings',
    'navigation',
    'users',
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
