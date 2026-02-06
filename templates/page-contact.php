<?php
require_once 'functions.php';
$settings = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$raw_subjects = $settings['contact_subjects_list'] ?? "General Inquiry";
$subject_array = explode("\n", str_replace("\r", "", $raw_subjects));

$status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validate_contact_form($_POST, $settings['turnstile_secret_key'])) {
        $stmt = $db->prepare("INSERT INTO contact_messages (sender_name, sender_email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $_POST['subject'],
            $_POST['message']
        ]);

        $to = $settings['contact_recipient_email'];
        $email_subject = "New Web Contact: " . $_POST['subject'];
        $email_body = "From: {$_POST['name']} ({$_POST['email']})\n\n{$_POST['message']}";
        mail($to, $email_subject, $email_body);

        $status = "<div class='alert alert-success shadow-sm'><i class='bi bi-check-all me-2'></i>Message sent successfully!</div>";
    } else {
        $status = "<div class='alert alert-danger shadow-sm'>Security verification failed. Please try again.</div>";
    }
}
?>

<div class="container py-5">
    <?= $status ?>
    
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border-top border-primary border-5">
                <h2 class="fw-bold text-dark mb-4">Send us a Message</h2>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-primary"><i class="bi bi-person"></i></span>
                                <input type="text" name="name" class="form-control border-start-0 ps-0" placeholder="John Doe" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-primary"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="john@doe.com" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Subject</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-primary"><i class="bi bi-tag"></i></span>
                            <select name="subject" class="form-select border-start-0 ps-0" required>
                                <option value="" disabled selected>How can we help?</option>
                                <?php foreach ($subject_array as $sub): ?>
                                    <?php $sub = trim($sub); if ($sub == "") continue; ?>
                                    <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Your Message</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-primary align-items-start pt-2"><i class="bi bi-chat-left-dots"></i></span>
                            <textarea name="message" class="form-control border-start-0 ps-0" rows="6" placeholder="Describe your inquiry in detail..." required></textarea>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="cf-turnstile" data-sitekey="<?= $settings['turnstile_site_key'] ?>"></div>
                    </div>

                    <button type="submit" class="btn btn-primary px-5 py-3 rounded-3 shadow-sm fw-bold hover-lift w-100 w-md-auto">
                        <i class="bi bi-send-fill me-2"></i>Send Message
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="ps-lg-4">
                <div class="mb-5">
                    <h4 class="fw-bold">Contact Information</h4>
                    <p class="text-muted">Prefer a different way to reach out? You can find us on our social platforms or via the details below.</p>
                    
                    <ul class="list-unstyled mt-4">
                        <li class="d-flex mb-3">
                            <div class="icon-box bg-primary-subtle text-primary rounded-3 p-2 me-3">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <span class="d-block fw-bold">Location</span>
                                <span class="text-muted small">San Francisco, CA 94103</span>
                            </div>
                        </li>
                        <li class="d-flex mb-3">
                            <div class="icon-box bg-primary-subtle text-primary rounded-3 p-2 me-3">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div>
                                <span class="d-block fw-bold">Business Hours</span>
                                <span class="text-muted small">Mon — Fri: 9:00 AM - 5:00 PM</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="mb-5">
                    <h5 class="fw-bold mb-3">Follow Us</h5>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-primary rounded-circle"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="btn btn-outline-primary rounded-circle"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-outline-primary rounded-circle"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="btn btn-outline-primary rounded-circle"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>

                <div class="p-4 bg-light rounded-4 border-start border-4 border-secondary">
                    <h6 class="fw-bold"><i class="bi bi-shield-lock me-2"></i>Privacy Notice</h6>
                    <p class="small text-muted mb-0">
                        By submitting this form, you agree to our <strong>Terms of Service</strong>. We value your privacy; your name and email will only be used to process this inquiry in accordance with our <strong>Privacy Policy</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
queue_css("
    .icon-box { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .input-group-text { border-color: #dee2e6; }
    .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: none; }
");
?>