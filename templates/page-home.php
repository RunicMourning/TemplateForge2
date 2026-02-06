<?php
/**
 * Template Name: Homepage
 * Description: Sample homepage layout with placeholder content.
 */

// 1. Capture Sidebar Content
ob_start(); ?>
<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4" style="background: #ffffff;">
    <div style="height: 4px; background: linear-gradient(90deg, #6610f2, #0d6efd);"></div>
    <div class="card-header bg-transparent border-0 pt-4 pb-0">
        <h6 class="text-uppercase fw-bold text-muted tracking-widest mb-1" style="font-size: 0.7rem;">
            Sidebar Section
        </h6>
        <h5 class="fw-bold d-flex align-items-center text-dark">
            <i class="bi bi-cpu-fill me-2 text-primary"></i>
            Placeholder Title
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex mb-4">
            <div class="text-primary me-3 bg-light rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 45px; height: 45px; flex-shrink: 0;">
                <i class="bi bi-lightning-charge-fill fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">Lorem Ipsum</h6>
                <p class="small text-muted mb-0">
                    Dolor sit amet, consectetur adipiscing elit.
                </p>
            </div>
        </div>

        <div class="d-flex mb-4">
            <div class="text-primary me-3 bg-light rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 45px; height: 45px; flex-shrink: 0;">
                <i class="bi bi-plugin fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">Adipiscing Elit</h6>
                <p class="small text-muted mb-0">
                    Sed do eiusmod tempor incididunt ut labore.
                </p>
            </div>
        </div>

        <div class="d-flex mb-0">
            <div class="text-primary me-3 bg-light rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 45px; height: 45px; flex-shrink: 0;">
                <i class="bi bi-shield-check fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">Tempor Incididunt</h6>
                <p class="small text-muted mb-0">
                    Ut enim ad minim veniam, quis nostrud exercitation.
                </p>
            </div>
        </div>
    </div>
</div>
<?php
$homepage_sidebar = ob_get_clean();
set_page_sidebar($homepage_sidebar);
?>

<div class="homepage-container">
    <header class="row align-items-center mb-5 pb-5 border-bottom">
        <div class="col-lg-7">
            <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-3 small fw-bold">
                <i class="bi bi-terminal-fill me-2"></i>
                Sample Label
            </div>
            <h1 class="display-5 fw-bold text-dark tracking-tight mb-3">
                <?php echo htmlspecialchars($settings['site_name']); ?>
                <span class="text-primary">Placeholder</span>
            </h1>
            <p class="lead text-secondary mb-4">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.
            </p>
            <div class="d-flex gap-2">
                <a href="blog.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
                    <i class="bi bi-grid-3x3-gap me-2"></i>
                    View Content
                </a>
                <a href="contact.html" class="btn btn-outline-dark rounded-pill px-4">
                    Learn More
                </a>
            </div>
        </div>

        <div class="col-lg-5 d-none d-lg-block">
            <div class="card bg-light border-1 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-light border-bottom border-secondary border-opacity-25 py-2">
                    <div class="d-flex gap-1">
                        <div class="rounded-circle bg-danger" style="width:10px; height:10px;"></div>
                        <div class="rounded-circle bg-warning" style="width:10px; height:10px;"></div>
                        <div class="rounded-circle bg-success" style="width:10px; height:10px;"></div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <code class="small text-success-emphasis">
                        <span class="text-primary">function</span>
                        <span class="text-warning">example</span>() {<br>
                        &nbsp;&nbsp;<span class="text-primary">return</span>
                        <span class="text-success">'lorem ipsum'</span>;<br>
                        }
                    </code>
                </div>
            </div>
        </div>
    </header>

    <section class="row g-4 mb-5 pb-5">
<div class="col-md-4">
    <div class="bg-white rounded-4 border border-light-subtle shadow-sm h-100 overflow-hidden">
        <div style="height: 4px; background: linear-gradient(90deg, #6610f2, #0d6efd);"></div>
        <div class="p-4">
            <i class="bi bi-speedometer2 text-primary fs-2 mb-3 d-block"></i>
            <h6 class="fw-bold text-dark">Lorem Heading</h6>
            <p class="small text-muted mb-0">
                Aenean quam. In scelerisque sem at dolor.
            </p>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="bg-white rounded-4 border border-light-subtle shadow-sm h-100 overflow-hidden">
        <div style="height: 4px; background: linear-gradient(90deg, #6610f2, #0d6efd);"></div>
        <div class="p-4">
            <i class="bi bi-diagram-3 text-primary fs-2 mb-3 d-block"></i>
            <h6 class="fw-bold text-dark">Ipsum Section</h6>
            <p class="small text-muted mb-0">
                Maecenas mattis. Sed convallis tristique sem.
            </p>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="bg-white rounded-4 border border-light-subtle shadow-sm h-100 overflow-hidden">
        <div style="height: 4px; background: linear-gradient(90deg, #6610f2, #0d6efd);"></div>
        <div class="p-4">
            <i class="bi bi-shield-lock text-primary fs-2 mb-3 d-block"></i>
            <h6 class="fw-bold text-dark">Dolor Amet</h6>
            <p class="small text-muted mb-0">
                Proin ut ligula vel nunc egestas porttitor.
            </p>
        </div>
    </div>
</div>
    </section>

<div class="latest-news-section">
    <!-- Section Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold m-0 text-uppercase small tracking-widest text-muted d-flex align-items-center">
            <i class="bi bi-journal-text me-2 text-primary fs-5"></i>
            Latest Items
        </h3>
        <a href="blog.php" class="text-decoration-none small fw-bold text-primary d-flex align-items-center">
            View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <!-- List of Posts -->
    <div class="row g-4">
        <?php
        $latest_items = $db->query(
            "SELECT * FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5"
        )->fetchAll();

        if (empty($latest_items)): ?>
            <div class="col-12 py-5 text-center text-muted">
                <div class="bg-light d-inline-block p-4 rounded-circle mb-3">
                    <i class="bi bi-database-exclamation fs-1"></i>
                </div>
                <h5>No Content Yet</h5>
                <p class="small mb-0">
                    Placeholder entries will appear here when available.
                </p>
            </div>
        <?php else:
            foreach ($latest_items as $item): ?>
                <div class="col-12">
                    <div class="card shadow-sm rounded-4 border-0 position-relative overflow-hidden news-list-card">
                        <!-- Gradient Accent Bar -->
                        <div class="position-absolute top-0 start-0 h-100" style="width: 4px; background: linear-gradient(to bottom, var(--bs-primary), #6610f2);"></div>

                        <div class="card-body ps-5">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start">
                                <div class="mb-2 mb-md-0 text-muted small" style="width: 80px;">
                                    <div class="fw-bold text-dark"><?php echo date('M d', strtotime($item['created_at'])); ?></div>
                                    <div class="opacity-75"><?php echo date('Y', strtotime($item['created_at'])); ?></div>
                                </div>

                                <div class="flex-fill px-md-3">
                                    <h5 class="fw-bold mb-1">
                                        <a href="blog-<?php echo $item['slug']; ?>.html" class="text-dark text-decoration-none stretched-link">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        <?php
                                        $text = !empty($item['excerpt']) ? $item['excerpt'] : $item['content'];
                                        echo substr(strip_tags($text), 0, 140) . '...';
                                        ?>
                                    </p>
                                </div>

                                <div class="text-md-end mt-2 mt-md-0">
                                    <span class="badge rounded-pill bg-white text-muted border px-3 py-2 fw-normal" style="font-size: 0.65rem;">
                                        <i class="bi bi-tag me-1"></i>
                                        <?php echo !empty($item['category']) ? htmlspecialchars($item['category']) : 'General'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php endforeach; endif; ?>
    </div>
</div></div>

<?php
queue_css("
.tracking-tight { letter-spacing: -0.025em; }
.tracking-widest { letter-spacing: 0.15em; }

.news-list-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.news-list-item:hover {
    background-color: #f8f9fa !important;
    padding-left: 20px !important;
}

.news-list-item:hover .accent-bar {
    opacity: 1 !important;
}

.news-list-item a {
    transition: color 0.2s ease;
}

.news-list-item:hover a {
    color: var(--bs-primary) !important;
}

.news-list-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.news-list-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.75rem 1.25rem rgba(0,0,0,0.1);
}
");
?>
