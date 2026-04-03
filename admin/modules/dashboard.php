<?php
// Data
$total_pages_count = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$total_posts_count = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();

$hits_by_day = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));
    $count = $db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp) = '$date'")->fetchColumn() ?: 0;
    $hits_by_day[$label] = $count;
}

$unique_visitors_today = $db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics WHERE timestamp >= date('now')")->fetchColumn();
$total_page_views      = $db->query("SELECT COUNT(*) FROM analytics")->fetchColumn();
$error_count           = $db->query("SELECT COUNT(*) FROM logs WHERE category = '404' AND timestamp >= date('now', '-7 days')")->fetchColumn();
$returning_today       = $db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics WHERE timestamp >= date('now') AND visitor_id IN (SELECT visitor_id FROM analytics WHERE timestamp < date('now'))")->fetchColumn() ?: 0;
$new_today             = max(0, $unique_visitors_today - $returning_today);
$top_pages             = $db->query("SELECT page_url, COUNT(*) as hits FROM analytics WHERE page_url NOT LIKE '%/blog/%' GROUP BY page_url ORDER BY hits DESC LIMIT 5")->fetchAll();
$top_posts             = $db->query("SELECT page_url, COUNT(*) as hits FROM analytics WHERE page_url LIKE '%/blog/%' GROUP BY page_url ORDER BY hits DESC LIMIT 5")->fetchAll();

if (!function_exists('get_dir_size')) {
    function get_dir_size($path) {
        $bytes = 0; $path = realpath($path);
        if ($path && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $f) {
                $bytes += $f->getSize();
            }
        }
        return $bytes;
    }
}
if (!function_exists('format_size')) {
    function format_size($b) {
        if ($b >= 1048576) return number_format($b/1048576, 2) . ' MB';
        if ($b >= 1024)    return number_format($b/1024, 1) . ' KB';
        return $b . ' B';
    }
}
$db_bytes            = file_exists('../db/cms.db') ? filesize('../db/cms.db') : 0;
$db_size_formatted   = format_size($db_bytes);
$webspace_formatted  = format_size(get_dir_size(__DIR__ . '/../'));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-title-bar">
    <div>
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">System performance and audience insights</div>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="index.php?view=pages&action=add" class="btn btn-outline btn-sm"><i class="bi bi-plus-circle"></i> New Page</a>
        <a href="index.php?view=blog&action=add"  class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> New Post</a>
    </div>
</div>

<!-- Stat Cards -->
<div class="a-grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-card-accent" style="background: linear-gradient(90deg, var(--a-accent), #6366f1);"></div>
        <div class="stat-label">Total Pages</div>
        <div class="stat-value"><?php echo $total_pages_count; ?></div>
        <div class="stat-sub">Published pages</div>
        <i class="bi bi-files stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background: linear-gradient(90deg, var(--a-success), #10b981);"></div>
        <div class="stat-label">Blog Posts</div>
        <div class="stat-value"><?php echo $total_posts_count; ?></div>
        <div class="stat-sub">Total posts</div>
        <i class="bi bi-journal-richtext stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background: linear-gradient(90deg, #a78bfa, #7c3aed);"></div>
        <div class="stat-label">Uniques Today</div>
        <div class="stat-value"><?php echo $unique_visitors_today; ?></div>
        <div class="stat-sub"><?php echo $new_today; ?> new &middot; <?php echo $returning_today; ?> returning</div>
        <i class="bi bi-people stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background: linear-gradient(90deg, var(--a-warning), #f97316);"></div>
        <div class="stat-label">404 Errors (7d)</div>
        <div class="stat-value"><?php echo $error_count; ?></div>
        <div class="stat-sub">Last 7 days</div>
        <i class="bi bi-exclamation-triangle stat-icon"></i>
    </div>
</div>

<!-- Charts row -->
<div class="a-grid-2 mb-3">
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-activity" style="color:var(--a-accent);"></i> Traffic Pulse (7 days)</div>
            <span class="badge badge-blue"><?php echo number_format($total_page_views); ?> total hits</span>
        </div>
        <div class="a-card-body" style="padding-top:0.5rem;">
            <canvas id="trafficPulse" style="width:100%;height:220px;"></canvas>
        </div>
    </div>
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-pie-chart" style="color:#a78bfa;"></i> Visitor Type</div>
        </div>
        <div class="a-card-body" style="display:flex; flex-direction:column; align-items:center;">
            <div style="height:180px; width:180px; position:relative; margin-bottom:1rem;">
                <canvas id="visitorChart"></canvas>
            </div>
            <div style="display:flex; flex-direction:column; gap:0.4rem; width:100%;">
                <div style="display:flex; justify-content:space-between; font-size:0.8rem;">
                    <span class="text-muted"><i class="bi bi-circle-fill" style="color:var(--a-accent); margin-right:0.35rem;"></i>New</span>
                    <span class="fw-bold"><?php echo $new_today; ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.8rem;">
                    <span class="text-muted"><i class="bi bi-circle-fill" style="color:var(--a-info); margin-right:0.35rem;"></i>Returning</span>
                    <span class="fw-bold"><?php echo $returning_today; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content + Health row -->
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:1.25rem;">
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-bar-chart-line" style="color:var(--a-accent);"></i> Top Pages</div>
        </div>
        <div class="a-card-body" style="padding:0;">
            <?php if (empty($top_pages)): ?>
                <div class="empty-state" style="padding:1.5rem;"><span class="empty-icon"><i class="bi bi-graph-up"></i></span><p class="text-sm">No data yet.</p></div>
            <?php else: foreach ($top_pages as $tp): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.65rem 1.1rem; border-bottom:1px solid var(--a-border);">
                <div class="mono text-sm" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:75%;"><?php echo htmlspecialchars($tp['page_url']); ?></div>
                <span class="badge"><?php echo $tp['hits']; ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-journal-text" style="color:var(--a-info);"></i> Trending Posts</div>
        </div>
        <div class="a-card-body" style="padding:0;">
            <?php if (empty($top_posts)): ?>
                <div class="empty-state" style="padding:1.5rem;"><span class="empty-icon"><i class="bi bi-journal"></i></span><p class="text-sm">No post data yet.</p></div>
            <?php else: foreach ($top_posts as $post): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.65rem 1.1rem; border-bottom:1px solid var(--a-border);">
                <div class="mono text-sm" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:75%;"><?php echo htmlspecialchars(str_replace('/blog/', '', $post['page_url'])); ?></div>
                <span class="badge badge-blue"><?php echo $post['hits']; ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-hdd-stack" style="color:var(--a-success);"></i> System Health</div>
        </div>
        <div class="a-card-body">
            <div class="mb-3">
                <div style="display:flex; justify-content:space-between; font-size:0.72rem; font-weight:700; color:var(--a-text-muted); margin-bottom:0.4rem; text-transform:uppercase; letter-spacing:0.06em;">
                    <span>Database</span><span><?php echo $db_size_formatted; ?></span>
                </div>
                <div style="background:var(--a-surface-2); border-radius:100px; height:5px; overflow:hidden;">
                    <div style="width:35%; height:100%; background:var(--a-info); border-radius:100px;"></div>
                </div>
            </div>
            <div class="mb-3">
                <div style="display:flex; justify-content:space-between; font-size:0.72rem; font-weight:700; color:var(--a-text-muted); margin-bottom:0.4rem; text-transform:uppercase; letter-spacing:0.06em;">
                    <span>Webspace</span><span><?php echo $webspace_formatted; ?></span>
                </div>
                <div style="background:var(--a-surface-2); border-radius:100px; height:5px; overflow:hidden;">
                    <div style="width:55%; height:100%; background:var(--a-accent); border-radius:100px;"></div>
                </div>
            </div>
            <div style="background:var(--a-surface-2); border:1px solid var(--a-border); border-radius:var(--a-radius); padding:0.75rem; text-align:center;">
                <div style="font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--a-text-muted); margin-bottom:0.25rem;">Server</div>
                <div style="font-size:0.85rem; font-weight:600;">
                    <i class="bi bi-cpu" style="margin-right:0.3rem;"></i>
                    PHP <?php echo PHP_VERSION; ?> &middot; <span style="color:var(--a-success);">Optimal</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var accentColor = getComputedStyle(document.documentElement).getPropertyValue('--a-accent').trim() || '#4f7ef8';
    var infoColor   = getComputedStyle(document.documentElement).getPropertyValue('--a-info').trim()   || '#06b6d4';

    // Traffic chart
    var pCtx = document.getElementById('trafficPulse').getContext('2d');
    var grad = pCtx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(79,126,248,0.18)');
    grad.addColorStop(1, 'rgba(79,126,248,0)');
    new Chart(pCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($hits_by_day)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($hits_by_day)); ?>,
                borderColor: accentColor, borderWidth: 2.5,
                fill: true, backgroundColor: grad,
                tension: 0.4, pointRadius: 4,
                pointBackgroundColor: '#fff', pointBorderColor: accentColor
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#7a7f95', font: { size: 11 } } },
                y: { display: false, beginAtZero: true }
            }
        }
    });

    // Doughnut chart
    var vCtx = document.getElementById('visitorChart').getContext('2d');
    new Chart(vCtx, {
        type: 'doughnut',
        data: {
            labels: ['New', 'Returning'],
            datasets: [{ data: [<?php echo $new_today; ?>, <?php echo $returning_today; ?>], backgroundColor: [accentColor, infoColor], borderWidth: 0, hoverOffset: 4 }]
        },
        options: { cutout: '78%', plugins: { legend: { display: false } }, maintainAspectRatio: false }
    });
})();
</script>
