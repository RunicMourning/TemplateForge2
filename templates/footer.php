        </main>
    </div>
</div>

<?php if (function_exists('run_hook')) run_hook('content_after'); ?>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="footer-brand" href="index.php">
                    <i class="bi bi-intersect"></i>
                    <?php echo htmlspecialchars($settings['site_name']); ?>
                </a>
                <p class="text-small" style="color: rgba(255,255,255,0.45); max-width: 280px; line-height: 1.6; margin-bottom: 0;">
                    <?php echo htmlspecialchars($settings['footer_text'] ?? 'Delivering strategic insights and industry updates.'); ?>
                </p>
                <div class="footer-social">
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" aria-label="Twitter/X"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" aria-label="Email"><i class="bi bi-envelope-fill"></i></a>
                </div>
            </div>

            <div>
                <div class="footer-col-title">Platform</div>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="blog.php">Latest Posts</a></li>
                    <li><a href="about.html">About Us</a></li>
                </ul>
            </div>

            <div>
                <div class="footer-col-title">Management</div>
                <ul class="footer-links">
                    <li><a href="admin/index.php"><i class="bi bi-lock-fill"></i> Admin Portal</a></li>
                    <li><a href="contact.html">Support</a></li>
                </ul>
            </div>

            <div style="text-align: right;">
                <div class="footer-col-title">System Status</div>
                <div class="status-pill">
                    <span class="status-dot"></span>
                    Operational &mdash; v2.5.0
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved.</span>
            <span>Designed with TemplatForge.</span>
        </div>
    </div>
</footer>

<?php if (function_exists('run_hook')) run_hook('footer_bottom'); ?>

</body>
</html>
