# TemplateForge2

TemplateForge2 is a lightweight, file-first PHP CMS/blog engine built on SQLite with an admin panel, pluggable addons, and hook-based theme injection.

## Project analysis

### What this project is
- **Frontend CMS** with page routing by slug (`index.php`) and a fallback template system in `templates/`.
- **Blog engine** with listing (`blog.php`) and single post rendering (`post.php`).
- **Admin console** (`/admin`) for content, users, settings, analytics, logs, and navigation management.
- **Installer** (`Install.php`) that creates the SQLite schema and seeds starter content.
- **Addon system** (`addons/*.php`) using a central hook registry (`includes/hooks.php`) to inject UI, JS, and policy content.

### High-level architecture
- **Entry points**
  - `index.php`: page routing (`pageslug`) + template rendering.
  - `blog.php`: post list with optional category/author filtering.
  - `post.php`: single published post by `slug`.
  - `admin/index.php`: auth gate + module router via `view` parameter.
- **Core services**
  - `functions.php`: logging, settings loading, analytics tracking, sidebar helpers, utility functions.
  - `includes/hooks.php`: `add_hook()` / `run_hook()` plugin execution model.
- **Presentation**
  - `templates/`: header/footer/page/blog/post templates.
  - `sidebars/`: reusable widgets included by `templates/header.php`.
- **Persistence**
  - SQLite database at `db/cms.db`.
  - Created/seeded by `Install.php`.

### Key features discovered
- Prepared statements are used in most user-input query paths (pages/posts/users/login/contact insert).
- Addons can extend both frontend and admin settings UI through hooks.
- Analytics capture page view metadata and simple visitor fingerprinting (`sha256(ip+ua)`).
- Contact form supports Cloudflare Turnstile configuration and message storage.

## Quick start

### Requirements
- PHP **8.0+**
- `pdo_sqlite` extension
- Writable directories for `db/` and `uploads/`

### Run locally
```bash
php -S 0.0.0.0:8000
```
Then open:
- `http://localhost:8000/Install.php` for first-time setup
- `http://localhost:8000/` for the site
- `http://localhost:8000/admin/` for admin

## Directory map

```text
TemplateForge2/
├── index.php              # main page router
├── blog.php               # blog list route
├── post.php               # blog post route
├── Install.php            # installer + schema bootstrap
├── functions.php          # shared helpers/services
├── includes/
│   ├── hooks.php          # addon hook engine
│   └── css-registry.php   # inline css queue utilities
├── templates/             # frontend templates
├── sidebars/              # widget snippets
├── addons/                # plugin-style extensions
└── admin/                 # admin panel + modules
```

## Security testing (requested)

I ran static and syntax-level checks focused on common web-app risks.

### Checks performed
1. **Full PHP syntax lint** across all PHP files.
2. **Dangerous function scan** (`eval`, `shell_exec`, `system`, `unserialize`, etc.).
3. **Error/debug exposure scan** (`display_errors`, `error_reporting`).
4. **Input surface scan** for direct `$_GET` / `$_POST` usage.
5. **SQL usage scan** for `prepare()` vs raw `query()` patterns.
6. **CSRF surface scan** for token usage.

### Security findings summary

#### Good
- Core auth and most CRUD data operations use PDO prepared statements.
- Passwords are hashed with `password_hash()` and checked with `password_verify()`.

#### Risks to address
1. **Debug exposure in production**
   - `display_errors` and full `E_ALL` are enabled in public entry points.
2. **Missing CSRF protections**
   - Admin and contact form POST actions do not include CSRF tokens.
3. **Installer hardening gap**
   - Installer can still be reached after setup unless manually removed.
   - Fallback default password (`admin123`) exists if empty input is posted.
4. **Potential XSS vectors**
   - CMS content fields are rendered as raw HTML in templates (intentional for rich content, but untrusted editor input would be dangerous).
5. **Dynamic module include from query param**
   - `admin/index.php` builds module paths from `view`; file existence check reduces risk, but an allowlist would be safer.

### Recommended hardening
- Disable `display_errors` in runtime entry points and log errors server-side.
- Add CSRF tokens to all state-changing forms (admin + contact).
- Require non-empty strong installer password; block installer once `db/cms.db` exists.
- Add strict allowlist for admin module routing (`dashboard`, `pages`, `blog`, etc.).
- Limit/admin-sanitize HTML-capable fields or use trusted-editor-only policy with role controls.
- Add security headers (`Content-Security-Policy`, `X-Frame-Options`, `Referrer-Policy`, etc.).

## Development notes
- The addon/hook model is the primary extension mechanism; prefer hook-based customization over editing core templates directly.
- Keep SQLite path handling relative to project root to avoid environment drift.

## License
No license file was found in this repository. Add one if distribution or reuse is intended.
