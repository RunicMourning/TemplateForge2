<?php
/**
 * Podcast Module — Schema Migration
 * Safe to run on every boot (IF NOT EXISTS / INSERT OR IGNORE).
 *
 * Owns the chapters table. The wiki module references chapters via a soft FK
 * (nullable reveal_chapter_id) and handles the case where this table is absent.
 */

function podcast_run_migrations(PDO $db): void {

    // ── chapters ──────────────────────────────────────────────────────────────
    // release_date drives ChapterGate visibility in the wiki module.
    // status 'released' is informational; date comparison is authoritative.
    $db->exec("CREATE TABLE IF NOT EXISTS chapters (
        id             INTEGER  PRIMARY KEY AUTOINCREMENT,
        title          TEXT     NOT NULL,
        episode_number INTEGER  NOT NULL UNIQUE,
        release_date   TEXT     NOT NULL,
        status         TEXT     NOT NULL DEFAULT 'scheduled'
                       CHECK(status IN ('scheduled','released')),
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_chapters_release
               ON chapters (release_date)");

    // ── episodes ──────────────────────────────────────────────────────────────
    // audio_url: direct mp3/m4a link OR embed code (iframe/player HTML).
    // linked_post_slug: show notes pulled from the matching blog post at render time.
    // chapter_id: soft FK → chapters.id — links episode to its chapter gate.
    $db->exec("CREATE TABLE IF NOT EXISTS episodes (
        id               INTEGER  PRIMARY KEY AUTOINCREMENT,
        episode_number   INTEGER  NOT NULL UNIQUE,
        title            TEXT     NOT NULL,
        slug             TEXT     NOT NULL UNIQUE,
        audio_url        TEXT     NOT NULL DEFAULT '',
        description      TEXT     NOT NULL DEFAULT '',
        linked_post_slug TEXT     DEFAULT NULL,
        chapter_id       INTEGER  DEFAULT NULL,
        release_date     TEXT     NOT NULL,
        status           TEXT     NOT NULL DEFAULT 'draft'
                         CHECK(status IN ('draft','published')),
        created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_episodes_status_release
               ON episodes (status, release_date)");

    // ── Podcast-level settings defaults ───────────────────────────────────────
    $defaults = [
        'podcast_title'       => '',
        'podcast_author'      => '',
        'podcast_description' => '',
        'podcast_cover_url'   => '',
        'podcast_language'    => 'en',
        'podcast_category'    => 'Fiction',
        'podcast_slug_prefix' => 'episodes',
    ];
    $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $stmt->execute([$k, $v]);
    }
}
