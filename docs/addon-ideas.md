# Addon Ideas (Hook-Aware)

This project already has a practical hook surface (`head_top`, `head_bottom`, `navbar_end`, `content_before`, `content_start`, `content_after`, `footer_bottom`, `privacy_policy_disclosures`, `admin_settings_ui`).

Below are addon ideas that fit those existing hooks **without requiring core template rewrites**.

## 1) Announcement Bar Addon
- **Hooks:** `content_before`, optionally `admin_settings_ui`.
- **Use case:** Time-boxed notices (maintenance windows, promos, incident alerts).
- **Why useful:** Gives non-technical admins a quick broadcast channel.

## 2) Structured Data / JSON-LD Addon
- **Hooks:** `head_bottom`.
- **Use case:** Inject `Organization`, `WebSite`, `BlogPosting`, and `BreadcrumbList` schema.
- **Why useful:** Better search indexing and richer SERP cards.

## 3) Performance Optimizer Addon
- **Hooks:** `head_top`, `head_bottom`, `footer_bottom`.
- **Use case:** DNS prefetch, preconnect, deferred non-critical scripts, lazy-loading helpers.
- **Why useful:** Faster first paint and lower layout shift on slower devices.

## 4) Accessibility Helper Addon
- **Hooks:** `content_before`, `footer_bottom`.
- **Use case:** Skip-link injection, font scaling widget, high-contrast toggle persisted in localStorage.
- **Why useful:** Improves WCAG coverage with minimal template changes.

## 5) Consent Manager (Advanced)
- **Hooks:** `footer_bottom`, `privacy_policy_disclosures`, `admin_settings_ui`.
- **Use case:** Granular consent categories (essential/analytics/marketing) with script gating.
- **Why useful:** More compliant than a simple accept-only banner.

## 6) Search Telemetry Addon
- **Hooks:** `footer_bottom` + analytics pipeline.
- **Use case:** Capture on-site search terms and result clicks for "no-result" tuning.
- **Why useful:** Helps content teams identify missing pages.

## 7) Feature Flags / A-B Testing Addon
- **Hooks:** `head_bottom`, `content_before`, `content_after`, `admin_settings_ui`.
- **Use case:** Targeted UI/content experiments by random visitor bucket.
- **Why useful:** Iterative optimization without editing core templates.

## 8) Security Headers Reporter Addon
- **Hooks:** `head_top` (for CSP nonce helpers), `admin_settings_ui`.
- **Use case:** Validate and display effective CSP/HSTS/XFO policy status in admin.
- **Why useful:** Makes hardening posture visible and actionable.

## 9) Broken Link Monitor Addon
- **Hooks:** `footer_bottom` (beacon), `admin_settings_ui`.
- **Use case:** Capture 404 referrals and external-link failures into an admin report.
- **Why useful:** Better UX and SEO hygiene.

## 10) Newsletter / Lead Capture Addon
- **Hooks:** `content_after`, `footer_bottom`, `admin_settings_ui`, `privacy_policy_disclosures`.
- **Use case:** Embedded forms and provider integrations (Mailchimp, ConvertKit, etc.).
- **Why useful:** Directly supports audience growth and retention.

## 11) Open Graph / Social Card Addon
- **Hooks:** `head_bottom`.
- **Use case:** Inject `og:*` and `twitter:*` tags per page/post.
- **Why useful:** Predictable link previews across social platforms.

## 12) Commenting Addon (Pluggable)
- **Hooks:** `content_after`, `head_bottom`, `privacy_policy_disclosures`.
- **Use case:** Integrate Commento/Isso/Disqus-like systems with privacy disclosure.
- **Why useful:** Community engagement without changing post templates deeply.

---

## Priority implementation order
1. Structured Data / JSON-LD
2. Open Graph / Social Card
3. Announcement Bar
4. Consent Manager (Advanced)
5. Accessibility Helper

This order gives immediate SEO + UX wins using current hooks.
