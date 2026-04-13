<?php
/**
 * Dashboard — Mission Control
 * Layer B: Application. Reads from D (analytics, logs, posts, pages, wiki, podcast).
 */

if (!function_exists('fmt_size')) {
    function fmt_size(int $b): string {
        if ($b >= 1073741824) return number_format($b / 1073741824, 2) . ' GB';
        if ($b >= 1048576)    return number_format($b / 1048576, 2) . ' MB';
        if ($b >= 1024)       return number_format($b / 1024, 1) . ' KB';
        return $b . ' B';
    }
}

// ── Traffic ───────────────────────────────────────────────────────────────────
$views_today     = (int)$db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp)=DATE('now')")->fetchColumn();
$views_yesterday = (int)$db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp)=DATE('now','-1 day')")->fetchColumn();
$views_7d        = (int)$db->query("SELECT COUNT(*) FROM analytics WHERE timestamp>=datetime('now','-7 days')")->fetchColumn();
$uniques_today   = (int)$db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics WHERE DATE(timestamp)=DATE('now')")->fetchColumn();
$uniques_7d      = (int)$db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics WHERE timestamp>=datetime('now','-7 days')")->fetchColumn();
$trend_pct       = $views_yesterday > 0 ? round((($views_today - $views_yesterday) / $views_yesterday) * 100, 1) : null;

// Top pages this week
$top_pages_week = $db->query(
    "SELECT page_url, COUNT(*) AS hits FROM analytics
     WHERE timestamp>=datetime('now','-7 days')
     GROUP BY page_url ORDER BY hits DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
$top_page_hits_max = $top_pages_week ? max(array_column($top_pages_week, 'hits')) : 1;

// Top referrers this week
$top_refs = $db->query(
    "SELECT referrer, COUNT(*) AS hits FROM analytics
     WHERE timestamp>=datetime('now','-7 days')
       AND referrer IS NOT NULL AND referrer != '' AND referrer != 'Direct'
     GROUP BY referrer ORDER BY hits DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// 14-day sparkline
$spark = [];
for ($i = 13; $i >= 0; $i--) {
    $d           = date('Y-m-d', strtotime("-$i days"));
    $label       = date('j', strtotime($d));
    $spark[$label] = (int)$db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp)='$d'")->fetchColumn();
}

// ── Error health ──────────────────────────────────────────────────────────────
$errors_404_7d  = (int)$db->query("SELECT COUNT(*) FROM logs WHERE category='404' AND timestamp>=datetime('now','-7 days')")->fetchColumn();
$errors_404_yd  = (int)$db->query("SELECT COUNT(*) FROM logs WHERE category='404' AND DATE(timestamp)=DATE('now','-1 day')")->fetchColumn();
$errors_php_7d  = (int)$db->query("SELECT COUNT(*) FROM logs WHERE category='PHP Error' AND timestamp>=datetime('now','-7 days')")->fetchColumn();
$security_7d    = (int)$db->query("SELECT COUNT(*) FROM logs WHERE category='SECURITY' AND timestamp>=datetime('now','-7 days')")->fetchColumn();
$error_trend    = $errors_404_yd > 0 ? round((($errors_404_7d/7 - $errors_404_yd) / $errors_404_yd) * 100, 1) : null;

// ── Content ───────────────────────────────────────────────────────────────────
$posts_published = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$posts_draft     = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status!='published'")->fetchColumn();
$total_pages     = (int)$db->query("SELECT COUNT(*) FROM pages")->fetchColumn();

// Wiki (soft — module may not be loaded)
$wiki_published = $wiki_draft = $wiki_gated = $wiki_types_counts = null;
try {
    $wiki_published    = (int)$db->query("SELECT COUNT(*) FROM wiki_entries WHERE status='published'")->fetchColumn();
    $wiki_draft        = (int)$db->query("SELECT COUNT(*) FROM wiki_entries WHERE status='draft'")->fetchColumn();
    $wiki_gated        = (int)$db->query("SELECT COUNT(*) FROM wiki_entries WHERE status='published' AND reveal_chapter_id IS NOT NULL")->fetchColumn();
    $wiki_types_counts = $db->query(
        "SELECT entry_type, COUNT(*) AS n FROM wiki_entries WHERE status='published' GROUP BY entry_type ORDER BY n DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception) {}

// Podcast (soft)
$podcast_published = $podcast_draft = $latest_ep = null;
try {
    $podcast_published = (int)$db->query("SELECT COUNT(*) FROM episodes WHERE status='published'")->fetchColumn();
    $podcast_draft     = (int)$db->query("SELECT COUNT(*) FROM episodes WHERE status='draft'")->fetchColumn();
    $latest_ep         = $db->query("SELECT title, release_date FROM episodes WHERE status='published' ORDER BY episode_number DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception) {}

// ── Activity ──────────────────────────────────────────────────────────────────
$recent_posts = $db->query(
    "SELECT id, title, category, status, created_at FROM posts ORDER BY created_at DESC LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

$recent_logs = $db->query(
    "SELECT category, event, user, timestamp FROM logs ORDER BY timestamp DESC LIMIT 12"
)->fetchAll(PDO::FETCH_ASSOC);

$last_login = $db->query(
    "SELECT timestamp FROM logs WHERE category='AUTH' AND event='Admin Login' ORDER BY timestamp DESC LIMIT 1"
)->fetchColumn();

// ── System / Server ───────────────────────────────────────────────────────────
$db_path_abs = __DIR__ . '/../../db/cms.db';
$db_size     = file_exists($db_path_abs) ? fmt_size(filesize($db_path_abs)) : null;
$db_modified = file_exists($db_path_abs) ? date('M j, Y g:ia', filemtime($db_path_abs)) : null;

$upload_size = null;
$upload_path = __DIR__ . '/../../uploads';
if (is_dir($upload_path)) {
    try {
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_path, FilesystemIterator::SKIP_DOTS));
        $bytes = 0;
        foreach ($iter as $f) if ($f->isFile()) $bytes += $f->getSize();
        $upload_size = fmt_size($bytes);
    } catch (Exception) {}
}

$uptime_str = null;
if (is_readable('/proc/uptime')) {
    $sec = (int)explode(' ', file_get_contents('/proc/uptime'))[0];
    $uptime_str = ($sec >= 86400 ? floor($sec/86400).'d ' : '') . floor(($sec%86400)/3600).'h ' . floor(($sec%3600)/60).'m';
}

$disk_free = $disk_total = $disk_pct = null;
try {
    $df = disk_free_space(__DIR__); $dt = disk_total_space(__DIR__);
    if ($df !== false && $dt !== false) {
        $disk_free = fmt_size((int)$df); $disk_total = fmt_size((int)$dt);
        $disk_pct  = round((1 - $df/$dt) * 100);
    }
} catch (Exception) {}

$mem_used  = function_exists('memory_get_usage') ? fmt_size(memory_get_usage(true)) : null;
$mem_limit = ini_get('memory_limit');
$sqlite_ver = null;
try { $sqlite_ver = $db->query("SELECT sqlite_version()")->fetchColumn(); } catch (Exception) {}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ── Header ─────────────────────────────────────────────────────────────── -->
<div class="page-title-bar">
    <div>
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">
            <?= date('l, F j, Y') ?>
            <?php if ($last_login): ?> &mdash; last login <?= date('M j \a\t g:ia', strtotime($last_login)) ?><?php endif; ?>
        </div>
    </div>
    <div class="a-flex gap-1 flex-wrap">
        <a href="index.php?view=blog&action=new"  class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> New Post</a>
        <a href="index.php?view=wiki&action=edit" class="btn btn-outline btn-sm"><i class="bi bi-journal-plus"></i> New Entry</a>
        <a href="../" target="_blank"             class="btn btn-ghost btn-sm"><i class="bi bi-box-arrow-up-right"></i> View Site</a>
    </div>
</div>

<!-- ── Row 1: KPI strip ───────────────────────────────────────────────────── -->
<div class="dash-kpi-strip mb-3">

    <a href="index.php?view=analytics" class="dash-kpi2">
        <div class="dash-kpi2-label"><i class="bi bi-eye"></i> Views Today</div>
        <div class="dash-kpi2-value"><?= number_format($views_today) ?></div>
        <?php if ($trend_pct !== null): ?>
        <div class="dash-kpi2-trend <?= $trend_pct >= 0 ? 'up' : 'down' ?>">
            <i class="bi bi-arrow-<?= $trend_pct >= 0 ? 'up' : 'down' ?>-short"></i><?= abs($trend_pct) ?>% vs yesterday
        </div>
        <?php endif; ?>
    </a>

    <a href="index.php?view=analytics" class="dash-kpi2">
        <div class="dash-kpi2-label"><i class="bi bi-people"></i> Unique Visitors</div>
        <div class="dash-kpi2-value"><?= number_format($uniques_today) ?></div>
        <div class="dash-kpi2-sub"><?= number_format($uniques_7d) ?> this week</div>
    </a>

    <a href="index.php?view=analytics" class="dash-kpi2">
        <div class="dash-kpi2-label"><i class="bi bi-graph-up"></i> Views This Week</div>
        <div class="dash-kpi2-value"><?= number_format($views_7d) ?></div>
        <div class="dash-kpi2-sub">~<?= number_format(round($views_7d/7)) ?>/day avg</div>
    </a>

    <a href="index.php?view=logs" class="dash-kpi2 <?= $errors_404_7d > 10 ? 'warn' : '' ?>">
        <div class="dash-kpi2-label"><i class="bi bi-exclamation-triangle"></i> 404 Errors</div>
        <div class="dash-kpi2-value"><?= $errors_404_7d ?></div>
        <div class="dash-kpi2-sub">last 7 days</div>
    </a>

    <?php if ($security_7d > 0): ?>
    <a href="index.php?view=logs" class="dash-kpi2 warn">
        <div class="dash-kpi2-label"><i class="bi bi-shield-exclamation"></i> Security Events</div>
        <div class="dash-kpi2-value"><?= $security_7d ?></div>
        <div class="dash-kpi2-sub">last 7 days</div>
    </a>
    <?php endif; ?>

    <?php if ($errors_php_7d > 0): ?>
    <a href="index.php?view=logs" class="dash-kpi2 warn">
        <div class="dash-kpi2-label"><i class="bi bi-bug"></i> PHP Errors</div>
        <div class="dash-kpi2-value"><?= $errors_php_7d ?></div>
        <div class="dash-kpi2-sub">last 7 days</div>
    </a>
    <?php endif; ?>

</div>

<!-- ── Row 2: Traffic chart + Content health ─────────────────────────────── -->
<div class="dash-main-grid mb-3">

    <!-- Traffic chart -->
    <div class="a-card dash-chart-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-activity" style="color:var(--a-accent);"></i> 14-Day Traffic</div>
            <a href="index.php?view=analytics" class="btn btn-ghost btn-sm">Full Report →</a>
        </div>
        <div class="a-card-body" style="padding-top:0.5rem;">
            <canvas id="dashSparkline" height="110"></canvas>
        </div>
    </div>

    <!-- Content health -->
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-clipboard-data" style="color:var(--a-accent);"></i> Content</div>
        </div>
        <div class="dash-health-list">

            <div class="dash-health-row">
                <i class="bi bi-journal-richtext" style="color:var(--a-accent);"></i>
                <div class="dash-health-body">
                    <div class="dash-health-title">Blog Posts</div>
                    <div class="dash-health-sub"><?= $posts_published ?> published<?= $posts_draft > 0 ? ', <span style="color:var(--a-warning);">' . $posts_draft . ' draft' . ($posts_draft > 1 ? 's' : '') . '</span>' : '' ?></div>
                </div>
                <a href="index.php?view=blog" class="btn btn-ghost btn-sm">Manage</a>
            </div>

            <div class="dash-health-row">
                <i class="bi bi-files" style="color:#8b5cf6;"></i>
                <div class="dash-health-body">
                    <div class="dash-health-title">Pages</div>
                    <div class="dash-health-sub"><?= $total_pages ?> pages</div>
                </div>
                <a href="index.php?view=pages" class="btn btn-ghost btn-sm">Manage</a>
            </div>

            <?php if ($wiki_published !== null): ?>
            <div class="dash-health-row">
                <i class="bi bi-journal-bookmark" style="color:#06b6d4;"></i>
                <div class="dash-health-body">
                    <div class="dash-health-title">Wiki Entries</div>
                    <div class="dash-health-sub">
                        <?= $wiki_published ?> published
                        <?= $wiki_draft > 0 ? ', <span style="color:var(--a-warning);">' . $wiki_draft . ' draft' . ($wiki_draft > 1 ? 's' : '') . '</span>' : '' ?>
                        <?= $wiki_gated > 0 ? ' &middot; <span style="color:#8b5cf6;">' . $wiki_gated . ' gated</span>' : '' ?>
                    </div>
                </div>
                <a href="index.php?view=wiki" class="btn btn-ghost btn-sm">Manage</a>
            </div>
            <?php endif; ?>

            <?php if ($podcast_published !== null): ?>
            <div class="dash-health-row">
                <i class="bi bi-mic" style="color:#f59e0b;"></i>
                <div class="dash-health-body">
                    <div class="dash-health-title">Episodes</div>
                    <div class="dash-health-sub">
                        <?= $podcast_published ?> published
                        <?= $podcast_draft > 0 ? ', <span style="color:var(--a-warning);">' . $podcast_draft . ' draft' . ($podcast_draft > 1 ? 's' : '') . '</span>' : '' ?>
                        <?php if ($latest_ep): ?> &middot; Latest: <?= htmlspecialchars(mb_strimwidth($latest_ep['title'], 0, 30, '…')) ?><?php endif; ?>
                    </div>
                </div>
                <a href="index.php?view=podcast" class="btn btn-ghost btn-sm">Manage</a>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<!-- ── Row 3: Top pages + Referrers ──────────────────────────────────────── -->
<div class="dash-two-col mb-3">

    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-bar-chart-steps" style="color:var(--a-accent);"></i> Top Pages (7 days)</div>
        </div>
        <div class="a-card-body" style="padding:0;">
            <?php if (empty($top_pages_week)): ?>
            <div class="empty-state" style="padding:1.5rem;"><p>No traffic data yet.</p></div>
            <?php else: foreach ($top_pages_week as $tp):
                $pct = round($tp['hits'] / $top_page_hits_max * 100); ?>
            <div class="dash-bar-row">
                <div class="dash-bar-label" title="<?= htmlspecialchars($tp['page_url']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($tp['page_url'], 0, 45, '…')) ?>
                </div>
                <div class="dash-bar-track">
                    <div class="dash-bar-fill" style="width:<?= $pct ?>%;"></div>
                </div>
                <div class="dash-bar-hits"><?= number_format($tp['hits']) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-signpost-split" style="color:var(--a-accent);"></i> Top Referrers (7 days)</div>
        </div>
        <div class="a-card-body" style="padding:0;">
            <?php if (empty($top_refs)): ?>
            <div class="empty-state" style="padding:1.5rem;"><p>No referrer data yet.</p></div>
            <?php else:
                $ref_max = max(array_column($top_refs, 'hits'));
                foreach ($top_refs as $ref):
                $ref_pct = round($ref['hits'] / $ref_max * 100);
                $ref_host = parse_url($ref['referrer'], PHP_URL_HOST) ?: $ref['referrer']; ?>
            <div class="dash-bar-row">
                <div class="dash-bar-label" title="<?= htmlspecialchars($ref['referrer']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($ref_host, 0, 35, '…')) ?>
                </div>
                <div class="dash-bar-track">
                    <div class="dash-bar-fill" style="width:<?= $ref_pct ?>%; background:var(--a-success);"></div>
                </div>
                <div class="dash-bar-hits"><?= number_format($ref['hits']) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

</div>

<!-- ── Row 4: Wiki type breakdown (if wiki loaded) ────────────────────────── -->
<?php if (!empty($wiki_types_counts)): ?>
<div class="a-card mb-3">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-tags" style="color:#06b6d4;"></i> Wiki by Type</div>
        <a href="index.php?view=wiki" class="btn btn-ghost btn-sm">All Entries →</a>
    </div>
    <div class="dash-type-strip">
        <?php
        $type_colors = ['character'=>'#3b82f6','place'=>'#10b981','faction'=>'#8b5cf6',
                        'concept'=>'#f59e0b','creature'=>'#ef4444','artifact'=>'#f59e0b','event'=>'#ec4899'];
        foreach ($wiki_types_counts as $tc):
            $col = $type_colors[$tc['entry_type']] ?? '#6b7280';
        ?>
        <a href="index.php?view=wiki&type=<?= urlencode($tc['entry_type']) ?>" class="dash-type-pill"
           style="border-color:<?= $col ?>22; background:<?= $col ?>11;">
            <span class="dash-type-dot" style="background:<?= $col ?>;"></span>
            <span class="dash-type-name"><?= ucfirst($tc['entry_type']) ?></span>
            <span class="dash-type-count" style="color:<?= $col ?>;"><?= $tc['n'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Row 5: Recent posts + Activity feed ───────────────────────────────── -->
<div class="dash-two-col mb-3">

    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-pencil-square" style="color:var(--a-accent);"></i> Recent Posts</div>
            <a href="index.php?view=blog" class="btn btn-ghost btn-sm">All Posts →</a>
        </div>
        <div class="a-card-body" style="padding:0;">
            <?php if (empty($recent_posts)): ?>
            <div class="empty-state" style="padding:1.5rem;"><p>No posts yet.</p></div>
            <?php else: foreach ($recent_posts as $p): ?>
            <div class="dash-post-row">
                <div class="dash-post-dot <?= $p['status'] === 'published' ? 'pub' : 'draft' ?>"></div>
                <div class="dash-post-body">
                    <div class="dash-post-title"><?= htmlspecialchars(mb_strimwidth($p['title'], 0, 48, '…')) ?></div>
                    <div class="dash-post-meta"><?= htmlspecialchars($p['category'] ?? 'General') ?> &middot; <?= date('M j', strtotime($p['created_at'])) ?></div>
                </div>
                <a href="index.php?view=blog&edit=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-sm" style="flex-shrink:0;">Edit</a>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-activity" style="color:var(--a-accent);"></i> Activity Feed</div>
            <a href="index.php?view=logs" class="btn btn-ghost btn-sm">All Logs →</a>
        </div>
        <div class="a-card-body" style="padding:0;">
            <?php if (empty($recent_logs)): ?>
            <div class="empty-state" style="padding:1.5rem;"><p>No activity yet.</p></div>
            <?php else:
            $cat_colors = ['AUTH'=>'#3b82f6','CRUD'=>'#10b981','SECURITY'=>'#ef4444',
                           '404'=>'#f97316','SETTINGS'=>'#8b5cf6','NAV'=>'#6366f1',
                           'PHP Error'=>'#ef4444','WIKI'=>'#06b6d4','NAV'=>'#6366f1'];
            foreach ($recent_logs as $log):
                $cc = $cat_colors[$log['category']] ?? '#6b7280'; ?>
            <div class="dash-log-row">
                <span class="dash-log-cat" style="background:<?= $cc ?>18;color:<?= $cc ?>;border:1px solid <?= $cc ?>30;">
                    <?= htmlspecialchars($log['category']) ?>
                </span>
                <div class="dash-log-event"><?= htmlspecialchars($log['event']) ?></div>
                <div class="dash-log-time"><?= date('M j g:ia', strtotime($log['timestamp'])) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

</div>

<!-- ── Row 6: Server stats ───────────────────────────────────────────────── -->
<?php
$srv = [];
if ($uptime_str)     $srv[] = ['bi-clock-history', 'var(--a-info)',    'Uptime',          $uptime_str, null];
if ($disk_free)      $srv[] = ['bi-hdd',           'var(--a-accent)',  'Disk Free',       $disk_free,  "of $disk_total ({$disk_pct}% used)"];
if ($db_size)        $srv[] = ['bi-database',      'var(--a-success)', 'Database',        $db_size,    $db_modified ? "Last write: $db_modified" : null];
if ($upload_size)    $srv[] = ['bi-folder2-open',  'var(--a-warning)', 'Uploads',         $upload_size, null];
if ($mem_used)       $srv[] = ['bi-memory',        '#a78bfa',          'Memory',          $mem_used,   "limit: $mem_limit"];
$srv[]               =       ['bi-code-slash',     'var(--a-info)',    'PHP',             PHP_VERSION, null];
if ($sqlite_ver)     $srv[] = ['bi-layers',        'var(--a-success)', 'SQLite',          $sqlite_ver, null];
?>
<?php if (!empty($srv)): ?>
<div class="a-card mb-2">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-server" style="color:var(--a-accent);"></i> Server</div>
    </div>
    <div class="dash-srv-grid">
        <?php foreach ($srv as [$icon, $col, $lbl, $val, $sub]): ?>
        <div class="dash-srv-cell">
            <div class="dash-srv-icon"><i class="bi <?= $icon ?>" style="color:<?= $col ?>;"></i></div>
            <div>
                <div class="dash-srv-val"><?= htmlspecialchars($val) ?></div>
                <div class="dash-srv-lbl"><?= htmlspecialchars($lbl) ?><?= $sub ? ' <span class="dash-srv-sub">' . htmlspecialchars($sub) . '</span>' : '' ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
(function(){
    var ctx   = document.getElementById('dashSparkline')?.getContext('2d');
    if (!ctx) return;
    var accent = '#4f7ef8';
    var muted  = 'rgba(79,126,248,0.08)';
    var grad   = ctx.createLinearGradient(0, 0, 0, 120);
    grad.addColorStop(0, 'rgba(79,126,248,0.18)');
    grad.addColorStop(1, 'rgba(79,126,248,0.01)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($spark)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($spark)) ?>,
                borderColor: accent, borderWidth: 2,
                fill: true, backgroundColor: grad,
                tension: 0.35, pointRadius: 2,
                pointBackgroundColor: accent, pointBorderColor: '#fff', pointBorderWidth: 1.5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false,
                callbacks: { label: ctx => ctx.parsed.y + ' views' }
            }},
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#7a7f95', font: { size: 10 } } },
                y: { display: false, beginAtZero: true }
            }
        }
    });
})();
</script>

<style>
/* ── KPI strip ───────────────────────────────────────────────────────────── */
.dash-kpi-strip {
    display: flex; gap: 0.75rem; flex-wrap: wrap;
}
.dash-kpi2 {
    flex: 1; min-width: 140px;
    background: var(--a-surface);
    border: 1px solid var(--a-border);
    border-radius: var(--a-radius-lg);
    padding: 1rem 1.1rem;
    text-decoration: none; color: var(--a-text);
    transition: border-color 0.15s, box-shadow 0.15s;
    position: relative; overflow: hidden;
}
.dash-kpi2::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 2px; background: var(--a-accent); opacity: 0;
    transition: opacity 0.15s;
}
.dash-kpi2:hover { border-color: var(--a-accent); box-shadow: var(--a-shadow); text-decoration: none; }
.dash-kpi2:hover::before { opacity: 1; }
.dash-kpi2.warn { border-color: rgba(239,68,68,0.4); background: rgba(239,68,68,0.04); }
.dash-kpi2.warn::before { background: var(--a-danger); opacity: 1; }
.dash-kpi2-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--a-text-muted); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.35rem; }
.dash-kpi2-value { font-size: 1.75rem; font-weight: 700; line-height: 1; margin-bottom: 0.25rem; }
.dash-kpi2-trend { font-size: 0.72rem; font-weight: 600; display: flex; align-items: center; gap: 0.2rem; }
.dash-kpi2-trend.up   { color: var(--a-success); }
.dash-kpi2-trend.down { color: var(--a-danger); }
.dash-kpi2-sub { font-size: 0.72rem; color: var(--a-text-muted); }

/* ── Main grid ───────────────────────────────────────────────────────────── */
.dash-main-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 0.75rem;
}
@media (max-width: 900px) { .dash-main-grid { grid-template-columns: 1fr; } }

.dash-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}
@media (max-width: 700px) { .dash-two-col { grid-template-columns: 1fr; } }

/* ── Content health list ─────────────────────────────────────────────────── */
.dash-health-list { display: flex; flex-direction: column; }
.dash-health-row {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--a-border);
    font-size: 0.875rem;
}
.dash-health-row:last-child { border-bottom: none; }
.dash-health-row > i { font-size: 1.1rem; flex-shrink: 0; }
.dash-health-body { flex: 1; min-width: 0; }
.dash-health-title { font-weight: 600; }
.dash-health-sub { font-size: 0.78rem; color: var(--a-text-muted); margin-top: 0.1rem; }

/* ── Bar charts ──────────────────────────────────────────────────────────── */
.dash-bar-row {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.6rem 1.25rem;
    border-bottom: 1px solid var(--a-border);
    font-size: 0.8rem;
}
.dash-bar-row:last-child { border-bottom: none; }
.dash-bar-label { width: 180px; flex-shrink: 0; color: var(--a-text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dash-bar-track { flex: 1; height: 4px; background: var(--a-surface-2); border-radius: 100px; overflow: hidden; }
.dash-bar-fill  { height: 100%; background: var(--a-accent); border-radius: 100px; transition: width 0.3s ease; }
.dash-bar-hits  { width: 36px; text-align: right; font-weight: 700; color: var(--a-text-muted); font-size: 0.75rem; }

/* ── Wiki type pills ─────────────────────────────────────────────────────── */
.dash-type-strip { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 1rem 1.25rem; }
.dash-type-pill {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.35rem 0.75rem;
    border: 1px solid transparent; border-radius: 100px;
    text-decoration: none; color: var(--a-text);
    font-size: 0.8rem; font-weight: 500;
    transition: opacity 0.15s;
}
.dash-type-pill:hover { opacity: 0.8; text-decoration: none; }
.dash-type-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.dash-type-name { }
.dash-type-count { font-weight: 700; }

/* ── Recent posts ────────────────────────────────────────────────────────── */
.dash-post-row {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.65rem 1.25rem; border-bottom: 1px solid var(--a-border);
}
.dash-post-row:last-child { border-bottom: none; }
.dash-post-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; background: var(--a-border); }
.dash-post-dot.pub { background: var(--a-success); }
.dash-post-dot.draft { background: var(--a-warning); }
.dash-post-body { flex: 1; min-width: 0; }
.dash-post-title { font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-post-meta  { font-size: 0.72rem; color: var(--a-text-muted); margin-top: 0.1rem; }

/* ── Activity feed ───────────────────────────────────────────────────────── */
.dash-log-row {
    display: flex; align-items: center; gap: 0.6rem;
    padding: 0.55rem 1.25rem; border-bottom: 1px solid var(--a-border);
    font-size: 0.8rem;
}
.dash-log-row:last-child { border-bottom: none; }
.dash-log-cat { font-size: 0.65rem; font-weight: 700; padding: 0.15em 0.55em; border-radius: 100px; flex-shrink: 0; white-space: nowrap; letter-spacing: 0.04em; }
.dash-log-event { flex: 1; color: var(--a-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dash-log-time  { font-size: 0.7rem; color: var(--a-text-muted); flex-shrink: 0; }

/* ── Server grid ─────────────────────────────────────────────────────────── */
.dash-srv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 1px; background: var(--a-border);
}
.dash-srv-cell {
    background: var(--a-surface); padding: 1rem 1.25rem;
    display: flex; align-items: flex-start; gap: 0.75rem;
}
.dash-srv-icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 0.1rem; }
.dash-srv-val  { font-size: 0.95rem; font-weight: 700; color: var(--a-text); line-height: 1.2; }
.dash-srv-lbl  { font-size: 0.7rem; color: var(--a-text-muted); margin-top: 0.2rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; }
.dash-srv-sub  { font-weight: 400; text-transform: none; letter-spacing: 0; }
</style>
