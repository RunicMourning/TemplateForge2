<?php
/**
 * Wiki Module — Schema Migration
 *
 * Safe to run on every boot. All statements use CREATE TABLE IF NOT EXISTS
 * and INSERT OR IGNORE so re-runs are harmless.
 *
 * Dependency note: reveal_chapter_id is a soft FK to chapters.id.
 * The chapters table is owned by the podcast module. No hard constraint
 * is declared here — if podcast is not loaded, wiki functions normally;
 * reveal_chapter_id simply stays NULL.
 */

function wiki_run_migrations(PDO $db): void {

    // ── wiki_entries ──────────────────────────────────────────────────────────
    //
    // body: block-based JSON array. Shape of each block:
    //   { "type": "paragraph|heading|image|divider", "content": "...", ...type-specific keys }
    // The admin form constructs this — raw JSON is never authored by hand.
    //
    // entry_type controls which fields the admin form renders and how the
    // frontend template lays out the entry.
    //
    $db->exec("CREATE TABLE IF NOT EXISTS wiki_entries (
        id                INTEGER  PRIMARY KEY AUTOINCREMENT,
        slug              TEXT     NOT NULL UNIQUE,
        title             TEXT     NOT NULL,
        entry_type        TEXT     NOT NULL DEFAULT 'concept'
                          CHECK(entry_type IN
                              ('character','place','faction','concept','creature','artifact','event')),
        body              TEXT     NOT NULL DEFAULT '[]',
        status            TEXT     NOT NULL DEFAULT 'draft'
                          CHECK(status IN ('draft','published')),
        reveal_chapter_id INTEGER  DEFAULT NULL,
        created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Index for public listing queries (type + status filters)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_wiki_entries_type_status
               ON wiki_entries (entry_type, status)");

    // ── wiki_images ───────────────────────────────────────────────────────────
    //
    // image_role drives how the frontend renders the image:
    //   cover    → full-width banner at top of entry
    //   portrait → sidebar thumbnail (character/creature entries)
    //   map      → full-width map block (place entries)
    //   inline   → embedded within body content
    //
    $db->exec("CREATE TABLE IF NOT EXISTS wiki_images (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        entry_id   INTEGER NOT NULL REFERENCES wiki_entries(id) ON DELETE CASCADE,
        media_id   INTEGER NOT NULL,
        image_role TEXT    NOT NULL DEFAULT 'inline'
                   CHECK(image_role IN ('cover','portrait','map','inline')),
        sort_order INTEGER NOT NULL DEFAULT 0
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_wiki_images_entry
               ON wiki_images (entry_id, sort_order)");

    // ── wiki_links ────────────────────────────────────────────────────────────
    //
    // Bidirectional cross-reference table. Both directions are stored
    // explicitly so lookups in either direction are a simple WHERE clause.
    // Unique constraint prevents duplicate pairs.
    //
    $db->exec("CREATE TABLE IF NOT EXISTS wiki_links (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        source_entry_id  INTEGER NOT NULL REFERENCES wiki_entries(id) ON DELETE CASCADE,
        target_entry_id  INTEGER NOT NULL REFERENCES wiki_entries(id) ON DELETE CASCADE,
        UNIQUE(source_entry_id, target_entry_id)
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_wiki_links_source
               ON wiki_links (source_entry_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_wiki_links_target
               ON wiki_links (target_entry_id)");

    // ── media ─────────────────────────────────────────────────────────────────
    //
    // Central media record table. Wiki images reference this via media_id.
    // Defined here for now; moves to core when other modules need it.
    //
    $db->exec("CREATE TABLE IF NOT EXISTS media (
        id            INTEGER  PRIMARY KEY AUTOINCREMENT,
        filename      TEXT     NOT NULL,
        original_name TEXT     NOT NULL,
        mime_type     TEXT     NOT NULL DEFAULT '',
        file_size     INTEGER  NOT NULL DEFAULT 0,
        url           TEXT     NOT NULL,
        uploaded_at   DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // ── Settings defaults ─────────────────────────────────────────────────────
    //
    // INSERT OR IGNORE — won't overwrite a value the admin has already changed.
    //
    $db->exec("INSERT OR IGNORE INTO settings (key, value)
               VALUES ('wiki_slug_prefix', 'wiki')");
    $db->exec("INSERT OR IGNORE INTO settings (key, value)
               VALUES ('wiki_title', 'Wiki')");
    $db->exec("INSERT OR IGNORE INTO settings (key, value)
               VALUES ('wiki_types', 'character,place,faction,concept,creature,artifact,event')");
}
