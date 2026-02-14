<?php 
/** * 1. Database & Analytics Logic */ 
$total_pages_count = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn(); 
$total_posts_count = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(); 

// Traffic Pulse (Last 7 Days)
$hits_by_day = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date)); 
    $count = $db->query("SELECT COUNT(*) FROM analytics WHERE DATE(timestamp) = '$date'")->fetchColumn() ?: 0;
    $hits_by_day[$label] = $count;
}

// Analytics Totals
$unique_visitors_today = $db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics WHERE timestamp >= date('now')")->fetchColumn(); 
$total_page_views = $db->query("SELECT COUNT(*) FROM analytics")->fetchColumn();
$error_count = $db->query("SELECT COUNT(*) FROM logs WHERE category = '404' AND timestamp >= date('now', '-7 days')")->fetchColumn();

$show_high_priority_alerts = ($_SESSION['show_dashboard_alerts'] ?? false) === true;

if (isset($_POST['dismiss_high_priority_alerts'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'dashboard_high_priority_alerts')) {
        http_response_code(403);
        die('Forbidden');
    }
    $_SESSION['show_dashboard_alerts'] = false;
    $show_high_priority_alerts = false;
}

$recent_high_priority_logs = get_recent_high_priority_logs($db, 5, 24);

/** * 2. NEW VS RETURNING LOGIC 
 * We check if a visitor_id appears on days prior to 'today'
 */
$returning_today = $db->query("SELECT COUNT(DISTINCT visitor_id) FROM analytics 
    WHERE timestamp >= date('now') 
    AND visitor_id IN (SELECT visitor_id FROM analytics WHERE timestamp < date('now'))")->fetchColumn() ?: 0;
$new_today = max(0, $unique_visitors_today - $returning_today);

/** * 3. TOP CONTENT LOGIC */
$top_pages = $db->query("SELECT page_url, COUNT(*) as hits FROM analytics 
    WHERE page_url NOT LIKE '%/blog/%' 
    GROUP BY page_url ORDER BY hits DESC LIMIT 5")->fetchAll();

$top_posts = $db->query("SELECT page_url, COUNT(*) as hits FROM analytics 
    WHERE page_url LIKE '%/blog/%' 
    GROUP BY page_url ORDER BY hits DESC LIMIT 5")->fetchAll();

// Existing Storage Logic
if (!function_exists('get_dir_size')) {
    function get_dir_size($path) {
        $bytestotal = 0; $path = realpath($path);
        if($path !== false && $path != '' && file_exists($path)){
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $obj){ $bytestotal += $obj->getSize(); }
        }
        return $bytestotal;
    }
}
$db_file = '../db/cms.db';  
$db_bytes = file_exists($db_file) ? filesize($db_file) : 0;
$total_webspace_bytes = get_dir_size(__DIR__ . '/../');

if (!function_exists('format_size')) {
    function format_size($bytes) {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
$db_size_formatted = format_size($db_bytes);
$webspace_formatted = format_size($total_webspace_bytes);
?> 

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="fw-bold mb-0">Dashboard</h2>
        <p class="text-muted small mb-0">System performance and audience insights</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm border rounded-pill overflow-hidden bg-white">
            <a href="index.php?view=pages&action=add" class="btn btn-link py-2 px-3 text-primary fw-bold small text-decoration-none border-end"><i class="bi bi-plus-circle me-1"></i> Page</a>
            <a href="index.php?view=blog&action=add" class="btn btn-link py-2 px-3 text-success fw-bold small text-decoration-none"><i class="bi bi-pencil-square me-1"></i> Post</a>
        </div>
    </div>
</div>

<?php if ($show_high_priority_alerts && !empty($recent_high_priority_logs)): ?>
<div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4" role="alert">
    <div>
        <div class="fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>High-priority alerts in the last 24 hours</div>
        <ul class="mb-0 small ps-3">
            <?php foreach ($recent_high_priority_logs as $alert): ?>
                <li class="mb-1">
                    <span class="fw-semibold"><?php echo htmlspecialchars($alert['event']); ?></span>
                    <span class="text-dark-emphasis">(<?php echo htmlspecialchars($alert['category']); ?>)</span>
                    <span class="text-muted">— <?php echo htmlspecialchars($alert['user'] ?: 'Anonymous'); ?> @ <?php echo date('Y-m-d H:i', strtotime($alert['timestamp'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <form method="POST" class="m-0">
        <?php echo csrf_input('dashboard_high_priority_alerts'); ?>
        <button type="submit" name="dismiss_high_priority_alerts" class="btn btn-outline-light btn-sm rounded-pill px-3">
            Dismiss
        </button>
    </form>
</div>
<?php endif; ?>

<div class="row g-3 mb-4"> 
    <div class="col-md-3"> 
        <div class="card bg-primary text-white border-0 shadow-sm rounded-4 overflow-hidden position-relative"> 
            <div class="card-body py-4 z-1 position-relative"> 
                <h6 class="text-uppercase x-small fw-bold opacity-75 mb-1">Total Pages</h6> 
                <h2 class="fw-bold mb-0"><?php echo $total_pages_count; ?></h2> 
            </div> 
            <i class="bi bi-files position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-25" style="font-size: 4rem;"></i>
        </div> 
    </div> 
    <div class="col-md-3"> 
        <div class="card bg-success text-white border-0 shadow-sm rounded-4 overflow-hidden position-relative"> 
            <div class="card-body py-4 z-1 position-relative"> 
                <h6 class="text-uppercase x-small fw-bold opacity-75 mb-1">Blog Posts</h6> 
                <h2 class="fw-bold mb-0"><?php echo $total_posts_count; ?></h2> 
            </div> 
            <i class="bi bi-journal-richtext position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-25" style="font-size: 4rem;"></i>
        </div> 
    </div> 
    <div class="col-md-3"> 
        <div class="card bg-purple text-white border-0 shadow-sm rounded-4 overflow-hidden position-relative" style="background-color: #6f42c1;"> 
            <div class="card-body py-4 z-1 position-relative"> 
                <h6 class="text-uppercase x-small fw-bold opacity-75 mb-1">Uniques Today</h6> 
                <h2 class="fw-bold mb-0"><?php echo $unique_visitors_today; ?></h2> 
            </div> 
            <i class="bi bi-people position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-25" style="font-size: 4rem;"></i>
        </div> 
    </div> 
    <div class="col-md-3"> 
        <div class="card bg-warning text-dark border-0 shadow-sm rounded-4 overflow-hidden position-relative"> 
            <div class="card-body py-4 z-1 position-relative"> 
                <h6 class="text-uppercase x-small fw-bold opacity-75 mb-1">404 Errors</h6> 
                <h2 class="fw-bold mb-0"><?php echo $error_count; ?></h2> 
            </div> 
            <i class="bi bi-exclamation-triangle position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-10" style="font-size: 4rem;"></i>
        </div> 
    </div> 
</div> 

<div class="row g-4 mb-4">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">Traffic Pulse</h6>
                <div class="badge bg-light text-primary border rounded-pill px-3 py-2 small fw-bold">
                    <?php echo number_format($total_page_views); ?> Total Hits
                </div>
            </div>
            <div class="card-body p-0 px-2 pb-2">
                <canvas id="trafficPulse" style="width: 100%; height: 300px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 text-center">
                <h6 class="fw-bold mb-0 text-dark">Visitor Type</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center p-4">
                <div style="height: 200px;">
                    <canvas id="visitorChart"></canvas>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between x-small mb-1">
                        <span class="text-muted"><i class="bi bi-circle-fill text-primary me-1"></i> New</span>
                        <span class="fw-bold"><?php echo $new_today; ?></span>
                    </div>
                    <div class="d-flex justify-content-between x-small">
                        <span class="text-muted"><i class="bi bi-circle-fill text-info me-1"></i> Returning</span>
                        <span class="fw-bold"><?php echo $returning_today; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h6 class="fw-bold mb-0">Top Pages</h6>
            </div>
            <div class="card-body px-4">
                <?php foreach($top_pages as $tp): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-truncate me-2 small fw-medium text-dark" style="max-width: 70%;"><?php echo $tp['page_url']; ?></div>
                    <span class="badge bg-light text-dark rounded-pill"><?php echo $tp['hits']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h6 class="fw-bold mb-0">Trending Posts</h6>
            </div>
            <div class="card-body px-4">
                <?php foreach($top_posts as $post): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-truncate me-2 small fw-medium text-dark" style="max-width: 70%;"><?php echo str_replace('/blog/', '', $post['page_url']); ?></div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill"><?php echo $post['hits']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h6 class="fw-bold mb-0">System Health</h6>
            </div>
            <div class="card-body px-4">
                <div class="mb-4">
                    <div class="d-flex justify-content-between x-small fw-bold text-muted mb-1">
                        <span>DATABASE</span>
                        <span><?php echo $db_size_formatted; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;"><div class="progress-bar bg-info" style="width: 35%"></div></div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between x-small fw-bold text-muted mb-1">
                        <span>STORAGE</span>
                        <span><?php echo $webspace_formatted; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;"><div class="progress-bar bg-primary" style="width: 55%"></div></div>
                </div>
                <div class="p-3 bg-light rounded-3 text-center">
                    <div class="x-small text-muted mb-1 fw-bold text-uppercase">Server Status</div>
                    <div class="small text-dark fw-bold"><i class="bi bi-cpu me-1"></i> PHP <?php echo PHP_VERSION; ?> | <span class="text-success">Optimal</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Traffic Pulse Line Chart
    const pulseCtx = document.getElementById('trafficPulse').getContext('2d');
    const pulseGradient = pulseCtx.createLinearGradient(0, 0, 0, 400);
    pulseGradient.addColorStop(0, 'rgba(13, 110, 253, 0.15)');
    pulseGradient.addColorStop(1, 'rgba(13, 110, 253, 0)');

    new Chart(pulseCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($hits_by_day)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($hits_by_day)); ?>,
                borderColor: '#0d6efd',
                borderWidth: 3,
                fill: true,
                backgroundColor: pulseGradient,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#adb5bd' } },
                y: { display: false, beginAtZero: true }
            }
        }
    });

    // Visitor Doughnut Chart
    const visitorCtx = document.getElementById('visitorChart').getContext('2d');
    new Chart(visitorCtx, {
        type: 'doughnut',
        data: {
            labels: ['New', 'Returning'],
            datasets: [{
                data: [<?php echo $new_today; ?>, <?php echo $returning_today; ?>],
                backgroundColor: ['#0d6efd', '#0dcaf0'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '80%',
            plugins: { legend: { display: false } },
            maintainAspectRatio: false
        }
    });
</script>

<style>
    .x-small { font-size: 0.72rem; }
    .rounded-4 { border-radius: 1rem !important; }
    .card { border: 1px solid rgba(0,0,0,0.03) !important; }
    .progress { background-color: #f0f2f5; border-radius: 10px; }
</style>