<?php
// 1. Diagnostics & Dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('IS_INSTALLER', true);

if (!function_exists('get_site_settings')) {
    @include_once __DIR__ . '/functions.php';
}

if (session_status() === PHP_SESSION_NONE) session_start();

$db_path = __DIR__ . '/db/cms.db';
$error = null;
$installation_success = false;

// --- IONOS COMPATIBLE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_user'])) {
    $step = '3';
    
    try {
        if (!file_exists(__DIR__ . '/db')) @mkdir(__DIR__ . '/db', 0777, true);
        if (!file_exists(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0777, true);

        // Remove old DB if exists to start fresh
        if (file_exists($db_path)) unlink($db_path);

        $db = new PDO("sqlite:$db_path");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        $user = $_POST['admin_user'] ?: 'admin';
        $pass = password_hash($_POST['admin_pass'] ?: 'admin123', PASSWORD_DEFAULT);
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

        <?php elseif ($step == '2'): ?>
            <h6 class="fw-bold text-muted mb-3">Admin Configuration</h6>
            <?php if($error) echo "<div class='alert alert-danger small'>$error</div>"; ?>
            <form action="" method="POST">
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
            <a href="?step=2" class="btn btn-primary w-100 py-2 fw-bold <?php echo !$all_passed ? 'disabled' : ''; ?>">Initialize Core</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>