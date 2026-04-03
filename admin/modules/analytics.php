<?php
/**
 * Modernized Analytics Module - admin/modules/analytics.php
 */

// 1. Core KPIs
$total_hits = $db->query("SELECT COUNT(*) FROM analytics")->fetchColumn();
$unique_visitors = $db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics")->fetchColumn();

// Trend Analysis
$hits_today = $db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp) = DATE('now')")->fetchColumn();
$hits_yesterday = $db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp) = DATE('now', '-1 day')")->fetchColumn();
$trend = ($hits_yesterday > 0) ? (($hits_today - $hits_yesterday) / $hits_yesterday) * 100 : 0;

// 2. Advanced Metrics
$bounces = $db->query("SELECT COUNT(*) FROM (SELECT visitor_id FROM analytics GROUP BY visitor_id HAVING COUNT(*) = 1)")->fetchColumn();
$bounce_rate = ($unique_visitors > 0) ? round(($bounces / $unique_visitors) * 100, 1) : 0;

// Traffic Composition: Human vs Bot
$bot_hits = $db->query("SELECT COUNT(*) FROM analytics WHERE browser LIKE '%bot%' OR browser LIKE '%spider%' OR browser LIKE '%crawler%'")->fetchColumn();
$human_hits = max(0, $total_hits - $bot_hits);

// REFERRER LOGIC (NEW)
$referrer_query = $db->query("
    SELECT 
        CASE 
            WHEN referrer IS NULL OR referrer = '' OR referrer = 'direct' THEN 'Direct'
            WHEN referrer LIKE '%google%' OR referrer LIKE '%bing%' OR referrer LIKE '%duckduckgo%' OR referrer LIKE '%yahoo%' OR referrer LIKE '%baidu%' THEN 'Search Engine'
            WHEN referrer LIKE '%facebook%' OR referrer LIKE '%t.co%' OR referrer LIKE '%twitter%' OR referrer LIKE '%instagram%' OR referrer LIKE '%linkedin%' OR referrer LIKE '%pinterest%' THEN 'Social Media'
            ELSE 'Referral'
        END as ref_group,
        COUNT(*) as count
    FROM analytics
    GROUP BY ref_group
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

$ref_labels = array_column($referrer_query, 'ref_group');
$ref_counts = array_column($referrer_query, 'count');

// Categorized Browser Stats
$browser_query = $db->query("
    SELECT 
        CASE 
            WHEN browser LIKE '%bot%' OR browser LIKE '%spider%' OR browser LIKE '%crawler%' THEN 'Bots'
            WHEN browser LIKE '%Edg%' THEN 'Edge'
            WHEN browser LIKE '%Firefox%' THEN 'Firefox'
            WHEN browser LIKE '%Chrome%' THEN 'Chrome'
            WHEN browser LIKE '%Safari%' THEN 'Safari'
            ELSE 'Other'
        END as browser_group,
        COUNT(*) as count
    FROM analytics
    GROUP BY browser_group
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

$browser_labels = array_column($browser_query, 'browser_group');
$browser_counts = array_column($browser_query, 'count');

// Retention: New vs Returning
$returning_count = $db->query("SELECT COUNT(*) FROM (SELECT visitor_id FROM analytics GROUP BY visitor_id HAVING COUNT(*) > 1)")->fetchColumn();
$new_count = max(0, $unique_visitors - $returning_count);

// Hourly Distribution (Last 24 Hours)
$hourly_stats = $db->query("
    SELECT STRFTIME('%H', timestamp) as hour, COUNT(*) as count 
    FROM analytics 
    WHERE timestamp >= DATETIME('now', '-24 hours')
    GROUP BY hour ORDER BY hour ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$full_hourly = [];
for ($i = 0; $i < 24; $i++) {
    $h = sprintf("%02d", $i);
    $full_hourly[$h] = $hourly_stats[$h] ?? 0;
}

// 3. Distributions
$top_pages = $db->query("SELECT page_url, COUNT(*) as count FROM analytics GROUP BY page_url ORDER BY count DESC LIMIT 5")->fetchAll();
$devices = $db->query("SELECT device, COUNT(*) as count FROM analytics GROUP BY device ORDER BY count DESC LIMIT 3")->fetchAll();

$hits_by_day = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $hits_by_day[$date] = $db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp) = '$date'")->fetchColumn() ?: 0;
}
?>

<style>
    .analytics-card { border: none; border-radius: 12px; transition: transform 0.2s; background: #fff; height: 100%; }
    .analytics-card:hover { transform: translateY(-3px); }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: #2c3e50; }
    .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #7f8c8d; }
    .trend-indicator { font-size: 0.85rem; font-weight: 600; }
    .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0dcaf0); color: white; }
    /* Small font for legend in 5-col row */
    .chart-container { position: relative; height: 180px; }
</style>

<div class="">
    <div class="a-flex gap-2">
        <div>
            <h2 class="fw-bold">Dashboard Overview</h2>
            <p class="text-muted">Full Traffic & Referral Intelligence</p>
        </div>
        <div >
            <span class="badge">
                <span class="spinner-grow spinner-grow-sm me-1"></span> Live
            </span>
        </div>
    </div>

    <div class="a-flex-between flex-wrap gap-2">
        <div class="col-md-3">
            <div class="a-card">
                <span class="stat-label">Page Views (Today)</span>
                <div class="stat-value"><?= number_format($hits_today) ?></div>
                <div class="trend-indicator <?= $trend >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $trend >= 0 ? '?' : '?' ?> <?= round(abs($trend), 1) ?>%
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="a-card">
                <span class="stat-label">Bounce Rate</span>
                <div class="stat-value"><?= $bounce_rate ?>%</div>
                <div class="progress mt-2 mx-auto" style="height: 4px; width: 80%;">
                    <div class="progress-bar bg-warning" style="width: <?= $bounce_rate ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="a-card">
                <span class="stat-label">Unique Visitors</span>
                <div class="stat-value"><?= number_format($unique_visitors) ?></div>
                <div class="text-muted">Lifetime Users</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="a-card">
                <span class="stat-label text-white opacity-75">Retention</span>
                <div class="stat-value text-white"><?= round(($returning_count / max(1, $unique_visitors)) * 100, 1) ?>%</div>
                <div class="small opacity-75">Returning Visitors</div>
            </div>
        </div>
    </div>

    <div class="a-flex-between flex-wrap gap-2">
        <div class="col">
            <div class="a-card">
                <h6 class="fw-bold">Browsers</h6>
                <canvas id="browserChart"></canvas>
            </div>
        </div>
        <div class="col">
            <div class="a-card">
                <h6 class="fw-bold">Retention</h6>
                <canvas id="retentionChart"></canvas>
            </div>
        </div>
        <div class="col">
            <div class="a-card">
                <h6 class="fw-bold">Devices</h6>
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
        <div class="col">
            <div class="a-card">
                <h6 class="fw-bold">Traffic Type</h6>
                <canvas id="trafficTypeChart"></canvas>
            </div>
        </div>
        <div class="col">
            <div class="a-card">
                <h6 class="fw-bold">Referrer Source</h6>
                <canvas id="referrerChart"></canvas>
            </div>
        </div>
    </div>

    <div class="a-flex-between flex-wrap gap-2">
        <div class="col-lg-6">
            <div class="a-card">
                <div class="a-card">
                    <h6 class="fw-bold">7-Day Traffic Trend</h6>
                </div>
                <div class="a-card"><canvas id="hitsByDay" height="250"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="a-card">
                <div class="a-card">
                    <h6 class="fw-bold">Time of Day Intensity</h6>
                </div>
                <div class="a-card"><canvas id="timeOfDayChart" height="250"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="a-card">
                <div class="a-card">
                    <h6 class="fw-bold">Peak Activity (Last 24h)</h6>
                </div>
                <div class="a-card"><canvas id="hourlyChart" height="250"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="a-card">
                <div class="a-card">
                    <h6 class="fw-bold">Most Popular Pages</h6>
                </div>
                <div class="a-card">
                    <div class="">
                        <table class="">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Path</th>
                                    <th >Visits</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_pages as $page): ?>
                                <tr>
                                    <td class="ps-4 small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($page['page_url']) ?></td>
                                    <td ><?= number_format($page['count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#7f8c8d';
    const legendStyle = { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } };

    // 1. Browser Chart
    new Chart(document.getElementById('browserChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($browser_labels) ?>,
            datasets: [{
                data: <?= json_encode($browser_counts) ?>,
                backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#6610f2'],
                cutout: '70%'
            }]
        },
        options: { plugins: { legend: legendStyle } }
    });

    // 2. Retention Chart
    new Chart(document.getElementById('retentionChart'), {
        type: 'pie',
        data: {
            labels: ['New', 'Returning'],
            datasets: [{
                data: [<?= $new_count ?>, <?= $returning_count ?>],
                backgroundColor: ['#e9ecef', '#0d6efd']
            }]
        },
        options: { plugins: { legend: legendStyle } }
    });

    // 3. Device Chart
    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($devices, 'device')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($devices, 'count')) ?>,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
                cutout: '70%'
            }]
        },
        options: { plugins: { legend: legendStyle } }
    });

    // 4. Traffic Composition
    new Chart(document.getElementById('trafficTypeChart'), {
        type: 'pie',
        data: {
            labels: ['Human', 'Bot'],
            datasets: [{
                data: [<?= $human_hits ?>, <?= $bot_hits ?>],
                backgroundColor: ['#20c997', '#6c757d']
            }]
        },
        options: { plugins: { legend: legendStyle } }
    });

    // 5. Referrer Source Chart (NEW)
    new Chart(document.getElementById('referrerChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($ref_labels) ?>,
            datasets: [{
                data: <?= json_encode($ref_counts) ?>,
                backgroundColor: ['#6610f2', '#0d6efd', '#fd7e14', '#20c997'],
                cutout: '70%'
            }]
        },
        options: { plugins: { legend: legendStyle } }
    });

    // Lines & Radar & Bars
    new Chart(document.getElementById('hitsByDay'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($hits_by_day)) ?>,
            datasets: [{ label: 'Views', data: <?= json_encode(array_values($hits_by_day)) ?>, borderColor: '#0d6efd', fill: true, backgroundColor: 'rgba(13, 110, 253, 0.05)', tension: 0.4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('timeOfDayChart'), {
        type: 'radar',
        data: {
            labels: <?= json_encode(array_keys($full_hourly)) ?>,
            datasets: [{ label: 'Intensity', data: <?= json_encode(array_values($full_hourly)) ?>, fill: true, backgroundColor: 'rgba(13, 110, 253, 0.2)', borderColor: '#0d6efd' }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { r: { ticks: { display: false } } }, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($full_hourly)) ?>,
            datasets: [{ data: <?= json_encode(array_values($full_hourly)) ?>, backgroundColor: '#0dcaf0', borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
</script>