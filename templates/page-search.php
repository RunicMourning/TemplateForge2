<?php
$query   = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if (!empty($query)) {
    $term = "%$query%";
    $stmt = $db->prepare("SELECT title, slug, content, 'page' as type FROM pages WHERE title LIKE ? OR content LIKE ? LIMIT 10");
    $stmt->execute([$term, $term]);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT title, slug, content, 'post' as type FROM posts WHERE (title LIKE ? OR content LIKE ?) AND status = 'published' LIMIT 10");
    $stmt->execute([$term, $term]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = array_merge($pages, $posts);
}
?>

<h1 class="fw-bold mb-4" style="font-family: var(--tf-font-display);">Search Results</h1>

<div class="mb-4">
    <form action="search.html" method="GET">
        <div class="input-group">
            <input type="text" name="q" placeholder="Search for something&hellip;" value="<?php echo htmlspecialchars($query); ?>">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>
</div>

<?php if (empty($query)): ?>
    <div class="empty-state">
        <span class="empty-icon"><i class="bi bi-search"></i></span>
        <p>Enter a keyword above to search the site.</p>
    </div>
<?php elseif (empty($results)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        No results found for &ldquo;<strong><?php echo htmlspecialchars($query); ?></strong>&rdquo;.
        Try different keywords or check your spelling.
    </div>
<?php else: ?>
    <div class="section-label mb-3">
        <i class="bi bi-search"></i>
        <?php echo count($results); ?> match<?php echo count($results) !== 1 ? 'es' : ''; ?> found
    </div>
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($results as $item):
            $url = ($item['type'] === 'post') ? "blog-{$item['slug']}.html" : "{$item['slug']}.html";
            $snippet = substr(strip_tags($item['content']), 0, 160);
        ?>
        <div class="post-card">
            <div class="post-card-accent"></div>
            <div class="post-card-body">
                <div class="post-meta">
                    <span class="badge"><?php echo ucfirst($item['type']); ?></span>
                </div>
                <div class="post-title">
                    <a href="<?php echo $url; ?>"><?php echo htmlspecialchars($item['title']); ?></a>
                </div>
                <?php if ($snippet): ?>
                    <p class="post-excerpt"><?php echo htmlspecialchars($snippet); ?>&hellip;</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
