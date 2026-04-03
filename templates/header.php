<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (function_exists('run_hook')) run_hook('head_top'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?> | <?php echo htmlspecialchars($page['title'] ?? 'Home'); ?></title>

    <?php
    require_once __DIR__ . '/../includes/theme-registry.php';
    $active_theme  = $settings['active_theme'] ?? 'broadsheet-light';
    // Migrate legacy theme slugs (no -light/-dark suffix) to light variant
    if (!preg_match('/-(light|dark)$/', $active_theme)) {
        $active_theme .= '-light';
    }
    if (!tf_is_valid_theme($active_theme)) $active_theme = 'broadsheet-light';
    $tf_registry   = tf_get_theme_registry();
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

        <p class="ts-subtitle">Select any theme — changes apply instantly. Add a <code>name-light.css</code> or <code>name-dark.css</code> to <code>/themes/</code> to register it automatically.</p>

        <div class="ts-list">
<?php
// Group themes by group name, light before dark
$ts_groups = [];
foreach ($tf_registry as $slug => $t) {
    $ts_groups[$t['group']][$t['variant']] = array_merge(['slug' => $slug], $t);
}
ksort($ts_groups);

foreach ($ts_groups as $group_name => $variants):
    // Show light first, then dark
    $ordered = [];
    if (isset($variants['Light'])) $ordered[] = $variants['Light'];
    if (isset($variants['Dark']))  $ordered[] = $variants['Dark'];

    foreach ($ordered as $t):
        $slug      = $t['slug'];
        $is_active = ($active_theme === $slug);
        $colors    = $t['colors'];
        // Build CSS gradient stops from the 4 color values
        $c1 = $colors[0] ?? '#888';
        $c2 = $colors[1] ?? '#555';
        $c3 = $colors[2] ?? '#333';
        $c4 = $colors[3] ?? '#111';
        $gradient = "linear-gradient(135deg, {$c1} 0%, {$c1} 30%, {$c2} 30%, {$c2} 55%, {$c3} 55%, {$c3} 78%, {$c4} 78%)";
        $variant_lower = strtolower($t['variant']);
?>
            <button class="ts-row <?php echo $is_active ? 'ts-active' : ''; ?>"
                    data-theme="<?php echo htmlspecialchars($slug); ?>"
                    aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
                    title="Apply <?php echo htmlspecialchars($t['label'] . ' ' . $t['variant']); ?>">

                <!-- CSS gradient color icon -->
                <div class="ts-icon" style="background: <?php echo $gradient; ?>;" aria-hidden="true">
                    <?php if ($is_active): ?>
                    <div class="ts-icon-check"><i class="bi bi-check-lg"></i></div>
                    <?php endif; ?>
                </div>

                <div class="ts-info">
                    <div class="ts-name">
                        <?php echo htmlspecialchars($t['label']); ?>
                        <span class="ts-variant ts-variant--<?php echo $variant_lower; ?>"><?php echo htmlspecialchars($t['variant']); ?></span>
                    </div>
                    <div class="ts-meta">
                        <span class="ts-layout"><i class="bi bi-layout-sidebar"></i> <?php echo htmlspecialchars($t['layout']); ?></span>
                    </div>
                </div>

                <?php if ($is_active): ?>
                <div class="ts-active-mark" aria-label="Currently active"></div>
                <?php endif; ?>

            </button>
<?php
    endforeach;
endforeach;
?>
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
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(4px);
}
.ts-overlay.ts-open { display: flex; }

.ts-modal {
    background: #fff;
    color: #111;
    border-radius: 12px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.25);
    width: 100%;
    max-width: 620px;
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: ts-in 0.18s ease;
}
@keyframes ts-in {
    from { opacity: 0; transform: translateY(-10px) scale(0.98); }
    to   { opacity: 1; transform: none; }
}

/* ── Header ─────────────────────────────────────────────────────── */
.ts-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.4rem 0.9rem;
    border-bottom: 1px solid #e8e8e8;
    font-size: 0.95rem;
    font-weight: 700;
    gap: 1rem;
    flex-shrink: 0;
}
.ts-header i { color: #7c3aed; margin-right: 0.4rem; }

.ts-close {
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    color: #999;
    line-height: 1;
    padding: 0 0.2rem;
    transition: color 0.15s;
    flex-shrink: 0;
}
.ts-close:hover { color: #111; }

.ts-subtitle {
    padding: 0.65rem 1.4rem;
    font-size: 0.78rem;
    color: #777;
    margin: 0;
    border-bottom: 1px solid #f0f0f0;
    flex-shrink: 0;
    line-height: 1.5;
}
.ts-subtitle code {
    background: #f0f0f0;
    padding: 0.1em 0.35em;
    border-radius: 3px;
    font-size: 0.9em;
    color: #555;
    border: none;
}

/* ── Theme list — 2-column grid ─────────────────────────────────── */
.ts-list {
    overflow-y: auto;
    padding: 0.75rem 1rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    flex: 1;
}

@media (max-width: 480px) {
    .ts-list { grid-template-columns: 1fr; }
}

/* ── Row button ─────────────────────────────────────────────────── */
.ts-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: none;
    border: 1.5px solid #e8e8e8;
    border-radius: 8px;
    padding: 0.6rem 0.75rem;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    transition: border-color 0.13s, background 0.13s, transform 0.13s;
    position: relative;
}
.ts-row:hover {
    border-color: #7c3aed;
    background: #faf8ff;
    transform: translateY(-1px);
}
.ts-row.ts-active {
    border-color: #7c3aed;
    background: #f5f0ff;
    box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
}

/* ── Color icon — 75×75 gradient swatch ─────────────────────────── */
.ts-icon {
    width: 75px;
    height: 75px;
    border-radius: 6px;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.08);
}

.ts-icon-check {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.35);
    color: #fff;
    font-size: 1.6rem;
    backdrop-filter: blur(1px);
}

/* ── Theme info ──────────────────────────────────────────────────── */
.ts-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.ts-name {
    font-size: 0.88rem;
    font-weight: 700;
    color: #111;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.ts-variant {
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.1em 0.5em;
    border-radius: 100px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.ts-variant--light {
    background: #fff8e1;
    color: #b45309;
    border: 1px solid #fde68a;
}
.ts-variant--dark {
    background: #1e1b4b;
    color: #a5b4fc;
    border: 1px solid #312e81;
}

.ts-meta {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.ts-layout, .ts-source {
    font-size: 0.72rem;
    color: #888;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ts-layout i, .ts-source i { font-size: 0.7rem; opacity: 0.7; flex-shrink: 0; }

/* Active indicator dot (top-right corner) */
.ts-active-mark {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #7c3aed;
}

/* ── Footer / status ────────────────────────────────────────────── */
.ts-footer {
    padding: 0.65rem 1.4rem;
    border-top: 1px solid #f0f0f0;
    min-height: 2.4rem;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}
.ts-status { font-size: 0.8rem; color: #777; }
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

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('ts-open')) closeModal();
    });

    document.querySelectorAll('.ts-row').forEach(function (row) {
        row.addEventListener('click', function () {
            var theme = row.dataset.theme;
            if (!theme) return;

            // Swap stylesheet immediately (optimistic)
            if (themeLink) themeLink.href = '/themes/' + theme + '.css';

            // Update active states
            document.querySelectorAll('.ts-row').forEach(function (r) {
                r.classList.remove('ts-active');
                r.setAttribute('aria-pressed', 'false');
                var chk = r.querySelector('.ts-icon-check');
                if (chk) chk.remove();
                var dot = r.querySelector('.ts-active-mark');
                if (dot) dot.remove();
            });

            row.classList.add('ts-active');
            row.setAttribute('aria-pressed', 'true');

            // Add check overlay to icon
            var icon = row.querySelector('.ts-icon');
            if (icon && !icon.querySelector('.ts-icon-check')) {
                var chk = document.createElement('div');
                chk.className = 'ts-icon-check';
                chk.innerHTML = '<i class="bi bi-check-lg"></i>';
                icon.appendChild(chk);
            }
            // Add active dot
            if (!row.querySelector('.ts-active-mark')) {
                var dot = document.createElement('div');
                dot.className = 'ts-active-mark';
                row.appendChild(dot);
            }

            status.textContent = 'Applying\u2026';
            status.className = 'ts-status ts-saving';

            var fd = new FormData();
            fd.append('theme', theme);
            fetch('/theme-switcher.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        status.textContent = '\u2713 Applied.';
                        status.className = 'ts-status ts-success';
                    } else {
                        status.textContent = '\u26a0 Could not save preference.';
                        status.className = 'ts-status ts-error';
                    }
                    setTimeout(function () { status.textContent = ''; status.className = 'ts-status'; }, 3000);
                })
                .catch(function () {
                    status.textContent = '\u26a0 Network error — theme applied visually but not saved.';
                    status.className = 'ts-status ts-error';
                    setTimeout(function () { status.textContent = ''; status.className = 'ts-status'; }, 4000);
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
