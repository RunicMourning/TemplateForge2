<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('IS_INSTALLER', true);

if (session_status() === PHP_SESSION_NONE) session_start();

function hash_equals_safe(string $known, string $user): bool {
    if (function_exists('hash_equals')) return hash_equals($known, $user);
    if (strlen($known) !== strlen($user)) return false;
    $res = 0;
    for ($i = 0; $i < strlen($known); $i++) $res |= ord($known[$i]) ^ ord($user[$i]);
    return $res === 0;
}

// ── Installer config ──────────────────────────────────────────────────────────

$db_path   = __DIR__ . '/db/cms.db';
$lock_file = __DIR__ . '/admin/lock';
$error     = null;
$installation_success = false;
$installer_locked     = file_exists($lock_file);
$app_env              = strtolower((string) getenv('APP_ENV'));
$is_production        = in_array($app_env, ['prod', 'production'], true);
$allow_production     = getenv('ALLOW_INSTALLER_IN_PRODUCTION') === '1';
$setup_token          = trim((string) getenv('INSTALLER_SETUP_TOKEN'));
$provided_token       = trim((string) ($_POST['setup_token'] ?? $_GET['setup_token'] ?? ''));

if (empty($_SESSION['installer_csrf'])) {
    $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
}

// Dev auto-auth
if ($setup_token === '' && !$is_production) {
    $setup_token = 'development-token';
    $_SESSION['installer_setup_authorized'] = true;
}

$session_auth     = !empty($_SESSION['installer_setup_authorized']);
$has_valid_token  = $setup_token !== '' && $provided_token !== '' && hash_equals_safe($setup_token, $provided_token);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enter_setup_token'])) {
    if ($has_valid_token) {
        $_SESSION['installer_setup_authorized'] = true;
        $session_auth = true;
    } else {
        unset($_SESSION['installer_setup_authorized']);
        $session_auth = false;
        $error = 'Invalid setup token.';
    }
}

if ($is_production && !$allow_production) {
    $installer_locked = true;
    $error = 'Installer is disabled in production.';
}

// ── Requirements ──────────────────────────────────────────────────────────────

$php_ver = PHP_VERSION;
$sqlite_ver = class_exists('SQLite3') ? SQLite3::version()['versionString'] : 'n/a';
$pdo_sqlite = extension_loaded('pdo_sqlite');
$db_dir_ok  = is_dir(__DIR__ . '/db')      ? is_writable(__DIR__ . '/db')      : is_writable(__DIR__);
$up_dir_ok  = is_dir(__DIR__ . '/uploads') ? is_writable(__DIR__ . '/uploads') : is_writable(__DIR__);

$requirements = [
    [
        'label'  => 'PHP Version',
        'detail' => 'PHP ' . $php_ver . ' (requires 8.0+)',
        'pass'   => version_compare($php_ver, '8.0.0', '>='),
    ],
    [
        'label'  => 'PDO SQLite',
        'detail' => $pdo_sqlite ? 'SQLite ' . $sqlite_ver : 'Extension not loaded',
        'pass'   => $pdo_sqlite,
    ],
    [
        'label'  => 'JSON Extension',
        'detail' => extension_loaded('json') ? 'Loaded' : 'Not available',
        'pass'   => extension_loaded('json'),
    ],
    [
        'label'  => 'Mbstring Extension',
        'detail' => extension_loaded('mbstring') ? 'Loaded' : 'Not available',
        'pass'   => extension_loaded('mbstring'),
    ],
    [
        'label'  => 'Database Directory (/db)',
        'detail' => is_dir(__DIR__ . '/db') ? 'Writable' : 'Will be created',
        'pass'   => $db_dir_ok,
    ],
    [
        'label'  => 'Uploads Directory (/uploads)',
        'detail' => is_dir(__DIR__ . '/uploads') ? 'Writable' : 'Will be created',
        'pass'   => $up_dir_ok,
    ],
    [
        'label'  => 'Session Support',
        'detail' => session_status() !== PHP_SESSION_DISABLED ? 'Active' : 'Disabled',
        'pass'   => session_status() !== PHP_SESSION_DISABLED,
    ],
    [
        'label'  => 'Clean Install',
        'detail' => file_exists($db_path) && filesize($db_path) > 0 ? 'Existing DB detected' : 'Ready',
        'pass'   => !(file_exists($db_path) && filesize($db_path) > 0),
    ],
];
$all_passed = !in_array(false, array_column($requirements, 'pass'));

// ── Theme discovery ───────────────────────────────────────────────────────────

function installer_get_themes(string $dir): array {
    $themes = [];
    foreach (glob($dir . '/themes/*.css') as $path) {
        $name = basename($path, '.css');
        if (in_array($name, ['core', 'core-base', 'core-components', 'core-content'], true)) continue;
        $head = file_get_contents($path, false, null, 0, 600);
        preg_match('/@tf-label:\s*(.+)/i',   $head, $lm);
        preg_match('/@tf-variant:\s*(.+)/i', $head, $vm);
        preg_match('/@tf-colors:\s*(.+)/i',  $head, $cm);
        preg_match('/@tf-group:\s*(.+)/i',   $head, $gm);
        $label   = isset($lm[1]) ? trim($lm[1]) : $name;
        $variant = isset($vm[1]) ? trim($vm[1]) : '';
        $colors  = isset($cm[1]) ? array_map('trim', explode(',', $cm[1])) : [];
        $group   = isset($gm[1]) ? trim($gm[1]) : $label;
        $themes[$name] = compact('name', 'label', 'variant', 'colors', 'group');
    }
    uasort($themes, fn($a, $b) => [$a['group'], $a['variant']] <=> [$b['group'], $b['variant']]);
    return $themes;
}

$themes = installer_get_themes(__DIR__);

// ── Installation ──────────────────────────────────────────────────────────────

if (!$installer_locked && ($session_auth || $has_valid_token) && isset($_POST['admin_user'])) {
    $step = '3';
    try {
        if (!hash_equals_safe($_SESSION['installer_csrf'], (string)($_POST['installer_csrf'] ?? ''))) {
            throw new RuntimeException('CSRF check failed. Please try again.');
        }

        $user        = trim((string)($_POST['admin_user'] ?? 'admin')) ?: 'admin';
        $raw_pass    = (string)($_POST['admin_pass'] ?? '');
        $site_name   = trim((string)($_POST['site_name'] ?? 'TemplateForge2')) ?: 'TemplateForge2';
        $active_theme = preg_replace('/[^a-z0-9-]/', '', (string)($_POST['active_theme'] ?? 'broadsheet-light'));
        if (!isset($themes[$active_theme])) $active_theme = 'broadsheet-light';

        if (strlen($raw_pass) < 12) {
            throw new RuntimeException('Password must be at least 12 characters.');
        }

        if (!file_exists(__DIR__ . '/db'))      @mkdir(__DIR__ . '/db',      0777, true);
        if (!file_exists(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0777, true);

        if (file_exists($db_path) && filesize($db_path) > 0) {
            throw new RuntimeException('Existing database detected. Remove it manually before reinstalling.');
        }

        $db = new PDO("sqlite:$db_path");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA foreign_keys = ON');

        // ── Schema ────────────────────────────────────────────────────────────
        $db->exec("CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, content TEXT)");
        $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT)");
        $db->exec("CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)");
        $db->exec("CREATE TABLE navigation (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT, url TEXT, css_class TEXT, css_id TEXT, sort_order INTEGER DEFAULT 0)");
        $db->exec("CREATE TABLE logs (id INTEGER PRIMARY KEY AUTOINCREMENT, category TEXT, event TEXT, details TEXT, user TEXT, ip TEXT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, content TEXT, excerpt TEXT, category TEXT, author TEXT, status TEXT DEFAULT 'published', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL)");
        $db->exec("CREATE TABLE social_links (id INTEGER PRIMARY KEY AUTOINCREMENT, platform TEXT NOT NULL, value TEXT NOT NULL, sort_order INTEGER DEFAULT 0)");
        $db->exec("CREATE TABLE footer_links (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT NOT NULL, url TEXT NOT NULL, sort_order INTEGER DEFAULT 0)");
        $db->exec("CREATE TABLE IF NOT EXISTS contact_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, sender_name TEXT NOT NULL, sender_email TEXT NOT NULL, subject TEXT NOT NULL, message TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE IF NOT EXISTS modules (id TEXT PRIMARY KEY, name TEXT NOT NULL, version TEXT NOT NULL DEFAULT '0.0.0', description TEXT NOT NULL DEFAULT '', enabled INTEGER NOT NULL DEFAULT 1, installed_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE analytics (id INTEGER PRIMARY KEY AUTOINCREMENT, visitor_id TEXT, session_id TEXT, page_url TEXT, entry_page TEXT, referrer TEXT, browser TEXT, os TEXT, device TEXT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE INDEX idx_analytics_ts  ON analytics(timestamp)");
        $db->exec("CREATE INDEX idx_analytics_vid ON analytics(visitor_id)");
        $db->exec("CREATE INDEX idx_analytics_sid ON analytics(session_id)");
        $db->exec("CREATE TABLE IF NOT EXISTS wiki_entries (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT NOT NULL UNIQUE, title TEXT NOT NULL, entry_type TEXT NOT NULL DEFAULT 'concept' CHECK(entry_type IN ('character','place','faction','concept','creature','artifact','event')), body TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published')), reveal_chapter_id INTEGER DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

        // ── Admin user ────────────────────────────────────────────────────────
        $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)")
           ->execute([$user, password_hash($raw_pass, PASSWORD_DEFAULT)]);

        // ── Settings ──────────────────────────────────────────────────────────
        $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute(['site_name',    $site_name]);
        $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute(['footer_text',  '&copy; ' . date('Y')]);
        $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute(['active_theme', $active_theme]);
        $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute(['wiki_slug_prefix', 'wiki']);
        $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute(['wiki_title',    'Wiki']);
        $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)")->execute(['wiki_types',   'character,place,faction,concept,creature,artifact,event']);

        // ── Navigation ────────────────────────────────────────────────────────
        $db->exec("INSERT INTO navigation (label, url, sort_order) VALUES
            ('Home',    '/home.html', 0),
            ('Blog',    '/blog.html', 1),
            ('Wiki',    '/wiki',      2),
            ('Privacy', '/privacy.html', 3)");

        // ── Categories ────────────────────────────────────────────────────────
        $db->exec("INSERT INTO categories (name) VALUES ('General'), ('News'), ('Tutorial')");

        // ── Pages ─────────────────────────────────────────────────────────────
        $sp = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (?,?,?)");
        $sp->execute(['Welcome',       'home',    '<h2>System Active</h2><p>Welcome to your newly installed CMS. You shouldn\'t see this because it loads a PHP template instead.</p>']);
        $sp->execute(['Contact Us',    'contact', '<h2>Contact Us</h2><p>Contact form loaded from template.</p>']);
        $sp->execute(['Privacy Policy','privacy', '<h2>Privacy Policy</h2><p>Privacy policy page.</p>']);
        $sp->execute(['Page Not Found','404',     '<h2>Oops!</h2><p>The page you requested could not be found.</p>']);

        // ── Blog posts ────────────────────────────────────────────────────────
        $post1 = '<p>Well, here we are. The site is live, the database is humming, and this is the first post.</p>'
               . '<p>' . htmlspecialchars($site_name) . ' is up and running &mdash; a lightweight, fast CMS built on PHP and SQLite with no dependencies, no bloat, and no monthly fees. Just files, a database, and a good cup of coffee.</p>'
               . '<p>From here you can write posts, manage pages, switch themes, build out a wiki, and make this site your own. The admin panel is at <strong>/admin</strong> whenever you need it.</p>'
               . '<p>This is your blank page. Fill it with something worth reading.</p>';

        $post2 = '<p>Welcome to ' . htmlspecialchars($site_name) . '. This post will walk you through the basics so you can hit the ground running.</p>'
               . '<h2>Writing Posts</h2><p>Head to <strong>Blog</strong> in the admin sidebar. Each post has a title, slug, category, and content.</p>'
               . '<h2>Managing Pages</h2><p>Static pages live under <strong>Pages</strong>. Custom PHP templates in <code>/templates/</code> load automatically.</p>'
               . '<h2>Themes</h2><p>Click the palette icon in the nav to open the theme switcher, or go to <strong>Settings &rarr; Appearance</strong>.</p>'
               . '<h2>The Wiki</h2><p>The built-in wiki supports lore entries, chapter gating, cross-linking with <code>[[Entry Title]]</code> syntax, and type management under <strong>Settings &rarr; Wiki</strong>.</p>'
               . '<h2>Navigation</h2><p>Edit menu links under <strong>Settings &rarr; Navigation</strong>. All URLs should start with <code>/</code>.</p>'
               . '<h2>Addons</h2><p>Drop any <code>.php</code> file into <code>/addons/</code> and it loads automatically. Use <code>add_hook()</code> and <code>add_filter()</code> to extend core behaviour.</p>';

        $pp = $db->prepare("INSERT INTO posts (title, slug, category, content, excerpt, status, author) VALUES (?,?,?,?,?,?,?)");
        $pp->execute(['Hello World',                            'hello-world',   'General', $post1, 'The site is live.', 'published', $user]);
        $pp->execute(['Getting Started with ' . $site_name,    'getting-started','General', $post2, 'A quick-start guide to posts, pages, themes, navigation, and the wiki.', 'published', $user]);

        // ── Wiki seed entries ─────────────────────────────────────────────────
        $wiki_entries = [
            ['templateforge2',    $site_name,         'concept',   '<p>' . htmlspecialchars($site_name) . ' is a lightweight, file-first PHP CMS built on SQLite with no external dependencies. Deployment is a file copy. The database is a single file. Everything is readable and modifiable without special tooling.</p><h2>Core Capabilities</h2><p>Content pages and blog posts with slug-based routing. A hook engine for pluggable addons. Visitor analytics and traffic statistics. Activity logging across all admin events. A built-in wiki with chapter gating and cross-linking.</p>'],
            ['hook-engine',       'Hook Engine',      'concept',   '<p>The hook engine is the primary extension mechanism. Addons register callbacks against named hook points which the core fires at key moments in the request lifecycle.</p><h2>Action Hooks</h2><p><code>add_hook($name, $callback)</code> registers a callback. <code>run_hook($name, ...$args)</code> fires all registered callbacks for that hook.</p><h2>Filter Hooks</h2><p><code>add_filter($name, $callback, $priority)</code> registers a filter. <code>apply_filter($name, $value, ...$args)</code> passes the value through all filters in priority order and returns the result.</p>'],
            ['module-system',     'Module System',    'concept',   '<p>Modules are self-contained feature packages in <code>/modules/{slug}/</code>. Each module declares a <code>module.json</code> manifest and can be enabled or disabled through the admin.</p><h2>Built-in Modules</h2><p>The wiki module provides lore entries with chapter gating and cross-linking. The podcast module provides episode management.</p>'],
            ['sqlite-storage',    'SQLite Storage',   'concept',   '<p>All persistent data lives in a single SQLite file at <code>db/cms.db</code>. The database directory is locked down by <code>.htaccess</code> to prevent direct browser access.</p><h2>Module Tables</h2><p>The wiki module adds: wiki_entries, wiki_images, wiki_links, wiki_post_links. The podcast module adds: episodes, chapters.</p>'],
            ['theme-system',      'Theme System',     'concept',   '<p>Themes are paired light and dark CSS files in <code>/themes/</code>. Switching themes requires no template edits. All themes declare <code>--tf-*</code> CSS custom properties used by core and module stylesheets.</p><h2>Adding a Theme</h2><p>Create a file in <code>/themes/</code> with the required metadata header and it appears in the theme switcher automatically.</p>'],
            ['analytics',         'Analytics',        'concept',   '<p>' . htmlspecialchars($site_name) . ' includes first-party visitor analytics with no external trackers and no data leaving the server.</p><h2>Admin Dashboard</h2><p>Page views, unique visitors, bounce rate, returning visitor retention, browser and device breakdowns, referrer source groups, top pages, and trend charts.</p>'],
            ['activity-logging',  'Activity Logging', 'concept',   '<p>Every significant admin action writes a structured log entry to the <code>logs</code> table. Entries include category, event, user, IP, and timestamp.</p><h2>Log Categories</h2><p>AUTH, CRUD, SETTINGS, SECURITY, PHP Error, 404.</p>'],
        ];

        $wq = $db->prepare('INSERT OR IGNORE INTO wiki_entries (title, slug, entry_type, body, status) VALUES (?,?,?,?,?)');
        foreach ($wiki_entries as [$slug, $title, $type, $body]) {
            $wq->execute([$title, $slug, $type, $body, 'published']);
        }

        // ── Lock installer ────────────────────────────────────────────────────
        if (@file_put_contents($lock_file, "Installed on " . date('c') . "\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n") === false) {
            throw new RuntimeException('Could not create installer lock file at admin/lock.');
        }

        unset($_SESSION['installer_setup_authorized']);
        $installation_success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
        $step  = '2';
    }
} elseif ($installer_locked) {
    $step = 'locked';
} elseif ($session_auth || $has_valid_token) {
    $step = '2';
} else {
    $step = '1';
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TemplateForge2 — Installer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f1117;
            color: #e2e5ec;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2.5rem 1rem;
        }
        .wrap { width: 100%; max-width: 680px; }

        /* Header */
        .inst-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .inst-logo {
            display: inline-flex; align-items: center; justify-content: center;
            width: 56px; height: 56px;
            background: rgba(79,126,248,0.15);
            border: 1px solid rgba(79,126,248,0.3);
            border-radius: 14px;
            font-size: 1.6rem;
            color: #4f7ef8;
            margin-bottom: 1rem;
        }
        .inst-title { font-size: 1.3rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem; }
        .inst-sub   { font-size: 0.8rem; color: rgba(255,255,255,0.35); }

        /* Steps indicator */
        .steps {
            display: flex; align-items: center; justify-content: center;
            gap: 0; margin-bottom: 2rem;
        }
        .step-dot {
            display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
            font-size: 0.72rem; color: rgba(255,255,255,0.3);
        }
        .step-dot .dot {
            width: 28px; height: 28px; border-radius: 50%;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700;
        }
        .step-dot.active .dot  { background: #4f7ef8; border-color: #4f7ef8; color: #fff; }
        .step-dot.done .dot    { background: #22c55e; border-color: #22c55e; color: #fff; }
        .step-dot.active       { color: rgba(255,255,255,0.8); }
        .step-line { flex: 1; height: 1px; background: rgba(255,255,255,0.1); max-width: 60px; }

        /* Card */
        .card {
            background: #1a1d27;
            border: 1px solid #2a2d3e;
            border-radius: 12px;
            overflow: hidden;
        }
        .card-accent { height: 3px; background: linear-gradient(90deg, #4f7ef8, #a78bfa); }
        .card-body   { padding: 2rem; }

        /* Requirements */
        .req-list { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.5rem; }
        .req-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.7rem 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 8px;
            gap: 1rem;
        }
        .req-label  { font-size: 0.875rem; font-weight: 600; color: rgba(255,255,255,0.8); }
        .req-detail { font-size: 0.78rem; color: rgba(255,255,255,0.35); margin-top: 0.15rem; }
        .req-icon   { font-size: 1.1rem; flex-shrink: 0; }
        .req-pass   { color: #22c55e; }
        .req-fail   { color: #ef4444; }

        /* Form elements */
        label {
            display: block; font-size: 0.78rem; font-weight: 600;
            color: rgba(255,255,255,0.45); text-transform: uppercase;
            letter-spacing: 0.06em; margin-bottom: 0.4rem;
        }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 0.65rem 0.9rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 6px; color: rgba(255,255,255,0.9);
            font-size: 0.9rem; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: inherit;
        }
        input:focus {
            border-color: #4f7ef8;
            box-shadow: 0 0 0 3px rgba(79,126,248,0.15);
        }
        input::placeholder { color: rgba(255,255,255,0.2); }
        .form-group { margin-bottom: 1.25rem; }
        .form-help  { font-size: 0.75rem; color: rgba(255,255,255,0.3); margin-top: 0.35rem; }
        .form-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }

        /* Divider */
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 1.5rem 0; }

        /* Theme picker */
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.6rem;
            margin-top: 0.5rem;
        }
        .theme-opt { position: relative; }
        .theme-opt input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .theme-opt label {
            display: flex; flex-direction: column;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 8px; overflow: hidden; cursor: pointer;
            transition: border-color 0.15s;
            text-transform: none; letter-spacing: 0;
            color: rgba(255,255,255,0.6); font-size: 0.8rem;
            font-weight: 500; padding: 0;
        }
        .theme-opt input:checked + label { border-color: #4f7ef8; }
        .theme-opt label:hover { border-color: rgba(79,126,248,0.5); }
        .theme-preview-bar { height: 8px; width: 100%; }
        .theme-preview-body {
            display: flex; gap: 3px; padding: 4px;
            height: 44px;
        }
        .theme-preview-main { flex: 1; border-radius: 2px; }
        .theme-preview-side { width: 24px; border-radius: 2px; }
        .theme-name {
            padding: 0.4rem 0.5rem;
            background: rgba(0,0,0,0.25);
            font-size: 0.72rem; line-height: 1.2;
            color: rgba(255,255,255,0.6);
        }
        .theme-variant {
            font-size: 0.62rem; opacity: 0.5;
        }
        .theme-opt input:checked + label .theme-name { color: #fff; }
        .check-mark {
            position: absolute; top: 4px; right: 4px;
            width: 18px; height: 18px; border-radius: 50%;
            background: #4f7ef8; color: #fff;
            font-size: 0.65rem; display: none;
            align-items: center; justify-content: center;
        }
        .theme-opt input:checked ~ .check-mark { display: flex; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.65rem 1.25rem;
            font-size: 0.875rem; font-weight: 600;
            border-radius: 6px; border: none; cursor: pointer;
            text-decoration: none; transition: all 0.15s;
            font-family: inherit;
        }
        .btn-primary { background: #4f7ef8; color: #fff; width: 100%; justify-content: center; }
        .btn-primary:hover { background: #3563e8; }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-outline {
            background: transparent; color: rgba(255,255,255,0.6);
            border: 1px solid rgba(255,255,255,0.15);
            width: 100%; justify-content: center;
        }
        .btn-outline:hover { border-color: rgba(255,255,255,0.35); color: #fff; }

        /* Alert */
        .alert {
            padding: 0.8rem 1rem; border-radius: 6px;
            font-size: 0.85rem; border-left: 4px solid #ef4444;
            background: rgba(239,68,68,0.1); color: #fca5a5;
            margin-bottom: 1.25rem; display: flex; gap: 0.5rem;
        }

        /* Success */
        .success-icon { font-size: 3.5rem; color: #22c55e; display: block; margin: 0 auto 1rem; text-align: center; }
        .success-title { font-size: 1.3rem; font-weight: 700; color: #fff; text-align: center; margin-bottom: 0.5rem; }
        .success-sub { font-size: 0.85rem; color: rgba(255,255,255,0.4); text-align: center; margin-bottom: 2rem; }
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

        /* Token section */
        .token-section { margin-top: 1.5rem; }
    </style>
</head>
<body>

<div class="wrap">

    <div class="inst-header">
        <div class="inst-logo"><i class="bi bi-intersect"></i></div>
        <div class="inst-title">TemplateForge2</div>
        <div class="inst-sub">System Installer</div>
    </div>

    <!-- Step indicators -->
    <?php if (!$installer_locked && $step !== 'locked'): ?>
    <div class="steps">
        <div class="step-dot <?= in_array($step, ['2','3']) || $installation_success ? 'done' : 'active' ?>">
            <div class="dot"><?= in_array($step, ['2','3']) || $installation_success ? '<i class="bi bi-check-lg"></i>' : '1' ?></div>
            <span>Requirements</span>
        </div>
        <div class="step-line"></div>
        <div class="step-dot <?= $installation_success ? 'done' : ($step === '2' ? 'active' : '') ?>">
            <div class="dot"><?= $installation_success ? '<i class="bi bi-check-lg"></i>' : '2' ?></div>
            <span>Configure</span>
        </div>
        <div class="step-line"></div>
        <div class="step-dot <?= $installation_success ? 'active' : '' ?>">
            <div class="dot">3</div>
            <span>Complete</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-accent"></div>
        <div class="card-body">

        <?php if ($installation_success): ?>
            <!-- ── Step 3: Success ───────────────────────────────────────── -->
            <i class="bi bi-check-circle-fill success-icon"></i>
            <div class="success-title">Installation Complete</div>
            <div class="success-sub">
                Your site is live. Delete <code>Install.php</code> from the server now.
            </div>
            <div class="btn-grid">
                <a href="/admin/" class="btn btn-primary"><i class="bi bi-speedometer2"></i> Admin Panel</a>
                <a href="/"       class="btn btn-outline"><i class="bi bi-globe"></i> View Site</a>
            </div>

        <?php elseif ($step === 'locked'): ?>
            <!-- ── Locked ────────────────────────────────────────────────── -->
            <div class="alert"><i class="bi bi-lock-fill"></i> <?= htmlspecialchars($error ?? 'Installer is locked.') ?></div>

        <?php elseif ($step === '2'): ?>
            <!-- ── Step 2: Configure ─────────────────────────────────────── -->

            <?php if ($error): ?>
            <div class="alert"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="installer_csrf" value="<?= htmlspecialchars($_SESSION['installer_csrf']) ?>">

                <!-- Site name -->
                <div class="form-group">
                    <label>Site Name</label>
                    <input type="text" name="site_name" value="<?= htmlspecialchars($_POST['site_name'] ?? 'My Site') ?>" required placeholder="My Awesome Site">
                </div>

                <hr class="divider">

                <!-- Theme picker -->
                <div class="form-group">
                    <label>Starting Theme</label>
                    <div class="theme-grid">
                    <?php
                    $default_theme = 'broadsheet-light';
                    foreach ($themes as $slug => $t):
                        $colors   = $t['colors'];
                        $bg       = $colors[0] ?? '#f8f9fa';
                        $accent   = $colors[1] ?? '#4f7ef8';
                        $accent2  = $colors[2] ?? '#a78bfa';
                        $text     = $colors[3] ?? '#222';
                        $is_dark  = strtolower($t['variant']) === 'dark';
                        $nav_bg   = $is_dark ? '#111' : '#222';
                    ?>
                    <div class="theme-opt">
                        <input type="radio" name="active_theme" id="th_<?= $slug ?>" value="<?= $slug ?>"
                               <?= $slug === $default_theme ? 'checked' : '' ?>>
                        <label for="th_<?= $slug ?>">
                            <div class="theme-preview-bar" style="background:<?= htmlspecialchars($nav_bg) ?>;"></div>
                            <div class="theme-preview-body" style="background:<?= htmlspecialchars($bg) ?>;">
                                <div class="theme-preview-main" style="background:<?= htmlspecialchars($bg === '#f8f9fa' ? '#fff' : $bg) ?>;"></div>
                                <div class="theme-preview-side" style="background:<?= htmlspecialchars($accent2) ?>;opacity:0.3;"></div>
                            </div>
                            <div class="theme-name">
                                <?= htmlspecialchars($t['label']) ?>
                                <div class="theme-variant"><?= htmlspecialchars($t['variant']) ?></div>
                            </div>
                        </label>
                        <div class="check-mark"><i class="bi bi-check-lg"></i></div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>

                <hr class="divider">

                <!-- Admin credentials -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Username</label>
                        <input type="text" name="admin_user" value="admin" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_pass" required autocomplete="new-password"
                               placeholder="12+ characters">
                        <div class="form-help">Minimum 12 characters.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-rocket-takeoff"></i> Install TemplateForge2
                </button>
            </form>

        <?php else: ?>
            <!-- ── Step 1: Requirements ──────────────────────────────────── -->

            <div class="req-list">
            <?php foreach ($requirements as $req): ?>
            <div class="req-row">
                <div>
                    <div class="req-label"><?= htmlspecialchars($req['label']) ?></div>
                    <div class="req-detail"><?= htmlspecialchars($req['detail']) ?></div>
                </div>
                <i class="bi <?= $req['pass'] ? 'bi-check-circle-fill req-pass' : 'bi-x-circle-fill req-fail' ?> req-icon"></i>
            </div>
            <?php endforeach; ?>
            </div>

            <?php if (!$all_passed): ?>
            <div class="alert">
                <i class="bi bi-exclamation-triangle"></i>
                One or more requirements are not met. Resolve them before continuing.
            </div>
            <?php endif; ?>

            <?php if ($error && $step === '1'): ?>
            <div class="alert"><i class="bi bi-shield-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="token-section">
                <form method="POST">
                    <input type="hidden" name="enter_setup_token" value="1">
                    <div class="form-group">
                        <label>Setup Token</label>
                        <input type="password" name="setup_token" placeholder="Enter your installer token" required>
                        <div class="form-help">Set via the <code>INSTALLER_SETUP_TOKEN</code> environment variable. Skipped automatically in development.</div>
                    </div>
                    <button type="submit" class="btn btn-primary" <?= !$all_passed ? 'disabled' : '' ?>>
                        <i class="bi bi-arrow-right"></i> Continue to Configuration
                    </button>
                </form>
            </div>

        <?php endif; ?>

        </div>
    </div>

</div>
</body>
</html>
