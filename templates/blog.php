<?php
/**
 * Template Name: Blog Index
 * Description: Editorial blog listing with sidebar.
 */

// 1. Data
$categories = $db->query("SELECT category, COUNT(*) as count FROM posts WHERE status = 'published' GROUP BY category ORDER BY count DESC")->fetchAll();
$popular     = $db->query("SELECT title, slug, created_at FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// 2. Sidebar
ob_start(); ?>

<div class="widget-card mb-3">
    <div class="card-accent"></div>
    <div class="p-3">
        <div class="widget-title"><i class="bi bi-collection-play-fill"></i> Categories</div>
        <?php if (empty($categories)): ?>
            <p class="text-muted text-small">No categories yet.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <?php foreach ($categories as $cat):
                    $catName = !empty($cat['category']) ? $cat['category'] : 'General';
                ?>
                <a href="category-<?php echo urlencode($catName); ?>.html"
                   style="display: flex; justify-content: space-between; align-items: center; padding: 0.45rem 0.6rem; border-radius: var(--tf-radius); color: var(--tf-text-muted); font-size: 0.875rem; text-decoration: none; transition: background 0.15s, color 0.15s;"
                   onmouseover="this.style.background='var(--tf-surface-2)'; this.style.color='var(--tf-text)';"
                   onmouseout="this.style.background=''; this.style.color='var(--tf-text-muted)';">
                    <span><i class="bi bi-chevron-right" style="font-size: 0.7rem; margin-right: 0.35rem; opacity: 0.5;"></i><?php echo htmlspecialchars($catName); ?></span>
                    <span class="badge"><?php echo $cat['count']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="widget-card">
    <div class="card-accent" style="background: linear-gradient(90deg, var(--tf-accent-2), var(--tf-accent));"></div>
    <div class="p-3">
        <div class="widget-title"><i class="bi bi-fire" style="color: var(--tf-accent-2);"></i> Recent Posts</div>
        <?php if (empty($popular)): ?>
            <p class="text-muted text-small">No posts yet.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($popular as $pop): ?>
                <div style="padding-left: 0.75rem; border-left: 2px solid var(--tf-border);">
                    <a href="blog-<?php echo $pop['slug']; ?>.html"
                       style="font-size: 0.875rem; font-weight: 600; color: var(--tf-text); text-decoration: none; line-height: 1.4; display: block; margin-bottom: 0.2rem;">
                        <?php echo htmlspecialchars($pop['title']); ?>
                    </a>
                    <div style="font-size: 0.72rem; color: var(--tf-text-muted);">
                        <i class="bi bi-clock" style="margin-right: 0.25rem;"></i>
                        <?php echo date('M j, Y', strtotime($pop['created_at'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$blog_sidebar = ob_get_clean();
set_page_sidebar($blog_sidebar);

// 3. Pagination
$per_page     = 10;
$current_pg   = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($current_pg - 1) * $per_page;
$total_posts  = $db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$total_pages  = ceil($total_posts / $per_page);

$stmt = $db->prepare("SELECT * FROM posts WHERE status='published' ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$per_page, $offset]);
$posts = $stmt->fetchAll();
?>

<div class="section-label mb-4">
    <i class="bi bi-journal-text"></i>
    All Posts
    <?php if ($total_posts > 0): ?>
        <span class="badge" style="margin-left: auto; text-transform: none; letter-spacing: normal;"><?php echo $total_posts; ?> articles</span>
    <?php endif; ?>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state">
        <span class="empty-icon"><i class="bi bi-pencil-square"></i></span>
        <h5>Nothing here yet</h5>
        <p class="text-small">Posts will appear here once published.</p>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-card-accent"></div>
                <div class="post-card-body">
                    <div class="post-meta">
                        <span><i class="bi bi-calendar3" style="margin-right: 0.3rem;"></i><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                        <?php if (!empty($post['category'])): ?>
                            <span><i class="bi bi-tag" style="margin-right: 0.3rem;"></i><?php echo htmlspecialchars($post['category']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="post-title">
                        <a href="blog-<?php echo $post['slug']; ?>.html"><?php echo htmlspecialchars($post['title']); ?></a>
                    </div>
                    <?php if (!empty($post['excerpt']) || !empty($post['content'])): ?>
                        <p class="post-excerpt">
                            <?php
                            $text = !empty($post['excerpt']) ? $post['excerpt'] : $post['content'];
                            echo substr(strip_tags($text), 0, 160) . '&hellip;';
                            ?>
                        </p>
                    <?php endif; ?>
                    <div style="margin-top: 0.85rem;">
                        <a href="blog-<?php echo $post['slug']; ?>.html" class="btn btn-ghost btn-sm">
                            Read more <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav class="pagination" aria-label="Page navigation">
            <?php if ($current_pg > 1): ?>
                <a href="?page=<?php echo $current_pg - 1; ?>"><i class="bi bi-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $current_pg): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($current_pg < $total_pages): ?>
                <a href="?page=<?php echo $current_pg + 1; ?>"><i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
