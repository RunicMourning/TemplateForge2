<?php
/**
 * Template Name: Privacy Policy
 */

ob_start(); ?>
<div class="widget-card">
    <div class="card-accent"></div>
    <div class="p-3">
        <div class="widget-title"><i class="bi bi-shield-lock-fill"></i> Legal Sections</div>
        <nav id="privacy-nav" style="display: flex; flex-direction: column; gap: 0.1rem;">
            <a href="#overview"    class="link-muted text-small" style="padding: 0.3rem 0.5rem 0.3rem 0.85rem; border-left: 2px solid var(--tf-border); border-radius: 0;">1. Overview</a>
            <a href="#collection"  class="link-muted text-small" style="padding: 0.3rem 0.5rem 0.3rem 0.85rem; border-left: 2px solid var(--tf-border); border-radius: 0;">2. Data Collection</a>
            <a href="#contact-data"class="link-muted text-small" style="padding: 0.3rem 0.5rem 0.3rem 0.85rem; border-left: 2px solid var(--tf-border); border-radius: 0;">3. Contact Inquiries</a>
            <a href="#third-party" class="link-muted text-small" style="padding: 0.3rem 0.5rem 0.3rem 0.85rem; border-left: 2px solid var(--tf-border); border-radius: 0;">4. Third-Party Services</a>
            <a href="#security"    class="link-muted text-small" style="padding: 0.3rem 0.5rem 0.3rem 0.85rem; border-left: 2px solid var(--tf-border); border-radius: 0;">5. Data Security</a>
            <a href="#updates"     class="link-muted text-small" style="padding: 0.3rem 0.5rem 0.3rem 0.85rem; border-left: 2px solid var(--tf-border); border-radius: 0;">6. Policy Updates</a>
        </nav>
    </div>
</div>
<?php
$privacy_sidebar = ob_get_clean();
set_page_sidebar($privacy_sidebar);
?>

<style>
section { scroll-margin-top: 5.5rem; }
#privacy-nav a:hover { color: var(--tf-accent) !important; border-left-color: var(--tf-accent); }
</style>

<header class="hero" style="padding-top: 0;">
    <div class="flex items-center gap-2 mb-3">
        <div style="width: 44px; height: 44px; background: var(--tf-surface-2); border: 1px solid var(--tf-border); border-radius: var(--tf-radius); display: flex; align-items: center; justify-content: center; color: var(--tf-accent); font-size: 1.3rem; flex-shrink: 0;">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div>
            <h1 class="fw-bold" style="font-size: 1.75rem; margin-bottom: 0.1rem;">Privacy Policy</h1>
            <p class="text-muted text-small mb-0">Compliance Documentation &bull; Last Updated: <?php echo date("F j, Y", filemtime(__FILE__)); ?></p>
        </div>
    </div>
</header>

<article class="post-content">

    <section id="overview" class="mb-5">
        <h2>1. Overview</h2>
        <p>This website is engineered to operate under a <strong>Privacy-First</strong> architecture. We do not collect, store, or process personal information from visitors.</p>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Visitors may browse the site anonymously without providing any personally identifiable information (PII).
        </div>
    </section>

    <section id="collection" class="mb-5">
        <h2>2. Information We Do Not Collect</h2>
        <p>Our infrastructure specifically excludes the following data points:</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.5rem;">
            <div><i class="bi bi-x-circle" style="color: var(--tf-danger, #e53935); margin-right: 0.4rem;"></i> Names or Email Addresses</div>
            <div><i class="bi bi-x-circle" style="color: var(--tf-danger, #e53935); margin-right: 0.4rem;"></i> Cookies or Local Storage</div>
            <div><i class="bi bi-x-circle" style="color: var(--tf-danger, #e53935); margin-right: 0.4rem;"></i> IP Addresses or Location Data</div>
            <div><i class="bi bi-x-circle" style="color: var(--tf-danger, #e53935); margin-right: 0.4rem;"></i> Behavioral Tracking</div>
        </div>
    </section>

    <section id="contact-data" class="mb-5">
        <h2>3. Contact Inquiries</h2>
        <p>If you use our <strong>Contact Form</strong>, you will be asked to provide your name and email so we may fulfill your request.</p>
        <ul>
            <li><strong>Official Correspondence:</strong> We only use your contact details to respond to your specific inquiry.</li>
            <li><strong>Data Retention:</strong> Correspondence is kept only as long as necessary.</li>
        </ul>
    </section>

    <section id="third-party" class="mb-5">
        <h2>4. Third-Party &amp; Technical Disclosures</h2>
        <p>While our core platform does not collect data, certain optional third-party tools or integrations may be present.</p>
        <?php
        if (function_exists('run_hook')) {
            ob_start();
            run_hook('privacy_policy_disclosures');
            $hook_content = ob_get_clean();
            if (!empty(trim($hook_content))) {
                echo '<div class="card p-3 mt-2">' . $hook_content . '</div>';
            } else {
                echo '<div class="alert alert-info"><i class="bi bi-check-all"></i> No additional third-party disclosures required.</div>';
            }
        }
        ?>
    </section>

    <section id="security" class="mb-5">
        <h2>5. Data Security</h2>
        <p>Standard encryption (SSL) is used to protect any data transmitted via our contact form.</p>
    </section>

    <section id="updates" class="mb-5">
        <h2>6. Policy Updates</h2>
        <p>This policy is reviewed periodically to reflect changes in site functionality.</p>
    </section>

    <div class="card p-3 mt-4" style="text-align: center; border-left: 4px solid var(--tf-accent);">
        <h6 class="fw-bold mb-2">Contact Administration</h6>
        <a href="contact.html" class="btn btn-secondary btn-sm">Contact Form</a>
    </div>

</article>
