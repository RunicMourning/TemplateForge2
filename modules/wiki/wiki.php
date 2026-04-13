<?php
/**
 * Wiki Module — Entry Point
 *
 * Loaded by the module loader after the DB connection is established.
 * Responsibilities here are limited to:
 *   1. Running schema migrations
 *   2. Registering hooks and filters
 *   3. Including sub-components as they are built out
 *
 * Phase 1: schema only. Hook stubs are registered so dependent modules
 * (e.g. podcast) can safely add_filter/add_hook against wiki hooks now
 * without errors, even before Phase 3 wires up the real logic.
 */

defined('ABSPATH') || define('ABSPATH', __DIR__);
$wiki_dir = __DIR__;

// ── Schema ────────────────────────────────────────────────────────────────────

require_once $wiki_dir . '/includes/schema.php';
require_once $wiki_dir . '/includes/wiki-functions.php';
require_once $wiki_dir . '/includes/chapter-gate.php';
require_once $wiki_dir . '/includes/wiki-linker.php';

if (isset($db)) {
    wiki_run_migrations($db);
    wiki_linker_migrate($db);
    wiki_migrate_slug_redirects($db);
}

// ── AJAX: wiki entry autocomplete ─────────────────────────────────────────────
if (isset($_GET['wiki_autocomplete']) && isset($db)) {
    wiki_autocomplete_response($db, $_GET['wiki_autocomplete']);
}

// ── Filter: resolve [[Links]] in rendered output ──────────────────────────────
if (function_exists('add_filter')) {
    add_filter('wiki_entry_render', function(string $html, array $entry) use ($db): string {
        $s = $GLOBALS['settings'] ?? [];
        return wiki_resolve_links($db, $html, $s);
    }, priority: 10);
}

// ── Inline CSS via hook ───────────────────────────────────────────────────────
add_hook('head_bottom', function() use ($wiki_dir) {
    echo '<link rel="stylesheet" href="/modules/wiki/assets/wiki.css">' . PHP_EOL;
});

// ── Public routing ────────────────────────────────────────────────────────────

/**
 * Called by index.php when wiki_prefix + wiki_slug params are detected.
 * Returns true if the request was handled (caller should exit rendering).
 */
function wiki_dispatch(PDO $db, array $settings, string $prefix, string $slug): bool {
    $wiki_prefix = $prefix;
    $wiki_dir    = __DIR__;

    // Prefix must match the configured setting
    $configured = $settings['wiki_slug_prefix'] ?? 'wiki';
    if ($prefix !== $configured) return false;

    // Master index: /lore (slug will be empty string when only prefix is given)
    if ($slug === '' || $slug === 'index') {
        global $page;
        $page = ['title' => ($settings['wiki_title'] ?? 'Wiki')];
        include $wiki_dir . '/templates/wiki-index.php';
        return true;
    }

    // Type index: /lore/character, /lore/place, etc.
    if (in_array($slug, wiki_entry_types($db), true)) {
        global $page;
        $wiki_type = $slug;
        $page = ['title' => ucfirst($slug) . 's — ' . ($settings['wiki_title'] ?? 'Wiki')];
        include $wiki_dir . '/templates/type-index.php';
        return true;
    }

    // Single entry — check redirect first, then live entry
    $redirect_slug = wiki_resolve_redirect($db, $slug);
    if ($redirect_slug) {
        $prefix_path = '/' . $prefix . '/' . $redirect_slug;
        header("Location: $prefix_path", true, 301);
        exit;
    }

    $entry = wiki_get_public_entry($db, $slug);

    if ($entry === null) return false; // Not found — fall through to 404

    global $page;
    if (!empty($entry['_gated'])) {
        $entry_title = $entry['title'];
        $page = ['title' => 'Not Yet Available'];
        include $wiki_dir . '/templates/gated.php';
        return true;
    }

    $page = ['title' => $entry['title'] . ' — ' . ($settings['wiki_title'] ?? 'Wiki')];
    include $wiki_dir . '/templates/entry.php';
    return true;
}

// ── Hook stubs ────────────────────────────────────────────────────────────────

// Filter: apply_filter('wiki_entry_render', $html, $entry_row)
// Action: run_hook('chapter_released', $chapter_id)

// ── Routing (Phase 4 will populate this) ─────────────────────────────────────

// Public wiki routes will be registered here once frontend templates exist.
// Pattern: /{wiki_slug_prefix}/{slug}  e.g. /lore/caen
