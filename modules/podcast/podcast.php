<?php
/**
 * Podcast Module — Entry Point
 * Loaded by the module loader after DB connection is established.
 */

$podcast_dir = __DIR__;

require_once $podcast_dir . '/includes/schema.php';
require_once $podcast_dir . '/includes/podcast-functions.php';

if (isset($db)) {
    podcast_run_migrations($db);
}

// ── CSS ───────────────────────────────────────────────────────────────────────
add_hook('head_bottom', function() {
    echo '<link rel="stylesheet" href="/modules/podcast/assets/podcast.css">' . PHP_EOL;
});

// ── Wiki integration: append "First appears: Episode X" to entry render ───────
add_filter('wiki_entry_render', function(string $html, array $entry) use ($db): string {
    if (empty($entry['reveal_chapter_id'])) return $html;

    $chapter = podcast_get_chapter($db, (int) $entry['reveal_chapter_id']);
    if (!$chapter) return $html;

    // Find episode linked to this chapter
    $stmt = $db->prepare('SELECT episode_number, title, slug FROM episodes WHERE chapter_id = ? AND status = ? LIMIT 1');
    $stmt->execute([$chapter['id'], 'published']);
    $episode = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$episode) return $html;

    $prefix = $GLOBALS['settings']['podcast_slug_prefix'] ?? 'episodes';
    $label  = htmlspecialchars("First appears: Episode {$episode['episode_number']} — {$episode['title']}");
    $url    = '/' . $prefix . '/' . htmlspecialchars($episode['slug']);

    $badge  = '<div class="wiki-first-appears"><i class="bi bi-mic"></i> '
            . '<a href="' . $url . '">' . $label . '</a></div>';

    return $html . $badge;
}, priority: 20);

// ── Public routing ────────────────────────────────────────────────────────────

/**
 * Handle /episodes and /episodes/{slug} requests.
 * Called by index.php when wiki_prefix matches podcast_slug_prefix.
 */
function podcast_dispatch(PDO $db, array $settings, string $prefix, string $slug): bool {
    $podcast_dir = __DIR__;

    if ($prefix !== ($settings['podcast_slug_prefix'] ?? 'episodes')) return false;

    if ($slug === '' || $slug === 'index') {
        global $page;
        $page = ['title' => 'Episodes'];
        include $podcast_dir . '/templates/archive.php';
        return true;
    }

    $episode = podcast_get_episode_by_slug($db, $slug);
    if (!$episode) return false;

    global $page;
    $page = ['title' => "Ep. {$episode['episode_number']}: {$episode['title']}"];
    include $podcast_dir . '/templates/episode.php';
    return true;
}

// Register dispatch — index.php checks podcast after wiki
$GLOBALS['_podcast_dispatch_registered'] = true;

// ── chapter_released action ───────────────────────────────────────────────────
// Fired externally when a chapter's release_date passes (future: cron/cache bust).
// Podcast module is the natural owner of this hook's implementation.
// run_hook('chapter_released', $chapter_id) — wired here for Phase 7+ use.
