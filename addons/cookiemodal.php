<?php
/**
 * Addon Name: Cookie Consent Modal
 */

// Hook 1: Footer
add_hook('footer_bottom', function() {
    ?>
    <div id="cookieConsentModal" style="position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(0, 0, 0, 0.85); color: white; visibility: hidden; opacity: 0; transition: opacity 0.3s ease-in-out, visibility 0.3s; padding: 15px 20px; text-align: center; z-index: 1050;">
      <div style="max-width: 600px; margin: auto; display: flex; justify-content: space-between; align-items: center; gap: 15px;">
        <p class="mb-0" style="flex: 1;">
          We use essential cookies for site functionality. <a href="/privacy.html" class="text-white text-decoration-underline">Privacy Policy</a>.
        </p>
        <button id="acceptCookies" class="btn btn-primary">Accept</button>
      </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (!localStorage.getItem("cookieConsent")) {
            let modal = document.getElementById("cookieConsentModal");
            modal.style.visibility = "visible";
            modal.style.opacity = "1";
            document.getElementById("acceptCookies").onclick = function() {
                localStorage.setItem("cookieConsent", "true");
                modal.style.opacity = "0";
            };
        }
    });
    </script>
    <?php
});

// Hook 2: Privacy (ENSURE THIS IS INSIDE THE SAME PHP BLOCK)
add_hook('privacy_policy_disclosures', function() {
    echo '<section class="mb-3 ps-3 border-start">
            <h4 class="h6 fw-semibold mb-1">Cookie Consent & Preferences</h4>
            <p class="text-muted">
                This website uses local storage to save your consent preferences. No personal data is collected or transmitted to third parties.
            </p>
          </section>';
});