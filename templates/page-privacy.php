<?php
/**
 * Template Name: Privacy Policy
 * Description: Decoupled sidebar navigation for global injection.
 */

// 1. Capture the navigation for the global sidebar "bucket"
ob_start(); ?>
    <div class="sticky-top" style="top: 100px;">
        <h6 class="text-uppercase small fw-bold mb-3 tracking-widest text-muted">Legal Sections</h6>
        <nav id="privacy-nav" class="nav flex-column small border-start">
            <a class="nav-link text-secondary py-1" href="#overview">1. Overview</a>
            <a class="nav-link text-secondary py-1" href="#collection">2. Data Collection</a>
            <a class="nav-link text-secondary py-1" href="#contact-data">3. Contact Inquiries</a>
            <a class="nav-link text-secondary py-1" href="#third-party">4. Third-Party Services</a>
            <a class="nav-link text-secondary py-1" href="#security">5. Data Security</a>
            <a class="nav-link text-secondary py-1" href="#updates">6. Policy Updates</a>
        </nav>
    </div>
<?php 
$privacy_sidebar = ob_get_clean();
// Register this HTML to the global sidebar variable we created earlier
set_page_sidebar($privacy_sidebar); 
?>

<div class="privacy-container py-5">
    <header class="mb-5 pb-4 border-bottom">
        <div class="d-flex align-items-center mb-3">
            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                <i class="bi bi-shield-lock fs-3"></i>
            </div>
            <div>
                <h1 class="fw-bold h2 mb-0">Privacy Policy</h1>
                <p class="text-muted small mb-0">
                    Compliance Documentation &bull; 
                    Last Updated: <?php echo date("F j, Y", filemtime(__FILE__)); ?>
                </p>
            </div>
        </div>
    </header>

    <article class="text-secondary" style="line-height: 1.8;">
        
        <section id="overview" class="mb-5">
            <h2 class="h5 fw-bold text-dark mb-3">1. Overview</h2>
            <p>
                This website is engineered to operate under a <strong>Privacy-First</strong> architecture. We do not collect, store, or process personal information from visitors.
            </p>
            <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                <p class="mb-0 small fw-medium">
                    <i class="bi bi-info-circle me-2"></i> 
                    Visitors may browse the site anonymously without providing any personally identifiable information (PII).
                </p>
            </div>
        </section>

        <section id="collection" class="mb-5">
            <h2 class="h5 fw-bold text-dark mb-3">2. Information We Do Not Collect</h2>
            <p>Our infrastructure specifically excludes the following data points from our environment:</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-x-circle text-danger me-2"></i> Names or Email Addresses</li>
                        <li class="mb-2"><i class="bi bi-x-circle text-danger me-2"></i> IP Addresses or Location Data</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-x-circle text-danger me-2"></i> Cookies or Local Storage</li>
                        <li class="mb-2"><i class="bi bi-x-circle text-danger me-2"></i> Behavioral Tracking</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="contact-data" class="mb-5">
            <h2 class="h5 fw-bold text-dark mb-3">3. Contact Inquiries</h2>
            <p>
                If you choose to use our <strong>Contact Form</strong>, you will be asked to provide your name and email address so that we may fulfill your request. 
            </p>
            <div class="bg-light p-3 rounded mb-3 border">
                <ul class="mb-0 small">
                    <li><strong>Official Correspondence:</strong> We only use your contact details to respond to your specific inquiry.</li>
                    <li><strong>Data Retention:</strong> Correspondence is kept only as long as necessary.</li>
                </ul>
            </div>
        </section>

        <section id="third-party" class="mb-5">
            <h2 class="h5 fw-bold text-dark mb-3">4. Third-Party & Technical Disclosures</h2>
            <p>
                While our core platform does not collect data, certain optional third-party tools or integrations may be present.
            </p>
            
            <?php 
                if (function_exists('run_hook')) {
                    ob_start();
                    run_hook('privacy_policy_disclosures'); 
                    $hook_content = ob_get_clean();

                    if (!empty(trim($hook_content))) {
                        echo '<div class="p-4 bg-white border rounded shadow-sm mb-4">' . $hook_content . '</div>';
                    } else {
                        echo '<div class="alert alert-light border small text-muted"><i class="bi bi-check-all me-2"></i>No additional third-party disclosures required.</div>';
                    }
                }
            ?>
        </section>

        <section id="security" class="mb-5">
            <h2 class="h5 fw-bold text-dark mb-3">5. Data Security</h2>
            <p>Standard encryption (SSL) is used to protect any data transmitted via our contact form.</p>
        </section>

        <section id="updates" class="mb-5">
            <h2 class="h5 fw-bold text-dark mb-3">6. Policy Updates</h2>
            <p>This policy is reviewed periodically to reflect changes in site functionality.</p>
        </section>

        <footer class="bg-light p-4 rounded-3 text-center">
            <h6 class="fw-bold text-dark">Contact Administration</h6>
            <a href="contact.html" class="btn btn-outline-dark btn-sm px-4 rounded-pill">Contact Form</a>
        </footer>

    </article>
</div>

<?php
queue_css("
    section { scroll-margin-top: 120px; }
    #privacy-nav .nav-link {
        font-size: 0.85rem;
        border-left: 2px solid transparent;
        margin-left: -1px;
    }
    #privacy-nav .nav-link:hover {
        color: var(--bs-primary) !important;
        border-left-color: var(--bs-primary);
    }
");
?>