<?php
/**
 * Template Name: Blog post
 * Description: High-contrast card layout optimized for readability and spacing.
 */

// 1. DATA FETCHING FOR SIDEBAR
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            <article class="card single-post shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                <div style="height: 4px; background: linear-gradient(90deg, #6610f2, var(--bs-primary));"></div>

                <div class="card-body p-4 p-md-5 pb-0">
                    <h1 class="h2 fw-bold text-dark mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom border-light-subtle">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                <?php echo htmlspecialchars($post['category'] ?? 'General'); ?>
                            </span>
                        </div>
                        
                        <div class="meta d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center small text-muted">
                                <i class="bi bi-person-circle me-1 text-primary opacity-75"></i>
                                <span class="fw-semibold text-dark text-capitalize"><?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?></span>
                            </div>
                            <div class="d-flex align-items-center small text-muted border-start ps-3 border-light-subtle">
                                <i class="bi bi-calendar3 me-2"></i>
                                <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body px-4 px-md-5 py-3">
                    <div class="post-content">
                        <?php echo $post['content']; ?>
                    </div>
                </div>

                <div id="comments" class="card-body p-4 p-md-5 border-top border-light-subtle bg-light-subtle">
                    <div class="mb-4">
                        <h4 class="fw-bold text-dark d-flex align-items-center">
                            <i class="bi bi-chat-left-text me-2 text-primary"></i> Community Discussion
                        </h4>
                    </div>
                    
                    <div class="p-5 text-center border border-dashed rounded-4 bg-white opacity-75">
                         <p class="text-muted mb-0 small">Comments service placeholder</p>
                    </div>
                </div>

                <div class="card-footer px-4 px-md-5 py-4 bg-white border-top border-light-subtle">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="blog.html" class="text-decoration-none small fw-bold link-primary-hover">
                            <i class="bi bi-arrow-left me-1"></i> Back to Blog
                        </a>
                        <div class="d-flex gap-3 text-muted">
                            <i class="bi bi-share small"></i>
                            <i class="bi bi-facebook small cursor-pointer"></i>
                            <i class="bi bi-twitter-x small cursor-pointer"></i>
                        </div>
                    </div>
                </div>
            </article>

        </div>
    </div>
</div>

<?php
queue_css("
.single-post {
    background-color: #fff;
}

.post-content {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #333;
}

.post-content p {
    margin-bottom: 1.2rem;
}

.post-content h2, .post-content h3 {
    font-weight: 700;
    margin-top: 1.8rem;
    margin-bottom: 1rem;
    color: #111;
}

.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.75rem;
    margin: 1.5rem 0;
    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.05);
}

.border-dashed {
    border-style: dashed !important;
}

.link-primary-hover:hover {
    color: var(--bs-primary) !important;
}

.bg-light-subtle {
    background-color: #f8f9fa !important;
}

.cursor-pointer {
    cursor: pointer;
}

.card-footer {
    border-bottom-left-radius: 1rem !important;
    border-bottom-right-radius: 1rem !important;
}
");
?>