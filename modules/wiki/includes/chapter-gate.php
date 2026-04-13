<?php
/**
 * Wiki Module — ChapterGate
 *
 * Single authority for all wiki visibility decisions.
 * Nothing else checks whether an entry is visible — only this file.
 *
 * Rules:
 *   - No reveal_chapter_id → always visible.
 *   - reveal_chapter_id set, chapters table missing → visible (podcast module not loaded).
 *   - reveal_chapter_id set, chapter not found → hidden (defensive default).
 *   - reveal_chapter_id set, chapter found → visible if release_date <= today.
 *
 * Admin preview mode:
 *   Set $_SESSION['wiki_preview'] = true to bypass all gating.
 *   Only available to authenticated admin users.
 */

/**
 * Returns true if the entry should be visible to the current visitor.
 *
 * @param PDO   $db
 * @param array $entry  A wiki_entries row.
 * @return bool
 */
function wiki_entry_is_visible(PDO $db, array $entry): bool {
    // Admin preview bypasses everything
    if (wiki_is_preview_mode()) return true;

    // No gate set — always visible
    if (empty($entry['reveal_chapter_id'])) return true;

    $chapter = wiki_get_chapter($db, (int) $entry['reveal_chapter_id']);

    // Chapters table missing (podcast module not loaded) — fail open
    if ($chapter === null && !wiki_chapters_table_exists($db)) return true;

    // Chapter record missing — fail closed (defensive)
    if ($chapter === null) return false;

    // Compare release date against today
    return $chapter['release_date'] <= date('Y-m-d');
}

/**
 * Filter a list of wiki entry rows down to only visible ones.
 * Use this for index pages, search results, and cross-link displays.
 *
 * @param PDO   $db
 * @param array $entries  Array of wiki_entries rows.
 * @return array
 */
function wiki_filter_visible(PDO $db, array $entries): array {
    return array_values(array_filter($entries, fn($e) => wiki_entry_is_visible($db, $e)));
}

/**
 * Returns true if admin preview mode is active.
 * Only honoured when the session has an authenticated admin user.
 */
function wiki_is_preview_mode(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['wiki_preview']);
}

/**
 * Enable or disable admin preview mode.
 */
function wiki_set_preview_mode(bool $active): void {
    if (!empty($_SESSION['user_id'])) {
        $_SESSION['wiki_preview'] = $active;
    }
}

// ── Internal helpers ──────────────────────────────────────────────────────────

/**
 * Fetch a single chapter row by id.
 * Returns null both when the row doesn't exist AND when the table doesn't exist.
 */
function wiki_get_chapter(PDO $db, int $id): ?array {
    if (!wiki_chapters_table_exists($db)) return null;
    $stmt = $db->prepare('SELECT id, title, release_date, status FROM chapters WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Check whether the chapters table exists in this DB.
 * Result is cached per request in $GLOBALS to avoid repeated PRAGMA calls.
 */
function wiki_chapters_table_exists(PDO $db): bool {
    if (isset($GLOBALS['_wiki_chapters_exists'])) {
        return $GLOBALS['_wiki_chapters_exists'];
    }
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='chapters'");
    $GLOBALS['_wiki_chapters_exists'] = (bool) $stmt->fetchColumn();
    return $GLOBALS['_wiki_chapters_exists'];
}
