<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = "404 - Page Not Found";

// Setup server info
$requested_url = $_SERVER['REQUEST_URI'] ?? 'Unknown URL';
$referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct Access';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$logged_in_user = $_SESSION['user'] ?? 'Anonymous';

// --- SMART LOGGING ---
$ignore_list = ['favicon.ico', 'robots.txt', 'apple-touch-icon'];
$is_junk = false;
foreach($ignore_list as $term) {
    if(strpos($requested_url, $term) !== false) $is_junk = true;
}

if (!$is_junk) {
    $log_details = "URL: $requested_url | IP: $client_ip | Ref: $referrer | UA: $user_agent";
    log_activity($db, 'ERROR', '404 Not Found', $log_details);
}

// Requested name for suggestion engine
$page_id = $_GET['pageslug'] ?? null;
$requested_name = mb_strtolower($page_id ?: pathinfo($requested_url, PATHINFO_FILENAME));

// --- SUGGESTION ENGINE ---
$suggestions = [];
$db_pages = $db->query("SELECT title, slug, 'page' as type FROM pages")->fetchAll();
$db_posts = $db->query("SELECT title, slug, 'post' as type FROM posts WHERE status='published'")->fetchAll();
$all_options = array_merge($db_pages, $db_posts);

foreach($all_options as $opt){
    $target_slug = $opt['slug'];
    similar_text($requested_name, $target_slug, $percent);
    
    if($percent > 40) {
        $suggestions[] = [
            'title'   => $opt['title'],
            // Updated to use your new blog-slug.html format
            'url'     => ($opt['type'] == 'post') ? "blog-{$target_slug}.html" : "{$target_slug}.html",
            'percent' => round($percent),
            'type'    => $opt['type']
        ];
    }
}
usort($suggestions, fn($a, $b) => $b['percent'] <=> $a['percent']);
$suggestions = array_slice($suggestions, 0, 3);

$request_id = substr(md5($requested_url.microtime()), 0, 10);
?>

<div class="container py-5">
    <div class="text-center mb-5 animate__animated animate__fadeIn">
        <div class="mb-4">
            <i class="bi bi-exclamation-triangle text-warning display-1"></i>
        </div>
        <h1 class="display-1 fw-bold text-dark">404</h1>
        <h2 class="h3 mb-3 fw-bold">Page Not Found</h2>
        <p class="lead text-muted mx-auto mb-4" style="max-width: 600px;">
            We couldn't find <code class="text-danger"><?php echo htmlspecialchars($requested_url); ?></code>.<br>
            Don't worry, our team has been notified, but you can try searching for it below.
        </p>

        <div class="row justify-content-center mb-4">
            <div class="col-md-6">
                <form action="search.html" method="GET" class="input-group input-group-lg shadow-sm">
                    <input type="text" name="q" class="form-control border-end-0" placeholder="Search our site..." aria-label="Search">
                    <button class="btn btn-primary px-4" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-2">
            <a href="index.php" class="btn btn-link text-decoration-none text-secondary">
                <i class="bi bi-house-door me-1"></i>Back to Home
            </a>
            <button class="btn btn-link text-decoration-none text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseErrorDetails">
                <i class="bi bi-info-circle me-1"></i>Technical Info
            </button>
        </div>
    </div>

    <?php if(!empty($suggestions)): ?>
        <div class="row justify-content-center mb-5">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm bg-white overflow-hidden">
                    <div class="card-header bg-primary text-white py-3 border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-lightbulb me-2"></i>Maybe you meant...</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach($suggestions as $s): ?>
                            <a href="<?php echo $s['url']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-start-0 border-end-0">
                                <div>
                                    <span class="badge rounded-pill bg-light text-dark border me-2 small fw-normal"><?php echo ucfirst($s['type']); ?></span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($s['title']); ?></span>
                                </div>
                                <span class="text-muted small"><?php echo $s['percent']; ?>% match <i class="bi bi-chevron-right ms-1"></i></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="collapse" id="collapseErrorDetails">
        <div class="card card-body bg-dark text-white-50 border-0 font-monospace small mb-5 shadow-lg">
            <div class="row g-4 text-start">
                <div class="col-md-6">
                    <div class="text-info border-bottom border-secondary mb-2 pb-1 x-small fw-bold">SERVER CONTEXT</div>
                    <div class="d-flex justify-content-between mb-1"><span>Request ID:</span> <span class="text-white"><?php echo $request_id; ?></span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Method:</span> <span class="text-white"><?php echo $request_method; ?></span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Software:</span> <span class="text-white text-truncate ms-2"><?php echo $server_software; ?></span></div>
                </div>
                <div class="col-md-6">
                    <div class="text-info border-bottom border-secondary mb-2 pb-1 x-small fw-bold">CLIENT CONTEXT</div>
                    <div class="d-flex justify-content-between mb-1"><span>User:</span> <span class="text-white"><?php echo htmlspecialchars($logged_in_user); ?></span></div>
                    <div class="d-flex justify-content-between mb-1"><span>IP Address:</span> <span class="text-white"><?php echo $client_ip; ?></span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Referrer:</span> <span class="text-white text-truncate ms-3"><?php echo htmlspecialchars($referrer); ?></span></div>
                </div>
                <div class="col-12 mt-2">
                    <div class="text-info border-bottom border-secondary mb-2 pb-1 x-small fw-bold">USER AGENT</div>
                    <div class="text-white small lh-sm"><?php echo htmlspecialchars($user_agent); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>