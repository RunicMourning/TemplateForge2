<?php
/**
 * Template Name: Single Blog Post
 */

$categories = $db->query("SELECT category, COUNT(*) as count FROM posts WHERE status = 'published' GROUP BY category ORDER BY count DESC")->fetchAll();
$popular     = $db->query("SELECT title, slug, created_at FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();

ob_start(); ?>
<div class="widget-card mb-3">
    <div class="card-accent"></div>
    <div class="p-3">
        <div class="widget-title"><i class="bi bi-collection-play-fill"></i> Categories</div>
        <div style="display: flex; flex-direction: column; gap: 0.3rem;">
            <?php foreach ($categories as $cat):
                $catName = !empty($cat['category']) ? $cat['category'] : 'General'; ?>
            <a href="category-<?php echo urlencode($catName); ?>.html"
               style="display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.55rem; border-radius: var(--tf-radius); color: var(--tf-text-muted); font-size: 0.875rem; text-decoration: none; transition: background 0.15s, color 0.15s;"
               onmouseover="this.style.background='var(--tf-surface-2)';this.style.color='var(--tf-text)';"
               onmouseout="this.style.background='';this.style.color='var(--tf-text-muted)';">
                <span><i class="bi bi-chevron-right" style="font-size: 0.7rem; margin-right: 0.3rem; opacity:0.5;"></i><?php echo htmlspecialchars($catName); ?></span>
                <span class="badge"><?php echo $cat['count']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="widget-card">
    <div class="card-accent" style="background: linear-gradient(90deg, var(--tf-accent-2), var(--tf-accent));"></div>
    <div class="p-3">
        <div class="widget-title"><i class="bi bi-fire" style="color: var(--tf-accent-2);"></i> Recent</div>
        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
            <?php foreach ($popular as $pop): ?>
            <div style="padding-left: 0.65rem; border-left: 2px solid var(--tf-border);">
                <a href="blog-<?php echo $pop['slug']; ?>.html"
                   style="font-size: 0.875rem; font-weight: 600; color: var(--tf-text); text-decoration: none; display: block; margin-bottom: 0.15rem; line-height: 1.4;">
                    <?php echo htmlspecialchars($pop['title']); ?>
                </a>
                <div style="font-size: 0.72rem; color: var(--tf-text-muted);">
                    <i class="bi bi-clock" style="margin-right: 0.25rem;"></i><?php echo date('M j, Y', strtotime($pop['created_at'])); ?>
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

<article>
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-accent"></div>
        <div class="p-4" style="padding-bottom: 0 !important;">
            <h1 style="font-family: var(--tf-font-display); font-size: clamp(1.5rem, 4vw, 2.25rem); margin-bottom: 1rem; line-height: 1.2;">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>
            <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b" style="margin-bottom: 0;">
                <div class="flex items-center gap-2">
                    <?php if (!empty($post['category'])): ?>
                        <span class="badge badge-accent"><?php echo htmlspecialchars($post['category']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="post-meta">
                    <span><i class="bi bi-calendar3" style="margin-right: 0.3rem;"></i><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                    <?php
                    $word_count = str_word_count(strip_tags($post['content']));
                    $read_time  = max(1, ceil($word_count / 200));
                    ?>
                    <span><i class="bi bi-clock" style="margin-right: 0.3rem;"></i><?php echo $read_time; ?> min read</span>
                </div>
            </div>
        </div>
        <div class="p-4" style="padding-top: 1.5rem !important;">
            <div class="post-content">
                <?php echo $post['content']; ?>
            </div>
        </div>
    </div>
</article>

<!-- Prev / Next -->
<?php
$prev = $db->prepare("SELECT title, slug FROM posts WHERE status='published' AND created_at < ? ORDER BY created_at DESC LIMIT 1");
$prev->execute([$post['created_at']]);
$prev_post = $prev->fetch();

$next = $db->prepare("SELECT title, slug FROM posts WHERE status='published' AND created_at > ? ORDER BY created_at ASC LIMIT 1");
$next->execute([$post['created_at']]);
$next_post = $next->fetch();

if ($prev_post || $next_post): ?>
<div class="flex justify-between flex-wrap gap-2 mt-4">
    <?php if ($prev_post): ?>
    <a href="blog-<?php echo $prev_post['slug']; ?>.html" class="btn btn-ghost btn-sm">
        <i class="bi bi-arrow-left"></i> <?php echo htmlspecialchars(mb_strimwidth($prev_post['title'], 0, 40, '&hellip;')); ?>
    </a>
    <?php else: ?><span></span><?php endif; ?>
    <?php if ($next_post): ?>
    <a href="blog-<?php echo $next_post['slug']; ?>.html" class="btn btn-ghost btn-sm">
        <?php echo htmlspecialchars(mb_strimwidth($next_post['title'], 0, 40, '&hellip;')); ?> <i class="bi bi-arrow-right"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>
