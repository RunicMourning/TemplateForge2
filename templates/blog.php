<?php
/**
 * Template Name: Blog Index
 * Description: Clean card-based blog with top accent bars and synced sidebar styles.
 */

// 1. DATA FETCHING (Sidebar)
$categories = $db->query("SELECT category, COUNT(*) as count FROM posts WHERE status = 'published' GROUP BY category ORDER BY count DESC")->fetchAll();
$popular = $db->query("SELECT title, slug, created_at FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();



// 2. CAPTURE SIDEBAR HTML
ob_start(); ?>
<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4" style="background: #ffffff;">
    <div style="height: 4px; background: linear-gradient(90deg, #6610f2, var(--bs-primary));"></div>
    <div class="card-header bg-transparent border-0 pt-4 pb-0">
        <h6 class="text-uppercase fw-bold text-muted tracking-widest mb-1" style="font-size: 0.7rem;">Explore</h6>
        <h5 class="fw-bold d-flex align-items-center text-dark">
            <i class="bi bi-collection-play-fill me-2 text-primary"></i> Categories
        </h5>
    </div>
    <div class="card-body">
        <div class="list-group list-group-flush">
            <?php foreach ($categories as $cat): 
                $catName = !empty($cat['category']) ? $cat['category'] : 'General';
            ?>
                <a href="category-<?php echo urlencode($catName); ?>.html" 
                   class="list-group-item list-group-item-action bg-transparent px-2 d-flex justify-content-between align-items-center border-light-subtle small rounded-3 mb-1">
                    <span><i class="bi bi-chevron-right small opacity-50 me-1"></i> <?php echo htmlspecialchars($catName); ?></span>
                    <span class="badge rounded-pill bg-light text-primary border border-primary-subtle"><?php echo $cat['count']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4" style="background: #ffffff;">
    <div style="height: 4px; background: linear-gradient(90deg, #fd7e14, #ffc107);"></div>
    <div class="card-header bg-transparent border-0 pt-4 pb-0">
        <h6 class="text-uppercase fw-bold text-muted tracking-widest mb-1" style="font-size: 0.7rem;">Popular</h6>
        <h5 class="fw-bold d-flex align-items-center text-dark">
            <i class="bi bi-fire me-2 text-warning"></i> Trending Now
        </h5>
    </div>
    <div class="card-body">
        <div class="vstack gap-3">
            <?php foreach ($popular as $pop): ?>
                <div class="popular-post-item ps-3 border-start border-2 border-light-subtle">
                    <a href="blog-<?php echo $pop['slug']; ?>.html" class="text-decoration-none text-dark fw-bold d-block lh-sm mb-1 small link-primary-hover">
                        <?php echo htmlspecialchars($pop['title']); ?>
                    </a>
                    <div class="d-flex align-items-center text-muted" style="font-size: 0.65rem;">
                        <i class="bi bi-clock me-1"></i>
                        <?php echo date('M j, Y', strtotime($pop['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php 
$blog_sidebar = ob_get_clean();
set_page_sidebar($blog_sidebar); 
?>
<div class="container">
    <div class="mb-5">
        <h1 class="display-5 fw-bold mb-1">Blog</h1>
        <p class="text-muted lead mb-0">Latest news and insights from our team.</p>
    </div>

    <?php if (empty($posts)): ?>
        <div class="text-center py-5 border rounded-4 bg-white shadow-sm">
            <i class="bi bi-journal-x display-1 text-muted opacity-25"></i>
            <p class="mt-3 text-muted">No blog posts have been published yet.</p>
        </div>
    <?php else: ?>

        <div class="vstack gap-4">
            <?php foreach ($posts as $p):
                $postUrl = "blog-" . htmlspecialchars($p['slug']) . ".html";
                $content = $p['content'] ?? '';
                
                // Reading Time Logic
                $wordCount = str_word_count(strip_tags($content));
                $readingTime = ceil($wordCount / 200);
                if ($readingTime < 1) $readingTime = 1;

                // Excerpt Logic
                $pos = strpos($content, '</p>');
                $excerpt = ($pos !== false) ? strip_tags(substr($content, 0, $pos + 4)) : substr(strip_tags($content), 0, 180) . '...';
            ?>

            <article class="post-card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                <div style="height: 4px; background: linear-gradient(90deg, #6610f2, var(--bs-primary));"></div>

                <div class="post-card-header p-4 pb-3">
                    <h2 class="h4 fw-bold mb-2">
                        <a href="<?php echo $postUrl; ?>" class="text-decoration-none text-dark link-primary-hover">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </a>
                    </h2>
                    <div class="meta d-flex align-items-center flex-wrap gap-3">
                        <span class="text-capitalize small text-muted"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($p['author'] ?? 'Admin'); ?></span>
                        <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo date('M j, Y', strtotime($p['created_at'])); ?></span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo htmlspecialchars($p['category'] ?? 'General'); ?></span>
                    </div>
                </div>

                <div class="post-card-body p-4 border-top border-bottom border-light-subtle">
                    <p class="text-secondary mb-0"><?php echo htmlspecialchars($excerpt); ?></p>
                </div>

                <div class="post-card-footer px-4 py-3 d-flex justify-content-between align-items-center bg-white">
                    <div class="text-muted small">
                        <span class="me-3"><i class="bi bi-clock me-1"></i><?php echo $readingTime; ?> min read</span>
                        <span><i class="bi bi-chat-left-text me-1"></i>12 comments</span>
                    </div>
                    <div>
                        <a href="<?php echo $postUrl; ?>" class="text-decoration-none fw-bold small d-flex align-items-center link-primary-hover">
                            Read article <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </article>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
queue_css("
.post-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 2rem rgba(0,0,0,0.12) !important;
}

.link-primary-hover:hover {
    color: var(--bs-primary) !important;
}

.post-card-body p {
    line-height: 1.6;
    font-size: 0.95rem;
}

.meta .badge {
    font-weight: 500;
    font-size: 0.75rem;
}
");
?>