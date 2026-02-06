</main>
    </div>
</div>

<?php if (function_exists('run_hook')) run_hook('content_after'); ?>

<footer class="bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="fw-bold mb-3 d-flex align-items-center">
                    <i class="bi bi-intersect text-primary me-2"></i>
                    <?php echo htmlspecialchars($settings['site_name']); ?>
                </h5>
                <p class="small text-secondary mb-4" style="max-width: 300px;">
                    Delivering strategic insights and industry-leading updates to our global community. 
                </p>
                <div class="d-flex gap-3 fs-5">
                    <a href="#" class="text-secondary hover-white"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="text-secondary hover-white"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-secondary hover-white"><i class="bi bi-envelope-fill"></i></a>
                </div>
            </div>
            
            <div class="col-6 col-lg-2">
                <h6 class="text-uppercase small fw-bold mb-3 tracking-widest text-white">Platform</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="index.php" class="text-secondary text-decoration-none hover-white">Dashboard</a></li>
                    <li class="mb-2"><a href="blog.php" class="text-secondary text-decoration-none hover-white">Latest News</a></li>
                    <li class="mb-2"><a href="about.html" class="text-secondary text-decoration-none hover-white">About Us</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h6 class="text-uppercase small fw-bold mb-3 tracking-widest text-white">Management</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <a href="admin/index.php" class="text-secondary text-decoration-none hover-white">
                            <i class="bi bi-lock-fill me-1"></i> Admin Portal
                        </a>
                    </li>
                    <li class="mb-2"><a href="contact.html" class="text-secondary text-decoration-none hover-white">Support</a></li>
                </ul>
            </div>

            <div class="col-lg-4 text-lg-end">
                <h6 class="text-uppercase small fw-bold mb-3 tracking-widest text-white">System Status</h6>
                <div class="d-inline-flex align-items-center small text-secondary bg-black bg-opacity-25 px-3 py-2 rounded-pill border border-secondary border-opacity-25">
                    <span class="p-1 bg-success border border-light rounded-circle me-2 animate-pulse"></span>
                    Operational: v2.4.0
                </div>
            </div>
        </div>
        
        <hr class="border-secondary opacity-10 my-4">
        
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="small text-secondary mb-0">
                    &copy; <?php echo date('Y'); ?> **<?php echo htmlspecialchars($settings['site_name']); ?>**. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="small text-secondary mb-0">
                    Designed for Excellence.
                </p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if (function_exists('run_hook')) run_hook('footer_bottom'); ?>

</body>
</html>

<?php

queue_css("
    /* Corporate Footer Specifics */
    .hover-white:hover { color: #fff !important; transition: color 0.2s ease; }
    footer .tracking-widest { letter-spacing: 0.15em; font-size: 0.7rem; }
    
    /* Subtle pulse for the status indicator */
    @keyframes pulse-green {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    .animate-pulse {
        animation: pulse-green 2s infinite ease-in-out;
    }
");
?>