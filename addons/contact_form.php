<?php
/**
 * Contact Form Addon
 * Handles injection of configuration settings into the Admin Panel.
 */

add_hook('admin_settings_ui', function() {
    // Access the global settings array fetched in settings.php
    global $res;
    
    // Ensure $res is an array to prevent errors if DB is empty
    if (!is_array($res)) {
        $res = []; 
    }

    // Extract values or use defaults
    $site_key    = htmlspecialchars($res['turnstile_site_key'] ?? '');
    $secret_key  = htmlspecialchars($res['turnstile_secret_key'] ?? '');
    $notif_email = htmlspecialchars($res['contact_recipient_email'] ?? 'admin@yoursite.com');
    
    // For the textarea, we want to keep the raw newlines but escape for HTML safety
    $subjects_raw = htmlspecialchars($res['contact_subjects_list'] ?? "General Inquiry\nSupport\nBilling");

    echo '
    <div class="card shadow-sm border-0 mb-4 border-start border-primary border-5">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 text-primary fw-bold">
                <i class="bi bi-envelope-paper-fill me-2"></i>Contact Form Configuration
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="mb-3">
                <label class="form-label fw-bold">Recipient Notification Email</label>
                <input type="email" name="config[contact_recipient_email]" value="' . $notif_email . '" class="form-control" placeholder="where@emails-go.com">
                <div class="form-text">All form submissions will be sent to this address.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Dropdown Subjects (One per line)</label>
                <textarea name="config[contact_subjects_list]" class="form-control" rows="4" placeholder="Enter subjects here...">' . $subjects_raw . '</textarea>
                <div class="form-text">These will automatically populate the "Subject" dropdown on your contact page.</div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Turnstile Site Key</label>
                    <input type="text" name="config[turnstile_site_key]" value="' . $site_key . '" class="form-control" placeholder="0x4AAAAAA...">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Turnstile Secret Key</label>
                    <input type="password" name="config[turnstile_secret_key]" value="' . $secret_key . '" class="form-control" placeholder="Keep this secret">
                </div>
            </div>
            <div class="alert alert-light border-0 small mb-0">
                <i class="bi bi-info-circle me-1"></i> 
                Bot protection powered by Cloudflare Turnstile. 
                <a href="https://dash.cloudflare.com/" target="_blank" class="text-decoration-none">Get your keys here.</a>
            </div>
        </div>
    </div>';
});