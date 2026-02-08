<?php
/**
 * Contact Form Addon
 * Handles injection of configuration settings into the Admin Panel.
 */

if (function_exists('register_settings_section')) {
    register_settings_section('contact_form', [
        'title' => 'Contact Form Configuration',
        'description' => 'Manage recipient email, subject list, and Turnstile credentials for the contact form addon.',
        'icon' => 'bi bi-envelope-paper-fill',
        'fields' => [
            [
                'key' => 'contact_recipient_email',
                'label' => 'Recipient Notification Email',
                'type' => 'email',
                'placeholder' => 'where@emails-go.com',
                'default' => 'admin@yoursite.com',
                'help' => 'All form submissions will be sent to this address.'
            ],
            [
                'key' => 'contact_subjects_list',
                'label' => 'Dropdown Subjects (One per line)',
                'type' => 'textarea',
                'rows' => 4,
                'default' => "General Inquiry\nSupport\nBilling",
                'help' => 'These values populate the Subject dropdown on the contact page.'
            ],
            [
                'key' => 'turnstile_site_key',
                'label' => 'Turnstile Site Key',
                'type' => 'text',
                'placeholder' => '0x4AAAAAA...'
            ],
            [
                'key' => 'turnstile_secret_key',
                'label' => 'Turnstile Secret Key',
                'type' => 'password',
                'placeholder' => 'Keep this secret'
            ],
        ]
    ]);
} else {
    // Legacy fallback for older hook-only installations.
    add_hook('admin_settings_ui', function() {
        global $res;
        if (!is_array($res)) {
            $res = [];
        }

        $site_key    = htmlspecialchars($res['turnstile_site_key'] ?? '');
        $secret_key  = htmlspecialchars($res['turnstile_secret_key'] ?? '');
        $notif_email = htmlspecialchars($res['contact_recipient_email'] ?? 'admin@yoursite.com');
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
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Dropdown Subjects (One per line)</label>
                    <textarea name="config[contact_subjects_list]" class="form-control" rows="4" placeholder="Enter subjects here...">' . $subjects_raw . '</textarea>
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
            </div>
        </div>';
    });
}
