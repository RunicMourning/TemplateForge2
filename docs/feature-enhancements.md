# Feature Enhancement Proposals

This document outlines high-impact enhancements for TemplateForge2, organized by implementation effort and user/business value.

## 1) Quick Wins (Low effort, high value)

### 1.1 Drafts + Scheduled Publishing
- Add `status` (`draft`, `published`, `scheduled`) and optional `publish_at` fields for pages/posts.
- Update admin forms to save drafts and schedule future publishing.
- Filter front-end queries to publish only eligible content.

**Why it helps**
- Enables safer content workflows.
- Improves editorial collaboration and release planning.

### 1.2 Revision History + Restore
- Store snapshots for pages/posts in a `content_revisions` table.
- Add a “History” panel in admin edit screens.
- Allow one-click rollback.

**Why it helps**
- Protects against accidental edits.
- Gives non-technical teams confidence when updating content.

### 1.3 SEO Essentials
- Per-page custom meta title/description.
- Auto-generated XML sitemap endpoint.
- Canonical URLs and optional Open Graph/Twitter card defaults.

**Why it helps**
- Improves discoverability and social share quality.
- Reduces need for manual SEO plugin workarounds.

### 1.4 Media Library Improvements
- Add upload metadata (alt text, caption, dimensions).
- Include media search and filtering in admin.
- Generate and serve responsive image sizes.

**Why it helps**
- Improves accessibility and performance.
- Makes content production faster.

## 2) Mid-Term Enhancements

### 2.1 Role-Based Access Control (RBAC)
- Extend users with roles/permissions (e.g., admin, editor, author, viewer).
- Gate module routes and mutating actions by capability.
- Add role management UI in admin.

**Why it helps**
- Enables larger teams and safer delegation.
- Reduces security risk from over-privileged accounts.

### 2.2 Content Taxonomy (Categories + Tags)
- Add categories/tags tables and mapping tables for posts.
- Support taxonomy archive pages and filters.
- Include taxonomy widgets in sidebars.

**Why it helps**
- Improves content organization and discovery.
- Supports editorial and SEO strategy growth.

### 2.3 Search Upgrade
- Move to SQLite FTS5 for full-text search over pages and posts.
- Add relevance ranking, excerpt snippets, and typo tolerance fallback.
- Add admin content search by status/author/date.

**Why it helps**
- Better user findability and lower bounce.
- Faster admin workflows.

### 2.4 Headless Content API
- Provide authenticated JSON endpoints for pages/posts/navigation.
- Add token-based auth for write endpoints.
- Keep hook points for addon extension.

**Why it helps**
- Enables decoupled front-ends (Next.js, mobile apps).
- Expands integration options without replacing the core CMS.

## 3) Strategic / Advanced Enhancements

### 3.1 Addon Marketplace + Signed Packages
- Define addon manifest format and version constraints.
- Add install/update/uninstall flows from admin.
- Support signature verification to prevent tampering.

**Why it helps**
- Builds ecosystem value and long-term extensibility.
- Lowers friction for feature adoption.

### 3.2 Workflow Automation
- Add event webhooks (publish, user created, form submitted).
- Add outbound integrations (Slack/Discord/email/webhook destinations).
- Create automation rule builder for non-technical users.

**Why it helps**
- Connects CMS events to ops/marketing workflows.
- Reduces manual work for teams.

### 3.3 Security Hardening Pack
- Enforce CSRF tokens on all mutating actions.
- Add login throttling + optional 2FA.
- Add security headers manager (CSP, HSTS, frame-ancestors, etc.).

**Why it helps**
- Improves production readiness.
- Reduces common exploit risks.

### 3.4 Performance + Caching Layer
- Add template fragment caching and full-page cache option.
- Add cache invalidation hooks on publish/update.
- Add performance dashboard in admin (TTFB, cache hit ratio).

**Why it helps**
- Better scalability on modest hosting.
- Faster perceived UX.

## 4) Suggested Prioritization

If the goal is broad impact with limited implementation risk, prioritize:

1. Drafts + Scheduled Publishing
2. Revision History + Restore
3. RBAC
4. SEO Essentials
5. FTS Search

## 5) Implementation Best Practices

- Keep database changes incremental with migration scripts.
- Introduce features behind config toggles when possible.
- Add tests for routing, authz checks, and content visibility logic.
- Preserve backward compatibility with existing hooks/addons.
- Document extension points and schema updates in `README.md`.
