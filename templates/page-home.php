<?php
/**
 * Template Name: Homepage
 * Description: Editorial homepage with sidebar, hero, feature cards, and latest posts.
 */

// 1. Capture Sidebar Content
ob_start(); ?>

<div class="widget-card">
    <div class="card-accent"></div>
    <div class="p-3">
        <div class="widget-title"><i class="bi bi-cpu-fill"></i> Quick Info</div>
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                <div style="width: 38px; height: 38px; background: var(--tf-surface-2); border: 1px solid var(--tf-border); border-radius: var(--tf-radius); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--tf-accent); font-size: 1.1rem;">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <div>
                    <div class="fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.2rem;">Lorem Ipsum</div>
                    <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Dolor sit amet, consectetur adipiscing elit.</div>
                </div>
            </div>
            <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                <div style="width: 38px; height: 38px; background: var(--tf-surface-2); border: 1px solid var(--tf-border); border-radius: var(--tf-radius); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--tf-accent); font-size: 1.1rem;">
                    <i class="bi bi-plugin"></i>
                </div>
                <div>
                    <div class="fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.2rem;">Adipiscing Elit</div>
                    <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Sed do eiusmod tempor incididunt ut labore.</div>
                </div>
            </div>
            <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                <div style="width: 38px; height: 38px; background: var(--tf-surface-2); border: 1px solid var(--tf-border); border-radius: var(--tf-radius); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--tf-accent); font-size: 1.1rem;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.2rem;">Tempor Incididunt</div>
                    <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Ut enim ad minim veniam, quis nostrud.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$homepage_sidebar = ob_get_clean();
set_page_sidebar($homepage_sidebar);
?>

<!-- Hero -->
<div class="hero">
    <div>
        <div class="hero-eyebrow">
            <i class="bi bi-terminal-fill"></i>
            Welcome
        </div>
        <h1 class="hero-title">
            <?php echo htmlspecialchars($settings['site_name']); ?>
            <em>Placeholder</em>
        </h1>
        <p class="hero-lead">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit.
            Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.
        </p>
        <div class="hero-actions">
            <a href="blog.php" class="btn btn-primary"><i class="bi bi-grid-3x3-gap"></i> View Content</a>
            <a href="contact.html" class="btn btn-secondary">Learn More</a>
        </div>
    </div>
</div>

<!-- Feature Cards -->
<div class="grid grid-3 mb-5">
    <div class="feature-card">
        <div class="card-accent"></div>
        <div class="feature-card-icon"><i class="bi bi-speedometer2"></i></div>
        <h5 class="fw-bold mb-1">Lorem Heading</h5>
        <p class="text-muted text-small mb-0">Aenean quam. In scelerisque sem at dolor.</p>
    </div>
    <div class="feature-card">
        <div class="card-accent"></div>
        <div class="feature-card-icon"><i class="bi bi-diagram-3"></i></div>
        <h5 class="fw-bold mb-1">Ipsum Section</h5>
        <p class="text-muted text-small mb-0">Maecenas mattis. Sed convallis tristique sem.</p>
    </div>
    <div class="feature-card">
        <div class="card-accent"></div>
        <div class="feature-card-icon"><i class="bi bi-shield-lock"></i></div>
        <h5 class="fw-bold mb-1">Dolor Amet</h5>
        <p class="text-muted text-small mb-0">Proin ut ligula vel nunc egestas porttitor.</p>
    </div>
</div>

<!-- Latest Posts -->
<div class="section-label">
    <i class="bi bi-journal-text"></i>
    Latest Items
    <a href="blog.php" class="text-small fw-semibold" style="color: var(--tf-accent); margin-left: auto; text-decoration: none; letter-spacing: normal; text-transform: none;">
        View All <i class="bi bi-arrow-right"></i>
    </a>
</div>

<div style="display: flex; flex-direction: column; gap: 1rem;">
    <?php
    $latest_items = $db->query(
        "SELECT * FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

    if (empty($latest_items)): ?>
        <div class="empty-state">
            <span class="empty-icon"><i class="bi bi-database-exclamation"></i></span>
            <h5>No Content Yet</h5>
            <p class="text-small">Placeholder entries will appear here when available.</p>
        </div>
    <?php else:
        foreach ($latest_items as $item): ?>
            <div class="post-card">
                <div class="post-card-accent"></div>
                <div class="post-card-body">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div style="width: 56px; flex-shrink: 0;">
                            <div class="fw-bold" style="font-size: 0.9rem;"><?php echo date('M d', strtotime($item['created_at'])); ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('Y', strtotime($item['created_at'])); ?></div>
                        </div>
                        <div class="flex-1" style="min-width: 0;">
                            <div class="post-title" style="font-size: 1rem;">
                                <a href="blog-<?php echo $item['slug']; ?>.html"><?php echo htmlspecialchars($item['title']); ?></a>
                            </div>
                            <div class="post-excerpt" style="font-size: 0.85rem;">
                                <?php
                                $text = !empty($item['excerpt']) ? $item['excerpt'] : $item['content'];
                                echo substr(strip_tags($text), 0, 140) . '&hellip;';
                                ?>
                            </div>
                        </div>
                        <div>
                            <span class="badge">
                                <i class="bi bi-tag" style="margin-right: 0.3rem;"></i>
                                <?php echo !empty($item['category']) ? htmlspecialchars($item['category']) : 'General'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach;
    endif; ?>
</div>
