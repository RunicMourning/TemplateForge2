<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$requested_url  = $_SERVER['REQUEST_URI']      ?? 'Unknown URL';
$referrer       = $_SERVER['HTTP_REFERER']     ?? 'Direct Access';
$client_ip      = $_SERVER['REMOTE_ADDR']      ?? 'Unknown';
$user_agent     = $_SERVER['HTTP_USER_AGENT']  ?? 'Unknown';
$request_method = $_SERVER['REQUEST_METHOD']   ?? 'Unknown';
$server_software= $_SERVER['SERVER_SOFTWARE']  ?? 'Unknown';
$logged_in_user = $_SESSION['user']            ?? 'Anonymous';

// Smart logging — skip junk requests
$ignore_list = ['favicon.ico', 'robots.txt', 'apple-touch-icon'];
$is_junk = false;
foreach ($ignore_list as $term) {
    if (strpos($requested_url, $term) !== false) { $is_junk = true; break; }
}
if (!$is_junk) {
    log_activity($db, 'ERROR', '404 Not Found',
        "URL: $requested_url | IP: $client_ip | Ref: $referrer | UA: $user_agent");
}

// Suggestion engine
$page_id        = $_GET['pageslug'] ?? null;
$requested_name = mb_strtolower($page_id ?: pathinfo($requested_url, PATHINFO_FILENAME));
$suggestions    = [];
$all_options    = array_merge(
    $db->query("SELECT title, slug, 'page' as type FROM pages")->fetchAll(),
    $db->query("SELECT title, slug, 'post' as type FROM posts WHERE status='published'")->fetchAll()
);
foreach ($all_options as $opt) {
    similar_text($requested_name, $opt['slug'], $pct);
    if ($pct > 40) {
        $suggestions[] = [
            'title'   => $opt['title'],
            'url'     => ($opt['type'] === 'post') ? "blog-{$opt['slug']}.html" : "{$opt['slug']}.html",
            'percent' => round($pct),
            'type'    => $opt['type'],
        ];
    }
}
usort($suggestions, fn($a, $b) => $b['percent'] <=> $a['percent']);
$suggestions = array_slice($suggestions, 0, 3);
$request_id  = substr(md5($requested_url . microtime()), 0, 10);
?>

<div style="text-align: center; padding: 3rem 0 2rem;">
    <i class="bi bi-exclamation-triangle" style="font-size: 3.5rem; color: var(--tf-accent-2); display: block; margin-bottom: 1rem;"></i>
    <h1 style="font-family: var(--tf-font-display); font-size: clamp(4rem, 12vw, 7rem); font-weight: 900; line-height: 1; margin-bottom: 0.5rem; color: var(--tf-accent);">404</h1>
    <h2 style="font-family: var(--tf-font-display); margin-bottom: 1rem;">Page Not Found</h2>
    <p class="text-muted" style="max-width: 520px; margin: 0 auto 2rem; line-height: 1.7;">
        We couldn&rsquo;t find <code><?php echo htmlspecialchars($requested_url); ?></code>.<br>
        Try searching for what you need, or head back home.
    </p>

    <div style="max-width: 440px; margin: 0 auto 1.5rem;">
        <form action="search.html" method="GET">
            <div class="input-group">
                <input type="text" name="q" placeholder="Search the site&hellip;" aria-label="Search">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>

    <div class="flex justify-center gap-2 flex-wrap">
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-house-door"></i> Home</a>
        <button class="btn btn-ghost" onclick="document.getElementById('errDetails').classList.toggle('hidden')">
            <i class="bi bi-info-circle"></i> Technical Info
        </button>
    </div>
</div>

<?php if (!empty($suggestions)): ?>
<div style="max-width: 560px; margin: 0 auto 2.5rem;">
    <div class="card">
        <div class="card-accent"></div>
        <div class="p-3">
            <div class="section-label" style="margin-bottom: 0.85rem;"><i class="bi bi-lightbulb"></i> Maybe you meant&hellip;</div>
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <?php foreach ($suggestions as $s): ?>
                <a href="<?php echo htmlspecialchars($s['url']); ?>"
                   style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.75rem; border-radius: var(--tf-radius); color: var(--tf-text); text-decoration: none; transition: background 0.15s; border: 1px solid var(--tf-border);"
                   onmouseover="this.style.background='var(--tf-surface-2)';"
                   onmouseout="this.style.background='';">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span class="badge"><?php echo ucfirst($s['type']); ?></span>
                        <span class="fw-semibold"><?php echo htmlspecialchars($s['title']); ?></span>
                    </div>
                    <span class="text-muted text-small"><?php echo $s['percent']; ?>% match <i class="bi bi-chevron-right"></i></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="errDetails" class="hidden" style="max-width: 600px; margin: 0 auto 2.5rem;">
    <div style="background: #0f1117; border: 1px solid #2a2d3e; border-radius: var(--tf-radius-lg); padding: 1.5rem; font-family: monospace; font-size: 0.8rem; color: rgba(255,255,255,0.5);">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem;">
            <div>
                <div style="color: #4f7ef8; font-weight: 700; margin-bottom: 0.5rem; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em;">Server Context</div>
                <div>Request ID: <span style="color:#fff;"><?php echo $request_id; ?></span></div>
                <div>Method: <span style="color:#fff;"><?php echo $request_method; ?></span></div>
                <div>Software: <span style="color:#fff;"><?php echo htmlspecialchars(substr($server_software, 0, 30)); ?></span></div>
            </div>
            <div>
                <div style="color: #4f7ef8; font-weight: 700; margin-bottom: 0.5rem; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em;">Client Context</div>
                <div>User: <span style="color:#fff;"><?php echo htmlspecialchars($logged_in_user); ?></span></div>
                <div>IP: <span style="color:#fff;"><?php echo htmlspecialchars($client_ip); ?></span></div>
                <div>Referrer: <span style="color:#fff;"><?php echo htmlspecialchars(substr($referrer, 0, 30)); ?>&hellip;</span></div>
            </div>
        </div>
        <div style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
            <div style="color: #4f7ef8; font-weight: 700; margin-bottom: 0.35rem; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em;">User Agent</div>
            <div style="color: #fff; word-break: break-all; line-height: 1.5;"><?php echo htmlspecialchars($user_agent); ?></div>
        </div>
    </div>
</div>
