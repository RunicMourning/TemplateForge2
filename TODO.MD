# Security To-Do List (TemplateForge2)

Date: 2026-02-06  
Scope: Actionable security worklist derived from static code review.

---

## How to use this list
- Work top-to-bottom by priority.
- Each item includes:
  - **Problem** (what is wrong)
  - **Why it matters** (impact/risk)
  - **What to do** (implementation tasks)
  - **Definition of done** (how to verify completion)

---

## 🔴 Priority 1 — Critical (do first)

### 1) Harden installer access controls in production
**Problem**
- `Install.php` can recreate and wipe the DB if someone posts installer fields.
- Existing DB file can be deleted during installation flow.

**Why it matters**
- Site takeover and total data loss are possible if installer remains reachable.

**What to do**
- Require a one-time setup secret/token for installation requests.
- Add server rule (Apache/Nginx) to deny public access to `Install.php` after deployment.

**Definition of done**
- Fresh setup requires valid setup token.
- Re-running installer cannot wipe existing DB.

---

### 2) Replace dynamic admin include routing with a hard allowlist
**Problem**
- Admin `view` parameter is used to build file include paths.

**Why it matters**
- Path traversal/LFI risk if attackers influence include target.

**What to do**
- Replace dynamic include with strict allowlist map, e.g.:
  - `dashboard`, `pages`, `blog`, `users`, `settings`, `navigation`, `logs`, `analytics`.
- Reject unknown modules with 400 or fallback to dashboard.
- Validate format using strict regex (`^[a-z_]+$`).

**Definition of done**
- Only known admin modules can be loaded.
- Requests like `?view=../../...` never reach `include`.

---

### 3) Harden admin file uploads (media tray)
**Problem**
- Uploaded files are accepted with minimal validation and written into web-accessible storage.

**Why it matters**
- Risk of web-shell upload or malicious file hosting.

**What to do**
- Enforce extension + MIME allowlist (use `finfo_file`).
- Generate server-side random filenames only; do not trust user basename.
- Add max-size limits and reject oversized uploads.
- Store uploads outside web root if possible.
- If uploads must stay in web root, enforce non-executable upload directory via server config (`php_flag engine off` / `RemoveHandler` / equivalent).

**Definition of done**
- `.php` and other executable payloads are rejected.
- MIME spoofing attempts fail.
- Upload directory cannot execute scripts.

---

## 🟠 Priority 2 — High (do next)

### 4) Add CSRF protection across all state-changing operations
**Problem**
- Admin forms and destructive operations lack CSRF tokens.
- Some deletes are triggered via GET.

**Why it matters**
- Logged-in admins can be tricked into unwanted actions.

**What to do**
- Implement CSRF token helper functions in shared code.
- Add hidden token fields to all mutation forms.
- Validate tokens before processing POST requests.
- Convert destructive GET actions to POST-only endpoints.
- Return 403 on CSRF failure and log attempts.

**Definition of done**
- All create/update/delete actions require valid CSRF token.
- GET requests cannot delete or mutate data.

---

### 5) Improve session security and login hardening
**Problem**
- Session ID is not regenerated after login.
- Cookie hardening flags are not explicitly set.

**Why it matters**
- Increased risk of session fixation and cookie abuse.

**What to do**
- Call `session_regenerate_id(true)` on successful authentication.
- Configure session cookie params before `session_start()`:
  - `HttpOnly=true`
  - `Secure=true` (in HTTPS)
  - `SameSite=Lax` or `Strict`
- Add idle timeout and absolute session timeout.
- Optionally bind session to user-agent hash (careful with false positives).

**Definition of done**
- Session ID rotates on login.
- Session cookies carry secure attributes.
- Expired sessions are invalidated automatically.

---

### 6) Stop displaying raw errors in production
**Problem**
- Public entry points enable `display_errors=1`.

**Why it matters**
- Attackers can learn internal paths, SQL details, and stack traces.

**What to do**
- Set `display_errors=0` for production.
- Enable `log_errors=1` with controlled file permissions.
- Gate verbose debug mode behind environment variable (`APP_ENV=dev`).

**Definition of done**
- Production pages never render PHP warnings/notices to users.
- Errors are captured in logs only.

---

## 🟡 Priority 3 — Medium (hardening / resilience)

### 7) Implement robust contact form validation and abuse controls
**Problem**
- `validate_contact_form()` is referenced and must be guaranteed and robust.
- No clear rate-limiting visible in flow.

**Why it matters**
- Contact endpoint is a common abuse target (spam, DoS, mail abuse).

**What to do**
- Implement/centralize `validate_contact_form()` in `functions.php`.
- Validate:
  - Turnstile response server-side
  - Email format (`filter_var`)
  - Input lengths and required fields
  - Safe subject handling
- Add per-IP rate limiting (e.g., N submissions per 15 minutes).
- Add CSRF token to contact form as well.

**Definition of done**
- Invalid/missing CAPTCHA and malformed fields are rejected.
- Excessive repeated submissions are throttled.

---

### 8) Reduce XSS risk in content and admin messaging
**Problem**
- Some outputs rely on raw stored content or unescaped interpolated variables.

**Why it matters**
- XSS can lead to admin hijack, data theft, and persistent compromise.

**What to do**
- Escape all user-derived values with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` by default.
- For rich text content, sanitize with allowlist sanitizer before save or render.
- Add baseline security headers:
  - `Content-Security-Policy`
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY` (or CSP frame-ancestors)
  - `Referrer-Policy`

**Definition of done**
- Script injection attempts are encoded or stripped.
- CSP blocks unexpected inline/script origins.

---

## Suggested sprint plan

### Sprint 1 (blocking risks)
- [ ] Setup token + production deny rule for installer
- [ ] Admin include allowlist
- [ ] Upload allowlist + MIME validation + non-executable uploads

### Sprint 2 (account security)
- [ ] CSRF framework rollout
- [ ] Convert delete actions to POST-only
- [ ] Session regeneration + cookie flags + timeouts

### Sprint 3 (resilience)
- [ ] Production error policy
- [ ] Contact validation + rate limiting
- [ ] XSS cleanup + security headers/CSP

---

## Security checks to run after each implementation phase

1. Static checks
- Run PHP lint across all files.
- Search for unsafe patterns (`include $`, destructive GET actions, raw output sinks).

2. Manual checks
- Verify installer is inaccessible in production without authorized setup flow.
- Attempt CSRF via cross-origin form to admin endpoints.
- Attempt upload of blocked extensions and spoofed MIME files.
- Verify session cookie flags in browser devtools.
- Verify no stack traces shown on forced errors.

3. Regression checks
- Ensure admin CRUD still works with CSRF enabled.
- Ensure legitimate uploads and contact submissions still succeed.
