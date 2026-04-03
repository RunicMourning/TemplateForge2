<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (function_exists('run_hook')) run_hook('head_top'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?> | <?php echo htmlspecialchars($page['title'] ?? 'Home'); ?></title>

    <?php
    $active_theme = $settings['active_theme'] ?? 'broadsheet';
    $allowed_themes = ['broadsheet', 'inkwell', 'blueprint', 'fieldnotes', 'terminal', 'magazine'];
    if (!in_array($active_theme, $allowed_themes)) $active_theme = 'broadsheet';
    ?>

    <!-- Bootstrap Icons (no Bootstrap CSS/JS) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Core styles (base layout, components) -->
    <link rel="stylesheet" href="/themes/core.css">
    <!-- Active theme (variables + layout overrides) -->
    <link rel="stylesheet" href="/themes/<?php echo $active_theme; ?>.css">

    <style>
        <?php if (function_exists('run_hook')) run_hook('custom_inline_css'); ?>
    </style>

    <?php if (function_exists('run_hook')) run_hook('head_bottom'); ?>
</head>
<body>

<?php
$menu_query = $db->query("SELECT * FROM navigation ORDER BY sort_order ASC");
$menu_items = $menu_query->fetchAll(PDO::FETCH_ASSOC);
$current_page = basename(strtok($_SERVER['REQUEST_URI'], '?'));
if ($current_page === '' || $current_page === 'index.php') $current_page = 'home.html';
?>

<nav class="site-nav">
    <div class="container">
        <div class="nav-inner">
            <a class="nav-brand" href="index.php">
                <i class="bi bi-intersect"></i>
                <?php echo htmlspecialchars($settings['site_name']); ?>
            </a>

            <ul class="nav-links">
                <?php foreach ($menu_items as $item):
                    $active = ($current_page === $item['url']) ? 'active' : '';
                ?>
                    <li><a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $active; ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
                <?php endforeach; ?>
                <?php if (function_exists('run_hook')) run_hook('navbar_end'); ?>
            </ul>

            <div class="nav-search">
                <i class="bi bi-search nav-search-icon"></i>
                <form action="/search.html" method="GET">
                    <input type="search" name="q" placeholder="Search&hellip;" aria-label="Search">
                </form>
            </div>

            <button class="nav-theme-btn" id="themeToggle" aria-label="Change theme" title="Change theme">
                <i class="bi bi-palette"></i>
            </button>

            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>
    <div class="nav-drawer" id="navDrawer">
        <?php foreach ($menu_items as $item):
            $active = ($current_page === $item['url']) ? 'active' : '';
        ?>
            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $active; ?>"><?php echo htmlspecialchars($item['label']); ?></a>
        <?php endforeach; ?>
        <div class="nav-drawer-search">
            <form action="/search.html" method="GET">
                <input type="search" name="q" placeholder="Search&hellip;" aria-label="Search">
            </form>
        </div>
    </div>
</nav>

<script>
(function(){
    var t=document.getElementById('navToggle'), d=document.getElementById('navDrawer');
    if(t&&d){ t.addEventListener('click',function(){ var o=d.classList.toggle('open'); t.setAttribute('aria-expanded',o); t.querySelector('i').className=o?'bi bi-x-lg':'bi bi-list'; }); }
})();
</script>

<!-- ── Theme Switcher Modal ──────────────────────────────────────── -->
<div id="themeSwitcherOverlay" class="ts-overlay" aria-hidden="true">
    <div class="ts-modal" role="dialog" aria-modal="true" aria-labelledby="tsTitle">
        <div class="ts-header">
            <span id="tsTitle"><i class="bi bi-palette"></i> Choose a Theme</span>
            <button class="ts-close" id="tsClose" aria-label="Close">&times;</button>
        </div>
        <p class="ts-subtitle">Theme changes take effect instantly. Layout and colors will update across all pages.</p>
        <div class="ts-grid">
<?php
$ts_themes = [
    'broadsheet' => [
        'label' => 'Broadsheet',
        'source' => 'Journal',
        'desc' => 'Editorial serif · Sidebar right',
        'nav' => '#222222',
        'bg'  => '#f8f9fa',
        'accent' => '#eb6864',
        'surface' => '#ffffff',
    ],
    'inkwell' => [
        'label' => 'Inkwell',
        'source' => 'Darkly',
        'desc' => 'Dark editorial · Sidebar left',
        'nav' => '#111111',
        'bg'  => '#222222',
        'accent' => '#00bc8c',
        'surface' => '#303030',
    ],
    'blueprint' => [
        'label' => 'Blueprint',
        'source' => 'Flatly',
        'desc' => 'SaaS/app shell · Panel layout',
        'nav' => '#2c3e50',
        'bg'  => '#ecf0f1',
        'accent' => '#18bc9c',
        'surface' => '#ffffff',
    ],
    'fieldnotes' => [
        'label' => 'Fieldnotes',
        'source' => 'Sandstone',
        'desc' => 'Warm stone · Full width',
        'nav' => '#3e3f3a',
        'bg'  => '#f8f5f0',
        'accent' => '#93c54b',
        'surface' => '#ffffff',
    ],
    'terminal' => [
        'label' => 'Terminal',
        'source' => 'Cyborg',
        'desc' => 'Hacker mono · No sidebar',
        'nav' => '#020202',
        'bg'  => '#060606',
        'accent' => '#2a9fd6',
        'surface' => '#111111',
    ],
    'magazine' => [
        'label' => 'Magazine',
        'source' => 'Vapor',
        'desc' => 'Cyberpunk · Left rail',
        'nav' => '#0d0020',
        'bg'  => '#1a0933',
        'accent' => '#ea39b8',
        'surface' => '#2a1050',
    ],
];
foreach ($ts_themes as $slug => $t):
    $is_active = ($active_theme === $slug);
?>
            <button class="ts-card <?php echo $is_active ? 'ts-active' : ''; ?>"
                    data-theme="<?php echo $slug; ?>"
                    aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
                    title="<?php echo htmlspecialchars($t['label']); ?>">
                <div class="ts-preview" style="background:<?php echo $t['bg']; ?>">
                    <div class="ts-prev-nav" style="background:<?php echo $t['nav']; ?>">
                        <div class="ts-prev-dot" style="background:<?php echo $t['accent']; ?>"></div>
                        <div class="ts-prev-dot" style="background:<?php echo $t['accent']; ?>; opacity:.5;"></div>
                        <div class="ts-prev-dot" style="background:<?php echo $t['accent']; ?>; opacity:.3;"></div>
                    </div>
                    <div class="ts-prev-body">
                        <div class="ts-prev-sidebar" style="background:<?php echo $t['surface']; ?>; border-right:1px solid rgba(0,0,0,0.08);"></div>
                        <div class="ts-prev-content" style="background:<?php echo $t['bg']; ?>">
                            <div class="ts-prev-line ts-prev-h" style="background:<?php echo $t['accent']; ?>"></div>
                            <div class="ts-prev-line" style="background:<?php echo $t['accent']; ?>; opacity:.3;"></div>
                            <div class="ts-prev-line" style="background:<?php echo $t['accent']; ?>; opacity:.2; width:70%;"></div>
                            <div class="ts-prev-card" style="background:<?php echo $t['surface']; ?>; border:1px solid rgba(128,128,128,0.15);">
                                <div class="ts-prev-line" style="background:<?php echo $t['accent']; ?>; opacity:.5; height:2px; margin-bottom:4px;"></div>
                                <div class="ts-prev-line" style="background:<?php echo $t['accent']; ?>; opacity:.2;"></div>
                            </div>
                        </div>
                    </div>
                    <?php if ($is_active): ?>
                    <div class="ts-active-badge"><i class="bi bi-check-lg"></i></div>
                    <?php endif; ?>
                </div>
                <div class="ts-card-info">
                    <strong><?php echo htmlspecialchars($t['label']); ?></strong>
                    <span class="ts-source">Based on <?php echo htmlspecialchars($t['source']); ?></span>
                    <span class="ts-desc"><?php echo htmlspecialchars($t['desc']); ?></span>
                </div>
            </button>
<?php endforeach; ?>
        </div>
        <div class="ts-footer">
            <span id="tsStatus" class="ts-status"></span>
        </div>
    </div>
</div>

<style>
/* ── Nav palette button ─────────────────────────────────────────── */
.nav-theme-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: 1px solid rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.8);
    width: 36px;
    height: 36px;
    border-radius: var(--tf-radius, 4px);
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
}
.nav-theme-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* ── Modal overlay ──────────────────────────────────────────────── */
.ts-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(3px);
}
.ts-overlay.ts-open { display: flex; }

.ts-modal {
    background: #fff;
    color: #1a1a1a;
    border-radius: 12px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 680px;
    max-height: 90vh;
    overflow-y: auto;
    animation: ts-slide-in 0.2s ease;
}
@keyframes ts-slide-in {
    from { opacity: 0; transform: translateY(-12px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.ts-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem 0.75rem;
    border-bottom: 1px solid #e5e5e5;
    font-size: 1rem;
    font-weight: 700;
    gap: 1rem;
}
.ts-header i { color: #7c3aed; margin-right: 0.4rem; }

.ts-close {
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    color: #999;
    line-height: 1;
    padding: 0 0.25rem;
    transition: color 0.15s;
    flex-shrink: 0;
}
.ts-close:hover { color: #111; }

.ts-subtitle {
    padding: 0.75rem 1.5rem 0;
    font-size: 0.82rem;
    color: #666;
    margin: 0;
}

/* ── Theme grid ─────────────────────────────────────────────────── */
.ts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
    padding: 1.25rem 1.5rem;
}

.ts-card {
    background: none;
    border: 2px solid #e5e5e5;
    border-radius: 10px;
    padding: 0;
    cursor: pointer;
    text-align: left;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    overflow: hidden;
    font-family: inherit;
}
.ts-card:hover {
    border-color: #7c3aed;
    box-shadow: 0 4px 16px rgba(124,58,237,0.15);
    transform: translateY(-2px);
}
.ts-card.ts-active {
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124,58,237,0.2);
}

/* ── Preview thumbnail ──────────────────────────────────────────── */
.ts-preview {
    height: 100px;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.ts-prev-nav {
    height: 14px;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 0 6px;
    flex-shrink: 0;
}
.ts-prev-dot {
    width: 20px;
    height: 4px;
    border-radius: 2px;
}
.ts-prev-body {
    flex: 1;
    display: flex;
    overflow: hidden;
}
.ts-prev-sidebar {
    width: 28%;
    flex-shrink: 0;
}
.ts-prev-content {
    flex: 1;
    padding: 6px;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.ts-prev-line {
    height: 5px;
    border-radius: 2px;
    width: 100%;
}
.ts-prev-h { height: 8px; margin-bottom: 2px; }
.ts-prev-card {
    margin-top: 4px;
    border-radius: 3px;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ts-active-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    background: #7c3aed;
    color: #fff;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}

/* ── Card info ──────────────────────────────────────────────────── */
.ts-card-info {
    padding: 0.6rem 0.75rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    border-top: 1px solid rgba(0,0,0,0.06);
}
.ts-card-info strong {
    font-size: 0.85rem;
    color: #111;
    font-weight: 700;
}
.ts-source {
    font-size: 0.7rem;
    color: #7c3aed;
    font-weight: 600;
}
.ts-desc {
    font-size: 0.7rem;
    color: #888;
}

/* ── Footer / status ────────────────────────────────────────────── */
.ts-footer {
    padding: 0.75rem 1.5rem 1.25rem;
    border-top: 1px solid #e5e5e5;
    min-height: 2.5rem;
    display: flex;
    align-items: center;
}
.ts-status {
    font-size: 0.82rem;
    color: #666;
}
.ts-status.ts-saving  { color: #7c3aed; }
.ts-status.ts-success { color: #16a34a; }
.ts-status.ts-error   { color: #dc2626; }
</style>

<script>
(function () {
    var overlay   = document.getElementById('themeSwitcherOverlay');
    var openBtn   = document.getElementById('themeToggle');
    var closeBtn  = document.getElementById('tsClose');
    var status    = document.getElementById('tsStatus');
    var themeLink = document.querySelector('link[href*="/themes/"][href*=".css"]:not([href*="core"])');

    function openModal() {
        overlay.classList.add('ts-open');
        overlay.setAttribute('aria-hidden', 'false');
        closeBtn.focus();
    }

    function closeModal() {
        overlay.classList.remove('ts-open');
        overlay.setAttribute('aria-hidden', 'true');
        openBtn.focus();
    }

    if (openBtn)  openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    // Close on overlay backdrop click
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('ts-open')) closeModal();
    });

    // Theme card clicks
    document.querySelectorAll('.ts-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var theme = card.dataset.theme;
            if (!theme) return;

            // Optimistic UI — swap stylesheet immediately
            if (themeLink) {
                themeLink.href = '/themes/' + theme + '.css';
            }

            // Mark active card
            document.querySelectorAll('.ts-card').forEach(function (c) {
                c.classList.remove('ts-active');
                c.setAttribute('aria-pressed', 'false');
                var badge = c.querySelector('.ts-active-badge');
                if (badge) badge.remove();
            });
            card.classList.add('ts-active');
            card.setAttribute('aria-pressed', 'true');
            if (!card.querySelector('.ts-active-badge')) {
                var badge = document.createElement('div');
                badge.className = 'ts-active-badge';
                badge.innerHTML = '<i class="bi bi-check-lg"></i>';
                card.querySelector('.ts-preview').appendChild(badge);
            }

            // Status
            status.textContent = 'Applying theme\u2026';
            status.className = 'ts-status ts-saving';

            // Persist via fetch
            var fd = new FormData();
            fd.append('theme', theme);
            fetch('/theme-switcher.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        status.textContent = '\u2713 Theme applied successfully.';
                        status.className = 'ts-status ts-success';
                    } else {
                        status.textContent = '\u26a0 Could not save theme preference.';
                        status.className = 'ts-status ts-error';
                    }
                    setTimeout(function () {
                        status.textContent = '';
                        status.className = 'ts-status';
                    }, 3000);
                })
                .catch(function () {
                    status.textContent = '\u26a0 Network error — visual theme applied but not saved.';
                    status.className = 'ts-status ts-error';
                    setTimeout(function () {
                        status.textContent = '';
                        status.className = 'ts-status';
                    }, 4000);
                });
        });
    });
})();
</script>

<?php if (function_exists('run_hook')) run_hook('content_before'); ?>

<div class="container">
    <div class="site-layout">

        <aside class="site-aside">
<?php
global $custom_sidebar_content;
if (!empty($custom_sidebar_content)) {
    echo '<div class="widget">' . $custom_sidebar_content . '</div>';
}
$sidebar_path = __DIR__ . '/../sidebars/*.php';
$widgets = glob($sidebar_path);
foreach ($widgets as $widget) {
    echo '<div class="widget">';
    include $widget;
    echo '</div>';
}
?>
        </aside>

        <main class="site-main">
            <?php if (function_exists('run_hook')) run_hook('content_start'); ?>
