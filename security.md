# Security Review

## Scope and approach
This review was performed as a lightweight code audit of the PHP application in this repository, focusing on:

- Authentication/session handling
- Input/output handling (especially XSS and upload surfaces)
- Administrative action protections
- Deployment hardening defaults

### What is already good
- **Prepared statements** are used in many query paths, reducing SQL injection risk.
- **CSRF token validation** is present on major admin POST actions.
- Passwords are hashed with `password_hash()` and verified with `password_verify()`.

---

## Findings

## 1) Stored/DOM XSS risk from unsanitized rich content rendering (**High**)
**Where observed**
- `templates/page.php` renders page content directly: `<?php echo $page['content']; ?>`
- `templates/post-single.php` renders post content directly: `<?php echo $post['content']; ?>`
- Admin page editor preview also renders raw saved HTML in `admin/modules/pages.php`.

**Why this matters**
If an attacker (or compromised admin account) can store script-bearing HTML, that script executes for visitors/admins. This can lead to session theft, CSRF bypass chaining, and persistent site compromise.

**Suggestions**
- Add an HTML sanitization layer (allowlist-based) before save or before render (preferred: before save + defense-in-depth on render).
- If full HTML is required, use a robust sanitizer policy (e.g., permit only safe tags/attributes/protocols).
- Add a restrictive Content Security Policy (CSP) to limit script execution impact.

---

## 2) Multiple output-encoding gaps in admin UI and links (**High**)
**Where observed**
- Username is printed without escaping in admin header dropdown.
- Success message for user creation interpolates raw username into HTML.
- Page slug is printed unescaped in admin page list URL fragment.
- Popular-post links print slug directly into `href`.
- Navigation URL in frontend header is emitted without escaping or URL validation.

**Why this matters**
Unescaped variables in HTML/attribute contexts can enable stored/reflected XSS or HTML injection if attacker-controlled data reaches those sinks.

**Suggestions**
- Apply context-appropriate encoding consistently:
  - `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` for HTML text/attributes.
  - `rawurlencode()` for path segments such as slugs.
- For URLs from DB (`navigation.url`), validate allowed schemes (e.g., only relative paths or `https`) and then encode.
- Add a small helper library (`e()`, `e_attr()`, `e_url_path_segment()`) and require its usage in templates.

---

## 3) File upload controls are incomplete (**High**)
**Where observed**
- `admin/includes/media_widget.php` accepts uploads and moves files by extension/name, but does not strictly enforce MIME/content checks, size limits, or a hard extension allowlist for **all** uploaded files.

**Why this matters**
Without strict validation, attackers can upload active content or oversized payloads. Even if execution is not immediate, this can enable stored XSS, malware hosting, or resource exhaustion.

**Suggestions**
- Enforce strict allowlist by both extension **and** MIME (`finfo_file`).
- Reject disallowed types (consider images only if business requirements allow).
- Enforce file size limits server-side.
- Store uploads outside web root if possible, or serve via controlled download endpoint.
- Normalize filenames completely (do not preserve user-supplied names except as metadata).

---

## 4) Debug/error display enabled in runtime controllers (**Medium**)
**Where observed**
- `index.php`, `post.php`, and `blog.php` enable `display_errors` and `E_ALL`.

**Why this matters**
In production, verbose errors can expose stack traces, paths, SQL details, and internal logic that materially assist attackers.

**Suggestions**
- Disable `display_errors` in production.
- Log errors to file/monitoring only (`log_errors=On`, secure log location).
- Gate debug mode behind environment variables, never hardcoded defaults.

---

## 5) Session hardening and auth abuse controls appear minimal (**Medium**)
**Where observed**
- Sessions are started, and ID regeneration occurs on login (good), but secure cookie flags/strict mode are not explicitly set in app code.
- No login rate-limiting/lockout/captcha evidence in admin login flow.

**Why this matters**
Weak session settings increase cookie theft/fixation risk in some deployments. Missing brute-force controls increases credential-stuffing risk.

**Suggestions**
- Set session cookie parameters explicitly before `session_start()`:
  - `httponly=true`, `secure=true` (HTTPS), `samesite=Lax/Strict`
  - `session.use_strict_mode=1`
- Add throttling for login attempts (IP + account key), progressive delays, and optional captcha.
- Record failed login telemetry and alert on spikes.

---

## 6) Authorization model is coarse-grained (**Low/Medium**)
**Where observed**
- Admin authorization is binary (`user_id` in session). There is no visible role/permission check separating high-risk actions (user management, settings, content changes).

**Why this matters**
Compromise of any admin account grants broad control. Lack of least privilege increases blast radius.

**Suggestions**
- Introduce RBAC (e.g., super-admin/editor/viewer).
- Protect sensitive modules (users/settings/logs) with role checks.
- Require recent-auth confirmation for critical actions (e.g., user deletion, credential changes).

---

## Recommended remediation order
1. **Immediate (0-2 days):** fix output encoding gaps and disable production error display.
2. **Short term (this sprint):** add robust HTML sanitization strategy + upload hardening.
3. **Next sprint:** session cookie hardening, login throttling, and RBAC foundations.
4. **Ongoing:** add automated security checks (linters, SAST, dependency and secret scanning) in CI.

## Optional hardening extras
- Add security headers: CSP, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `X-Frame-Options`/`frame-ancestors`.
- Add audit events for permission failures and sensitive setting changes.
- Add backups/restore drills for `db/cms.db` and upload assets.
