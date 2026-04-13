<?php
/**
 * Wiki Module — Linker
 *
 * Resolves [[Entry Title]] syntax in HTML content at render time.
 * Also maintains the wiki_post_links reverse index (which posts reference which entries).
 *
 * Render-time resolution means:
 *   - Renamed slugs auto-update without re-saving posts
 *   - Gated entries render as plain text, not broken hrefs
 *   - No stored markup to go stale
 */

// ── Schema ────────────────────────────────────────────────────────────────────

function wiki_linker_migrate(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS wiki_post_links (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        post_slug TEXT    NOT NULL,
        post_type TEXT    NOT NULL DEFAULT 'post'
                  CHECK(post_type IN ('post', 'page', 'wiki')),
        entry_id  INTEGER NOT NULL REFERENCES wiki_entries(id) ON DELETE CASCADE,
        UNIQUE(post_slug, post_type, entry_id)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_wiki_post_links_entry
               ON wiki_post_links (entry_id)");
}

// ── Resolver ──────────────────────────────────────────────────────────────────

/**
 * Replace [[Title]] tokens in HTML with resolved links or plain text.
 * Safe to run on any HTML string — only replaces the [[...]] pattern.
 *
 * @param PDO    $db
 * @param string $html
 * @param array  $settings   Site settings array (needs wiki_slug_prefix)
 * @return string
 */
function wiki_resolve_links(PDO $db, string $html, array $settings): string {
    if (strpos($html, '[[') === false) return $html;

    $prefix = $settings['wiki_slug_prefix'] ?? 'wiki';
    $cache  = [];

    return preg_replace_callback('/\[\[([^\]]+)\]\]/', function($m) use ($db, $prefix, &$cache) {
        $title = trim($m[1]);
        if (isset($cache[$title])) return $cache[$title];

        $stmt = $db->prepare('SELECT id, slug, status, reveal_chapter_id FROM wiki_entries WHERE title = ? COLLATE NOCASE');
        $stmt->execute([$title]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entry || $entry['status'] !== 'published') {
            return $cache[$title] = htmlspecialchars($title);
        }

        if (!wiki_entry_is_visible($db, $entry)) {
            return $cache[$title] = htmlspecialchars($title);
        }

        $url    = '/' . $prefix . '/' . htmlspecialchars($entry['slug']);
        $result = '<a href="' . $url . '" class="wiki-inline-link">' . htmlspecialchars($title) . '</a>';
        return $cache[$title] = $result;
    }, $html);
}

// ── Appears-in index ──────────────────────────────────────────────────────────

/**
 * Scan HTML for [[Title]] tokens and record which wiki entries are referenced.
 * Call this when saving a post, page, or wiki entry body.
 *
 * @param PDO    $db
 * @param string $post_slug   The slug of the post/page/wiki entry being saved
 * @param string $post_type   'post', 'page', or 'wiki'
 * @param string $html        Raw HTML body content
 */
function wiki_index_links(PDO $db, string $post_slug, string $post_type, string $html): void {
    // Clear existing records for this source
    $db->prepare('DELETE FROM wiki_post_links WHERE post_slug = ? AND post_type = ?')
       ->execute([$post_slug, $post_type]);

    if (strpos($html, '[[') === false) return;

    preg_match_all('/\[\[([^\]]+)\]\]/', $html, $matches);
    $titles = array_unique($matches[1]);
    if (empty($titles)) return;

    $insert = $db->prepare('INSERT OR IGNORE INTO wiki_post_links (post_slug, post_type, entry_id)
                            SELECT ?, ?, id FROM wiki_entries WHERE title = ? COLLATE NOCASE');
    foreach ($titles as $title) {
        $insert->execute([$post_slug, $post_type, trim($title)]);
    }
}

/**
 * Return all posts/pages that reference a given wiki entry.
 * Used for the "Appears in" section on the public entry page.
 *
 * @param PDO $db
 * @param int $entry_id
 * @return array  Rows with post_slug, post_type, and post title where available
 */
function wiki_get_appearances(PDO $db, int $entry_id): array {
    try {
        $stmt = $db->prepare("
            SELECT wpl.post_slug, wpl.post_type,
                   CASE wpl.post_type
                       WHEN 'post' THEN (SELECT title FROM posts WHERE slug = wpl.post_slug LIMIT 1)
                       WHEN 'page' THEN (SELECT title FROM pages WHERE slug = wpl.post_slug LIMIT 1)
                       ELSE wpl.post_slug
                   END AS title
            FROM wiki_post_links wpl
            WHERE wpl.entry_id = ?
            ORDER BY wpl.post_type, wpl.post_slug
        ");
        $stmt->execute([$entry_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception) {
        return [];
    }
}

// ── AJAX endpoint — entry lookup for autocomplete ─────────────────────────────

/**
 * Return JSON array of wiki entry titles matching a search string.
 * Called via fetch() from the editor toolbar autocomplete.
 */
function wiki_autocomplete_response(PDO $db, string $q): void {
    header('Content-Type: application/json');
    if (strlen($q) < 1) { echo '[]'; exit; }
    $stmt = $db->prepare("SELECT title FROM wiki_entries WHERE title LIKE ? AND status = 'published' ORDER BY title ASC LIMIT 10");
    $stmt->execute(['%' . $q . '%']);
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}
