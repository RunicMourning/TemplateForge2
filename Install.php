<?php
// 1. Diagnostics & Dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('IS_INSTALLER', true);

if (!function_exists('get_site_settings')) {
    @include_once __DIR__ . '/functions.php';
}

if (session_status() === PHP_SESSION_NONE) session_start();

function hash_equals_safe(string $known, string $user): bool {
    if (function_exists('hash_equals')) {
        return hash_equals($known, $user);
    }
    if (strlen($known) !== strlen($user)) return false;
    $res = 0;
    for ($i = 0; $i < strlen($known); $i++) {
        $res |= ord($known[$i]) ^ ord($user[$i]);
    }
    return $res === 0;
}

$db_path = __DIR__ . '/db/cms.db';
$lock_file = __DIR__ . '/admin/lock';
$error = null;
$installation_success = false;
$installer_locked = file_exists($lock_file);
$app_env = strtolower((string) getenv('APP_ENV'));
$is_production = in_array($app_env, ['prod', 'production'], true);
$allow_production_installer = getenv('ALLOW_INSTALLER_IN_PRODUCTION') === '1';
$setup_token = trim((string) getenv('INSTALLER_SETUP_TOKEN'));
$provided_setup_token = trim((string) ($_POST['setup_token'] ?? $_GET['setup_token'] ?? ''));

if (empty($_SESSION['installer_csrf'])) {
    $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
}

$has_valid_setup_token = $setup_token !== '' && $provided_setup_token !== '' && hash_equals_safe($setup_token, $provided_setup_token);

if ($is_production && !$allow_production_installer) {
    $installer_locked = true;
    $error = 'Installer is disabled in production. Set ALLOW_INSTALLER_IN_PRODUCTION=1 only for controlled setup windows.';
}

// --- IONOS COMPATIBLE LOGIC ---
if ($installer_locked) {
    $step = '1';
    if ($error === null) {
        $error = 'Installer is locked. Remove admin/lock only if you intentionally need to reinstall.';
    }
} elseif ($setup_token === '') {
    $step = '1';
    $error = 'Installer token is not configured. Set INSTALLER_SETUP_TOKEN in your environment before continuing.';
} elseif (!$has_valid_setup_token) {
    $step = '1';
    $error = 'Invalid or missing setup token. Provide the correct one-time installer token to continue.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_user'])) {
    $step = '3';
    
    try {
        if (!isset($_POST['installer_csrf']) || !hash_equals_safe($_SESSION['installer_csrf'], (string) $_POST['installer_csrf'])) {
            throw new RuntimeException('Invalid installer request token (CSRF check failed).');
        }

        if (!file_exists(__DIR__ . '/db')) @mkdir(__DIR__ . '/db', 0777, true);
        if (!file_exists(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0777, true);

        // Refuse destructive reinstall if an existing database is present.
        if (file_exists($db_path) && filesize($db_path) > 0) {
            throw new RuntimeException('Existing database detected. Destructive reinstall is disabled. Remove DB manually after backup if intentional.');
        }

        $db = new PDO("sqlite:$db_path");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA foreign_keys = ON');

        // --- 1. Schema Creation (Old + New Combined) ---
        $db->exec("CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, content TEXT)");
        $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT)");
        $db->exec("CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)");
        $db->exec("CREATE TABLE navigation (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT, url TEXT, css_class TEXT, css_id TEXT, sort_order INTEGER DEFAULT 0)");
        $db->exec("CREATE TABLE logs (id INTEGER PRIMARY KEY AUTOINCREMENT, category TEXT, event TEXT, details TEXT, user TEXT, ip TEXT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, content TEXT, excerpt TEXT, category TEXT, author TEXT, status TEXT DEFAULT 'published', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL)");
		$db->exec("CREATE TABLE IF NOT EXISTS contact_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, sender_name TEXT NOT NULL, sender_email TEXT NOT NULL, subject TEXT NOT NULL, message TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        
        // Analytics with Performance Indexes
        $db->exec("CREATE TABLE analytics (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            visitor_id TEXT, 
            page_url TEXT, 
            referrer TEXT, 
            browser TEXT, 
            os TEXT, 
            device TEXT, 
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $db->exec("CREATE INDEX idx_analytics_ts ON analytics(timestamp)");
        $db->exec("CREATE INDEX idx_analytics_vid ON analytics(visitor_id)");

        // --- 2. Admin Creation ---
        $user = trim((string) ($_POST['admin_user'] ?? 'admin')) ?: 'admin';
        $raw_pass = (string) ($_POST['admin_pass'] ?? '');
        if (strlen($raw_pass) < 12) {
            throw new RuntimeException('Admin password must be at least 12 characters long.');
        }
        $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute([$user, $pass]);

        // --- 3. Seeding Content (Your Original Content) ---
        $db->exec("INSERT INTO settings (key, value) VALUES ('site_name', 'TemplateForge2'), ('footer_text', '&copy\; " . date('Y') . "')");
        $db->exec("INSERT INTO navigation (label, url, sort_order) VALUES ('Home', 'home.html', 0), ('Blog', 'blog.php', 1), ('Privacy', 'privacy.html', 2)");
        $db->exec("INSERT INTO categories (name) VALUES ('General'), ('News'), ('Tutorial')");
        
        // Default Pages
        $stmt = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (?,?,?)");
        $stmt->execute(['Welcome', 'home', '<h2>System Active</h2><p>Welcome to your newly installed extensible CMS. You shouldn\'t see this because it loads a PHP file instead.</p>']);
        $stmt->execute(['Contact Us', 'contact', '<h2>Contact Us</h2><p>Contact us form here. You shouldn\'t see this because it loads a PHP file instead.</p>']);
        $stmt->execute(['Privacy Policy', 'privacy', '<h2>Privacy Policy</h2><p>Our commitment to your privacy. This page is automatically enhanced by active addons. You shouldn\'t see this because it loads a PHP file instead.</p>']);
        $stmt->execute(['Page Not Found', '404', '<h2>Oops!</h2><p>The page you requested could not be found.</p>']);

        // Initial Blog Post
        $db->prepare("INSERT INTO posts (title, slug, category, content, excerpt, author) VALUES (?,?,?,?,?,?)")
           ->execute(['First Post', 'hello-world', 'General', 'Welcome to your blog.', 'Initial post...', $user]);

        if (@file_put_contents($lock_file, "Installed on " . date('c') . PHP_EOL . "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . PHP_EOL) === false) {
            throw new RuntimeException('Unable to create installer lock file at admin/lock.');
        }

        $installation_success = true;

    } catch (Exception $e) {
        $error = "Installation Error: " . $e->getMessage();
        $step = '2'; 
    }
} else {
    $step = $_GET['step'] ?? '1';
}

// Requirement Checks
$requirements = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO SQLite' => extension_loaded('pdo_sqlite'),
    'Folder /db' => is_writable(__DIR__ . '/db') || is_writable(__DIR__),
    'Folder /uploads' => is_writable(__DIR__ . '/uploads') || is_writable(__DIR__)
];
$all_passed = !in_array(false, $requirements);
$safe_setup_token = htmlspecialchars($provided_setup_token, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMS Core | Deployment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; min-height: 100vh; display: flex; align-items: center; font-family: 'Inter', sans-serif; }
        .install-card { border-radius: 1rem; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.05); overflow: hidden; width: 100%; max-width: 450px; margin: auto; }
        .gradient-header { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); padding: 2rem; color: white; text-align: center; }
    </style>
</head>
<body>

<div class="card install-card">
    <div class="gradient-header">
        <div class="small text-uppercase fw-bold opacity-75 mb-1">Step <?php echo ($installation_success) ? '3' : $step; ?> of 3</div>
        <h4 class="fw-bold mb-0">System Deployment</h4>
    </div>
    
    <div class="card-body p-4">
        <?php if ($error && !$installer_locked): ?>
            <div class="alert alert-danger small"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
        <?php endif; ?>
        <?php if ($installation_success): ?>
            <div class="text-center py-3">
                <i class="bi bi-check-circle-fill text-success display-4"></i>
                <h5 class="mt-3 fw-bold">Installed Successfully!</h5>
                <p class="text-muted small">Content and analytics are ready. Delete <code>install.php</code> now.</p>
                <div class="d-grid gap-2 mt-4">
                    <a href="/admin/" class="btn btn-primary">Login to Admin</a>
                    <a href="index.php" class="btn btn-outline-secondary">View Site</a>
                </div>
            </div>

        <?php elseif ($installer_locked): ?>
            <div class="alert alert-warning small mb-0">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>

        <?php elseif ($step == '2'): ?>
            <h6 class="fw-bold text-muted mb-3">Admin Configuration</h6>
            <form action="" method="POST">
                <input type="hidden" name="setup_token" value="<?php echo $safe_setup_token; ?>">
                <input type="hidden" name="installer_csrf" value="<?php echo htmlspecialchars($_SESSION['installer_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Master Username</label>
                    <input type="text" name="admin_user" class="form-control" value="admin" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Master Password</label>
                    <input type="password" name="admin_pass" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Finalize Installation</button>
            </form>

        <?php else: ?>
            <h6 class="fw-bold text-muted mb-3">Pre-Flight Checklist</h6>
            <ul class="list-group mb-4 border">
                <?php foreach($requirements as $lbl => $pass): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center small">
                    <?php echo $lbl; ?>
                    <i class="bi <?php echo $pass ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'; ?>"></i>
                </li>
                <?php endforeach; ?>
            </ul>
            <form method="GET" class="mt-3">
                <input type="hidden" name="step" value="2">
                <label class="form-label small fw-bold">Installer Setup Token</label>
                <input type="password" name="setup_token" class="form-control mb-2" placeholder="Required" required>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold <?php echo !$all_passed ? 'disabled' : ''; ?>">Initialize Core</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
