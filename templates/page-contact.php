<?php
require_once 'functions.php';
$settings = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$raw_subjects  = $settings['contact_subjects_list'] ?? "General Inquiry";
$subject_array = explode("\n", str_replace("\r", "", $raw_subjects));

$status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'contact_form')) {
        $status = "<div class='alert alert-error'><i class='bi bi-shield-x'></i> Security verification failed. Please refresh and try again.</div>";
    } elseif (validate_contact_form($_POST, $settings['turnstile_secret_key'])) {
        $stmt = $db->prepare("INSERT INTO contact_messages (sender_name, sender_email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['subject'], $_POST['message']]);

        $to = $settings['contact_recipient_email'];
        mail($to, "New Web Contact: " . $_POST['subject'],
             "From: {$_POST['name']} ({$_POST['email']})\n\n{$_POST['message']}");

        $status = "<div class='alert alert-success'><i class='bi bi-check-all'></i> Message sent successfully!</div>";
    } else {
        $status = "<div class='alert alert-error'><i class='bi bi-shield-x'></i> Security verification failed. Please try again.</div>";
    }
}
?>

<?php echo $status; ?>

<div class="grid" style="grid-template-columns: 1fr; gap: 2.5rem;">
    <div style="grid-column: 1;">
        <h2 class="fw-bold mb-4" style="font-family: var(--tf-font-display);">Send us a Message</h2>

        <div class="card">
            <div class="card-accent"></div>
            <div class="p-4">
                <form method="POST">
                    <?php echo csrf_input('contact_form'); ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;" class="mb-3">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Full Name</label>
                            <input type="text" name="name" placeholder="John Doe" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="john@doe.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject" required>
                            <option value="" disabled selected>How can we help?</option>
                            <?php foreach ($subject_array as $sub):
                                $sub = trim($sub); if ($sub === '') continue; ?>
                                <option value="<?php echo htmlspecialchars($sub); ?>"><?php echo htmlspecialchars($sub); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Your Message</label>
                        <textarea name="message" rows="6" placeholder="Describe your inquiry in detail&hellip;" required></textarea>
                    </div>

                    <?php if (!empty($settings['turnstile_site_key'])): ?>
                    <div class="form-group">
                        <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($settings['turnstile_site_key']); ?>"></div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary w-full">
                        <i class="bi bi-send-fill"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <h4 class="fw-bold mb-2" style="font-family: var(--tf-font-display);">Contact Information</h4>
        <p class="text-muted text-small mb-4">Prefer a different way to reach out? Find us on social or via the details below.</p>

        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
            <div class="flex items-center gap-2">
                <div style="width: 36px; height: 36px; background: var(--tf-surface-2); border: 1px solid var(--tf-border); border-radius: var(--tf-radius); display: flex; align-items: center; justify-content: center; color: var(--tf-accent); flex-shrink: 0;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <div class="fw-semibold text-small">Location</div>
                    <div class="text-muted" style="font-size: 0.8rem;">San Francisco, CA 94103</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div style="width: 36px; height: 36px; background: var(--tf-surface-2); border: 1px solid var(--tf-border); border-radius: var(--tf-radius); display: flex; align-items: center; justify-content: center; color: var(--tf-accent); flex-shrink: 0;">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div>
                    <div class="fw-semibold text-small">Business Hours</div>
                    <div class="text-muted" style="font-size: 0.8rem;">Mon &mdash; Fri: 9:00 AM &ndash; 5:00 PM</div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-2" style="font-family: var(--tf-font-display);">Follow Us</h5>
        <div class="flex gap-1 mb-4">
            <a href="#" class="btn btn-ghost btn-sm" aria-label="Twitter/X"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="btn btn-ghost btn-sm" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" class="btn btn-ghost btn-sm" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
            <a href="#" class="btn btn-ghost btn-sm" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        </div>

        <div class="card p-3" style="border-left: 4px solid var(--tf-accent);">
            <h6 class="fw-bold mb-1 text-small"><i class="bi bi-shield-lock" style="margin-right: 0.35rem;"></i>Privacy Notice</h6>
            <p class="text-muted" style="font-size: 0.8rem; margin-bottom: 0; line-height: 1.5;">
                By submitting this form, you agree to our <strong>Terms of Service</strong>. Your name and email will only be used to process this inquiry per our <strong>Privacy Policy</strong>.
            </p>
        </div>
    </div>
</div>
