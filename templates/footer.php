        </main>
    </div>
</div>

<?php if (function_exists('run_hook')) run_hook('content_after'); ?>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand + social links -->
            <div>
                <a class="footer-brand" href="index.php">
                    <i class="bi bi-intersect"></i>
                    <?php echo htmlspecialchars($settings['site_name']); ?>
                </a>
                <p class="text-small" style="color: rgba(255,255,255,0.45); max-width: 280px; line-height: 1.6; margin-bottom: 0;">
                    <?php echo $settings['footer_text'] ?? 'Delivering strategic insights and industry updates.'; ?>
                </p>
                <?php echo render_social_links($db); ?>
            </div>

            <!-- Dynamic footer links -->
            <?php
            $footer_links_html = render_footer_links($db);
            if (!empty($footer_links_html)):
            ?>
            <div>
                <div class="footer-col-title">Links</div>
                <?php echo $footer_links_html; ?>
            </div>
            <?php endif; ?>

            <!-- Admin portal -->
            <div>
                <div class="footer-col-title">Management</div>
                <ul class="footer-links">
                    <li><a href="admin/index.php"><i class="bi bi-lock-fill"></i> Admin Portal</a></li>
                </ul>
            </div>

            <!-- Status -->
            <div style="text-align: right;">
                <div class="footer-col-title">System Status</div>
                <div class="status-pill">
                    <span class="status-dot"></span>
                    Operational
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved.</span>
            <span>Powered by TemplatForge.</span>
        </div>
    </div>
</footer>

<?php if (function_exists('run_hook')) run_hook('footer_bottom'); ?>

</body>
</html>
