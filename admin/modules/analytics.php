<?php
/**
 * Analytics — Intelligence
 * Layer B: Application. Supports date range filtering and drill-down.
 * Drill-down: ?view=analytics&drill=page|referrer|browser|device&val=...
 */

// ── Date range ───────────────────────────────────────────────────
$range_options = ['7' => '7 Days', '30' => '30 Days', '90' => '90 Days', '0' => 'All Time'];
$range = in_array($_GET['range'] ?? '30', array_keys($range_options)) ? ($_GET['range'] ?? '30') : '30';
$range_label = $range_options[$range];

if ($range === '0') {
    $where_date  = '';
    $where_date_prev = '';
} else {
    $where_date      = "WHERE timestamp >= datetime('now', '-{$range} days')";
    $prev            = (int)$range * 2;
    $where_date_prev = "WHERE timestamp >= datetime('now', '-{$prev} days') AND timestamp < datetime('now', '-{$range} days')";
}

$and_date = $where_date ? str_replace('WHERE ', 'AND ', $where_date) : '';

// ── Drill-down mode ──────────────────────────────────────────────
$drill     = $_GET['drill'] ?? null;
$drill_val = $_GET['val']   ?? null;
$valid_drills = ['page', 'referrer', 'browser', 'device'];
if ($drill && !in_array($drill, $valid_drills)) { $drill = null; $drill_val = null; }

// ── Build range URL helper ────────────────────────────────────────
function range_url($r, $d = null, $v = null) {
    $url = 'index.php?view=analytics&range=' . $r;
    if ($d && $v) $url .= '&drill=' . urlencode($d) . '&val=' . urlencode($v);
    return $url;
}

// ── KPI queries ──────────────────────────────────────────────────
$views         = $db->query("SELECT COUNT(*) FROM analytics $where_date")->fetchColumn();
$uniques       = $db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics $where_date")->fetchColumn();
$sessions      = $db->query("SELECT COUNT(DISTINCT session_id) FROM analytics $where_date")->fetchColumn();
$bounces       = (int)$db->query("SELECT COUNT(*) FROM (SELECT session_id FROM analytics $where_date GROUP BY session_id HAVING COUNT(*) = 1)")->fetchColumn();
$bounce_rate   = $sessions > 0 ? round(($bounces / $sessions) * 100, 1) : 0;
$avg_daily     = ($range === '0' || $range === '') ? $views : round($views / max(1, (int)$range), 1);

// Trend vs previous period
$views_prev    = $db->query("SELECT COUNT(*) FROM analytics $where_date_prev")->fetchColumn();
$uniques_prev  = $db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics $where_date_prev")->fetchColumn();
$trend_views   = $views_prev > 0   ? round((($views   - $views_prev)   / $views_prev)   * 100, 1) : 0;
$trend_uniques = $uniques_prev > 0 ? round((($uniques - $uniques_prev) / $uniques_prev) * 100, 1) : 0;

// ── Traffic over time ─────────────────────────────────────────────
$traffic_days = min((int)$range ?: 30, 90);
$traffic = [];
for ($i = $traffic_days - 1; $i >= 0; $i--) {
    $d     = date('Y-m-d', strtotime("-$i days"));
    $label = $traffic_days <= 14 ? date('M j', strtotime($d)) : date('M j', strtotime($d));
    $traffic[$label] = (int)$db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp) = '$d'")->fetchColumn();
}

// ── Segmentation ─────────────────────────────────────────────────
$top_pages = $db->query(
    "SELECT page_url as label, COUNT(*) as hits FROM analytics $where_date
     GROUP BY page_url ORDER BY hits DESC LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$top_referrers = $db->query(
    "SELECT 
        CASE 
            WHEN referrer IS NULL OR referrer = '' OR referrer = 'Direct' THEN 'Direct'
            WHEN referrer LIKE '%google%' OR referrer LIKE '%bing%' OR referrer LIKE '%duckduckgo%' THEN 'Search Engine'
            WHEN referrer LIKE '%facebook%' OR referrer LIKE '%twitter%' OR referrer LIKE '%instagram%' OR referrer LIKE '%linkedin%' THEN 'Social Media'
            ELSE referrer
        END as label,
        COUNT(*) as hits
     FROM analytics $where_date
     GROUP BY label ORDER BY hits DESC LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

$top_browsers = $db->query(
    "SELECT browser as label, COUNT(*) as hits FROM analytics $where_date
     GROUP BY browser ORDER BY hits DESC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$top_devices = $db->query(
    "SELECT device as label, COUNT(*) as hits FROM analytics $where_date
     GROUP BY device ORDER BY hits DESC LIMIT 4"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Drill-down queries ────────────────────────────────────────────
if ($drill && $drill_val) {
    $col_map = ['page' => 'page_url', 'referrer' => 'referrer', 'browser' => 'browser', 'device' => 'device'];
    $drill_col = $col_map[$drill];

    // Traffic over time for this specific dimension
    $drill_traffic = [];
    for ($i = $traffic_days - 1; $i >= 0; $i--) {
        $d     = date('Y-m-d', strtotime("-$i days"));
        $label = date('M j', strtotime($d));
        $stmt  = $db->prepare("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp) = ? AND $drill_col = ?");
        $stmt->execute([$d, $drill_val]);
        $drill_traffic[$label] = (int)$stmt->fetchColumn();
    }

    // Related data
    if ($drill === 'page') {
        $stmt = $db->prepare("SELECT referrer, COUNT(*) as hits FROM analytics $where_date AND page_url = ? GROUP BY referrer ORDER BY hits DESC LIMIT 8");
        $stmt->execute([$drill_val]);
    } elseif ($drill === 'referrer') {
        $stmt = $db->prepare("SELECT page_url as referrer, COUNT(*) as hits FROM analytics $where_date AND referrer = ? GROUP BY page_url ORDER BY hits DESC LIMIT 8");
        $stmt->execute([$drill_val]);
    } else {
        $stmt = $db->prepare("SELECT page_url as referrer, COUNT(*) as hits FROM analytics $where_date AND $drill_col = ? GROUP BY page_url ORDER BY hits DESC LIMIT 8");
        $stmt->execute([$drill_val]);
    }
    $drill_related = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $drill_views   = array_sum($drill_traffic);
    $drill_uniq_q  = $db->prepare("SELECT COUNT(DISTINCT visitor_id) FROM analytics $where_date AND $drill_col = ?");
    $drill_uniq_q->execute([$drill_val]);
    $drill_uniques = $drill_uniq_q->fetchColumn();
}

// ── Helper: horizontal bar ────────────────────────────────────────
function bar_list(array $rows, string $drill_type, string $range, int $max = 0): string {
    if (empty($rows)) return '<p style="color:var(--a-text-muted); padding:1rem; font-size:0.85rem;">No data yet.</p>';
    $max = $max ?: max(array_column($rows, 'hits'));
    $out = '<div class="bar-list">';
    foreach ($rows as $row) {
        $pct   = $max > 0 ? round(($row['hits'] / $max) * 100) : 0;
        $label = htmlspecialchars($row['label']);
        $hits  = number_format($row['hits']);
        $href  = htmlspecialchars(range_url($range, $drill_type, $row['label']));
        $out  .= "<a href=\"{$href}\" class=\"bar-row\">
            <div class=\"bar-label\" title=\"{$label}\">{$label}</div>
            <div class=\"bar-track\"><div class=\"bar-fill\" style=\"width:{$pct}%\"></div></div>
            <div class=\"bar-hits\">{$hits}</div>
        </a>";
    }
    $out .= '</div>';
    return $out;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if ($drill && $drill_val): ?>
<!-- ══ DRILL-DOWN VIEW ══════════════════════════════════════════ -->

<div class="page-title-bar">
    <div>
        <div class="page-title"><?php echo ucfirst($drill); ?> Detail</div>
        <div class="page-subtitle" style="font-family:monospace; font-size:0.8rem;"><?php echo htmlspecialchars($drill_val); ?></div>
    </div>
    <a href="<?php echo htmlspecialchars(range_url($range)); ?>" class="btn btn-ghost btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Analytics
    </a>
</div>

<!-- Drill KPIs -->
<div class="a-grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,var(--a-accent),#6366f1);"></div>
        <div class="stat-label">Views</div>
        <div class="stat-value"><?php echo number_format($drill_views); ?></div>
        <div class="stat-sub">in <?php echo $range_label; ?></div>
        <i class="bi bi-eye stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,#10b981,#34d399);"></div>
        <div class="stat-label">Unique Visitors</div>
        <div class="stat-value"><?php echo number_format($drill_uniques); ?></div>
        <div class="stat-sub">distinct visitors</div>
        <i class="bi bi-people stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,#8b5cf6,#a78bfa);"></div>
        <div class="stat-label">Dimension</div>
        <div class="stat-value" style="font-size:1rem;"><?php echo ucfirst($drill); ?></div>
        <div class="stat-sub">drill type</div>
        <i class="bi bi-funnel stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,#f97316,#fb923c);"></div>
        <div class="stat-label">Range</div>
        <div class="stat-value" style="font-size:1rem;"><?php echo $range_label; ?></div>
        <div class="stat-sub">selected period</div>
        <i class="bi bi-calendar3 stat-icon"></i>
    </div>
</div>

<div class="a-grid-2 mb-3">
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-activity" style="color:var(--a-accent);"></i> Traffic Over Time</div>
        </div>
        <div class="a-card-body" style="padding-top:0.25rem;">
            <canvas id="drillChart" height="180"></canvas>
        </div>
    </div>
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-list-ul" style="color:var(--a-accent);"></i> <?php echo $drill === 'page' ? 'Top Referrers to this Page' : 'Top Pages'; ?></div>
        </div>
        <div class="a-card-body" style="padding:0.5rem 0.75rem;">
            <?php echo bar_list($drill_related, $drill === 'page' ? 'referrer' : 'page', $range); ?>
        </div>
    </div>
</div>

<script>
(function(){
    var ctx    = document.getElementById('drillChart').getContext('2d');
    var accent = getComputedStyle(document.documentElement).getPropertyValue('--a-accent').trim() || '#4f7ef8';
    var grad   = ctx.createLinearGradient(0, 0, 0, 180);
    grad.addColorStop(0, 'rgba(79,126,248,0.18)');
    grad.addColorStop(1, 'rgba(79,126,248,0)');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($drill_traffic)); ?>,
            datasets: [{ data: <?php echo json_encode(array_values($drill_traffic)); ?>, backgroundColor: accent, borderRadius: 4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#7a7f95', font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, border: { display: false }, ticks: { color: '#7a7f95', font: { size: 10 } } }
            }
        }
    });
})();
</script>

<?php else: ?>
<!-- ══ MAIN ANALYTICS VIEW ══════════════════════════════════════ -->

<div class="page-title-bar">
    <div>
        <div class="page-title">Analytics</div>
        <div class="page-subtitle">Traffic &amp; audience intelligence</div>
    </div>
    <!-- Date range tabs -->
    <div class="range-tabs">
        <?php foreach ($range_options as $val => $label): ?>
        <a href="<?php echo htmlspecialchars(range_url($val)); ?>"
           class="range-tab <?php echo $range === $val ? 'range-tab--active' : ''; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- KPI row -->
<div class="a-grid-4 mb-3">
    <?php
    function kpi_trend(float $pct): string {
        $dir   = $pct >= 0 ? 'up' : 'down';
        $color = $pct >= 0 ? '#16a34a' : '#dc2626';
        return "<span style=\"color:{$color}; font-size:0.78rem; display:flex; align-items:center; gap:0.2rem;\">
            <i class=\"bi bi-arrow-{$dir}\"></i>" . abs($pct) . "%</span>";
    }
    ?>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,#4f7ef8,#6366f1);"></div>
        <div class="stat-label">Page Views</div>
        <div class="stat-value"><?php echo number_format($views); ?></div>
        <?php echo kpi_trend($trend_views); ?>
        <i class="bi bi-eye stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,#10b981,#34d399);"></div>
        <div class="stat-label">Unique Visitors</div>
        <div class="stat-value"><?php echo number_format($uniques); ?></div>
        <?php echo kpi_trend($trend_uniques); ?>
        <i class="bi bi-people stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,#f97316,#fb923c);"></div>
        <div class="stat-label">Bounce Rate</div>
        <div class="stat-value"><?php echo $bounce_rate; ?>%</div>
        <div class="stat-sub"><?php echo number_format($sessions); ?> sessions</div>
        <i class="bi bi-arrow-return-left stat-icon"></i>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:linear-gradient(90deg,#8b5cf6,#a78bfa);"></div>
        <div class="stat-label">Avg Daily Views</div>
        <div class="stat-value"><?php echo number_format($avg_daily, 1); ?></div>
        <div class="stat-sub">over <?php echo $range_label; ?></div>
        <i class="bi bi-graph-up stat-icon"></i>
    </div>
</div>

<!-- Traffic trend chart -->
<div class="a-card mb-3">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-activity" style="color:var(--a-accent);"></i> Traffic Trend — <?php echo $range_label; ?></div>
        <span class="badge"><?php echo number_format($views); ?> total views</span>
    </div>
    <div class="a-card-body" style="padding-top:0.25rem;">
        <canvas id="trafficChart" height="160"></canvas>
    </div>
</div>

<!-- Segmentation row -->
<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.25rem; margin-bottom:1.25rem;">
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-file-earmark-bar-graph" style="color:var(--a-accent);"></i> Top Pages</div>
        </div>
        <div class="a-card-body" style="padding:0.5rem 0.75rem;">
            <?php echo bar_list($top_pages, 'page', $range); ?>
        </div>
    </div>
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-signpost-split" style="color:#8b5cf6;"></i> Referrers</div>
        </div>
        <div class="a-card-body" style="padding:0.5rem 0.75rem;">
            <?php echo bar_list($top_referrers, 'referrer', $range); ?>
        </div>
    </div>
    <div class="a-card">
        <div class="a-card-header">
            <div class="a-card-title"><i class="bi bi-browser-chrome" style="color:#f97316;"></i> Browsers &amp; Devices</div>
        </div>
        <div class="a-card-body" style="padding:0.5rem 0.75rem;">
            <?php echo bar_list($top_browsers, 'browser', $range); ?>
            <div style="border-top:1px solid var(--a-border); margin:0.5rem 0;"></div>
            <?php echo bar_list($top_devices, 'device', $range); ?>
        </div>
    </div>
</div>

<script>
(function(){
    var accent = getComputedStyle(document.documentElement).getPropertyValue('--a-accent').trim() || '#4f7ef8';
    var ctx    = document.getElementById('trafficChart').getContext('2d');
    var grad   = ctx.createLinearGradient(0, 0, 0, 160);
    grad.addColorStop(0, 'rgba(79,126,248,0.18)');
    grad.addColorStop(1, 'rgba(79,126,248,0)');

    // Thin data down for readability when range is large
    var rawLabels = <?php echo json_encode(array_keys($traffic)); ?>;
    var rawData   = <?php echo json_encode(array_values($traffic)); ?>;
    var step = rawLabels.length > 45 ? 7 : rawLabels.length > 20 ? 3 : 1;
    var labels = [], data = [];
    for (var i = 0; i < rawLabels.length; i += step) {
        labels.push(rawLabels[i]);
        data.push(rawData.slice(i, i + step).reduce(function(a, b){ return a + b; }, 0));
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                borderColor: accent, borderWidth: 2.5,
                fill: true, backgroundColor: grad,
                tension: 0.4, pointRadius: rawLabels.length > 20 ? 0 : 4,
                pointBackgroundColor: '#fff', pointBorderColor: accent
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#7a7f95', font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false }, ticks: { color: '#7a7f95', font: { size: 11 } } }
            }
        }
    });
})();
</script>

<?php endif; ?>
