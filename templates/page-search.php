<?php
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if (!empty($query)) {
    $search_term = "%$query%";

    // 1. Search Pages
    $stmt = $db->prepare("SELECT title, slug, content, 'page' as type FROM pages WHERE title LIKE ? OR content LIKE ? LIMIT 10");
    $stmt->execute([$search_term, $search_term]);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Search Blog Posts
    $stmt = $db->prepare("SELECT title, slug, content, 'post' as type FROM posts WHERE (title LIKE ? OR content LIKE ?) AND status = 'published' LIMIT 10");
    $stmt->execute([$search_term, $search_term]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = array_merge($pages, $posts);
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-4">Search Results</h1>
            
            <form action="search.html" method="GET" class="mb-5">
                <div class="input-group input-group-lg shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Search for something..." value="<?php echo htmlspecialchars($query); ?>">
                    <button class="btn btn-primary px-4" type="submit">Search</button>
                </div>
            </form>

            <?php if (empty($query)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">Enter a keyword above to search our site.</p>
                </div>
            <?php elseif (empty($results)): ?>
                <div class="alert alert-light border text-center py-5">
                    <i class="bi bi-emoji-frown display-4 text-muted"></i>
                    <p class="mt-3 mb-0 lead">No results found for "<strong><?php echo htmlspecialchars($query); ?></strong>"</p>
                    <small class="text-muted">Try using different keywords or checking your spelling.</small>
                </div>
            <?php else: ?>
                <p class="mb-4 text-muted small uppercase fw-bold ls-1"><?php echo count($results); ?> matches found</p>
                
                <div class="d-flex flex-column gap-4">
                    <?php foreach ($results as $item): 
                        $url = ($item['type'] == 'post') ? "blog-{$item['slug']}.html" : "{$item['slug']}.html";
                        $icon = ($item['type'] == 'post') ? "bi-journal-text" : "bi-file-earmark";
                        $badge_class = ($item['type'] == 'post') ? "bg-info-subtle text-info" : "bg-secondary-subtle text-secondary";
                    ?>
                        <div class="card border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge <?php echo $badge_class; ?> me-2 small fw-normal">
                                        <i class="bi <?php echo $icon; ?> me-1"></i> <?php echo ucfirst($item['type']); ?>
                                    </span>
                                </div>
                                <h3 class="h4 fw-bold">
                                    <a href="<?php echo $url; ?>" class="text-decoration-none text-dark"><?php echo htmlspecialchars($item['title']); ?></a>
                                </h3>
                                <p class="text-muted mb-0 small">
                                    <?php echo substr(strip_tags($item['content']), 0, 200); ?>...
                                </p>
                                <a href="<?php echo $url; ?>" class="btn btn-link p-0 mt-2 text-primary fw-bold text-decoration-none small">
                                    Read More <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>