<?php
/**
 * Wiki Module — CRUD & Helper Functions
 */

// ── Entries ───────────────────────────────────────────────────────────────────

function wiki_get_entries(PDO $db, array $f = [], int $limit = 20, int $offset = 0): array {
    [$where, $params] = wiki_build_where($f);
    $stmt = $db->prepare("SELECT * FROM wiki_entries WHERE $where ORDER BY updated_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([...$params, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function wiki_count_entries(PDO $db, array $f = []): int {
    [$where, $params] = wiki_build_where($f);
    $stmt = $db->prepare("SELECT COUNT(*) FROM wiki_entries WHERE $where");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Public-facing entry list — applies visibility gating.
 * Use this for frontend index pages and search. Never for admin views.
 */
function wiki_get_public_entries(PDO $db, array $f = [], int $limit = 20, int $offset = 0): array {
    // Force published status for public queries
    $f['status'] = 'published';
    [$where, $params] = wiki_build_where($f);
    $stmt = $db->prepare("SELECT * FROM wiki_entries WHERE $where ORDER BY title ASC LIMIT ? OFFSET ?");
    $stmt->execute([...$params, $limit, $offset]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return wiki_filter_visible($db, $rows);
}

/**
 * Public-facing single entry fetch — applies visibility gating.
 * Returns null if the entry is gated, so callers don't need to check separately.
 */
function wiki_get_public_entry(PDO $db, string $slug): ?array {
    $stmt = $db->prepare('SELECT * FROM wiki_entries WHERE slug = ? AND status = ?');
    $stmt->execute([$slug, 'published']);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$entry) return null;
    return wiki_entry_is_visible($db, $entry) ? $entry : ['_gated' => true, 'title' => $entry['title']];
}

function wiki_build_where(array $f): array {
    $where = ['1=1']; $params = [];
    if (!empty($f['type']))   { $where[] = 'entry_type = ?'; $params[] = $f['type']; }
    if (!empty($f['status'])) { $where[] = 'status = ?';     $params[] = $f['status']; }
    if (!empty($f['search'])) {
        $where[] = '(title LIKE ? OR body LIKE ?)';
        $params[] = "%{$f['search']}%"; $params[] = "%{$f['search']}%";
    }
    return [implode(' AND ', $where), $params];
}

function wiki_get_entry(PDO $db, int $id): ?array {
    $stmt = $db->prepare('SELECT * FROM wiki_entries WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function wiki_get_entry_by_slug(PDO $db, string $slug): ?array {
    $stmt = $db->prepare('SELECT * FROM wiki_entries WHERE slug = ? AND status = ?');
    $stmt->execute([$slug, 'published']);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function wiki_save_entry(PDO $db, array $d): int {
    $slug = wiki_unique_slug($db, $d['slug'] ?: wiki_slugify($d['title']), $d['id'] ?? null);
    $body = $d['body'] ?: '[]';
    $chapter = $d['reveal_chapter_id'] ?: null;

    if (!empty($d['id'])) {
        $db->prepare('UPDATE wiki_entries SET title=?, slug=?, entry_type=?, body=?, status=?, reveal_chapter_id=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')
           ->execute([$d['title'], $slug, $d['entry_type'], $body, $d['status'], $chapter, $d['id']]);
        return (int) $d['id'];
    }

    $db->prepare('INSERT INTO wiki_entries (title, slug, entry_type, body, status, reveal_chapter_id) VALUES (?,?,?,?,?,?)')
       ->execute([$d['title'], $slug, $d['entry_type'], $body, $d['status'], $chapter]);
    return (int) $db->lastInsertId();
}

function wiki_delete_entry(PDO $db, int $id): void {
    $db->prepare('DELETE FROM wiki_entries WHERE id = ?')->execute([$id]);
}

function wiki_slugify(string $title): string {
    $s = mb_strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    return trim(preg_replace('/[\s-]+/', '-', $s), '-');
}

function wiki_unique_slug(PDO $db, string $base, ?int $exclude = null): string {
    $slug = $base; $i = 1;
    while (true) {
        $sql = 'SELECT id FROM wiki_entries WHERE slug = ?' . ($exclude ? ' AND id != ?' : '');
        $stmt = $db->prepare($sql);
        $stmt->execute($exclude ? [$slug, $exclude] : [$slug]);
        if (!$stmt->fetchColumn()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

// ── Slug redirect history ─────────────────────────────────────────────────────

function wiki_migrate_slug_redirects(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS wiki_slug_redirects (
        old_slug TEXT PRIMARY KEY,
        new_slug TEXT NOT NULL,
        entry_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

function wiki_record_slug_change(PDO $db, int $entry_id, string $old_slug, string $new_slug): void {
    if ($old_slug === $new_slug) return;
    // Point old slug to new slug; update any existing redirect chains
    $db->prepare("INSERT INTO wiki_slug_redirects (old_slug, new_slug, entry_id)
                  VALUES (?,?,?)
                  ON CONFLICT(old_slug) DO UPDATE SET new_slug = excluded.new_slug")
       ->execute([$old_slug, $new_slug, $entry_id]);
    // Remove any redirect that now points to itself
    $db->prepare("DELETE FROM wiki_slug_redirects WHERE old_slug = new_slug")->execute();
}

function wiki_resolve_redirect(PDO $db, string $slug): ?string {
    $stmt = $db->prepare("SELECT new_slug FROM wiki_slug_redirects WHERE old_slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetchColumn() ?: null;
}

// ── Orphan link detection ─────────────────────────────────────────────────────

function wiki_get_orphaned_links(PDO $db): array {
    return $db->query("
        SELECT wl.id, wl.source_entry_id, wl.target_entry_id,
               s.title AS source_title, t.title AS target_title
        FROM wiki_links wl
        LEFT JOIN wiki_entries s ON s.id = wl.source_entry_id
        LEFT JOIN wiki_entries t ON t.id = wl.target_entry_id
        WHERE s.id IS NULL OR t.id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function wiki_prune_orphaned_links(PDO $db): int {
    $db->exec("DELETE FROM wiki_links
               WHERE source_entry_id NOT IN (SELECT id FROM wiki_entries)
                  OR target_entry_id NOT IN (SELECT id FROM wiki_entries)");
    return (int) $db->query("SELECT changes()")->fetchColumn();
}

// ── Bulk status update ────────────────────────────────────────────────────────

function wiki_bulk_set_status(PDO $db, array $ids, string $status): void {
    if (empty($ids) || !in_array($status, ['draft', 'published'], true)) return;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare("UPDATE wiki_entries SET status = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id IN ($placeholders)")
       ->execute([$status, ...array_map('intval', $ids)]);
}

// ── JSON export ───────────────────────────────────────────────────────────────

function wiki_export_json(PDO $db): string {
    $entries = $db->query("SELECT * FROM wiki_entries ORDER BY entry_type, title ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($entries as &$e) {
        $e['images'] = wiki_get_images($db, (int) $e['id']);
        $links = wiki_get_links($db, (int) $e['id']);
        $e['linked_entries'] = array_column($links, 'title');
    }
    return json_encode([
        'exported_at' => date('c'),
        'count'       => count($entries),
        'entries'     => $entries,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Returns the list of valid entry types.
 * Reads from settings table if $db provided, otherwise returns the default set.
 */
function wiki_entry_types(?PDO $db = null): array {
    $defaults = ['character', 'place', 'faction', 'concept', 'creature', 'artifact', 'event'];
    if (!$db) return $defaults;
    try {
        $row = $db->prepare("SELECT value FROM settings WHERE key = 'wiki_types'");
        $row->execute();
        $val = $row->fetchColumn();
        if (!$val) return $defaults;
        $types = array_values(array_filter(array_map('trim', explode(',', $val))));
        return $types ?: $defaults;
    } catch (Exception) {
        return $defaults;
    }
}

// ── Media & Images ────────────────────────────────────────────────────────────

function wiki_save_media(PDO $db, array $file, string $doc_root): ?int {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $rel_dir = 'uploads/wiki/' . date('Y/m') . '/';
    $abs_dir = $doc_root . '/' . $rel_dir;
    if (!is_dir($abs_dir)) mkdir($abs_dir, 0755, true);

    $filename = bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $abs_dir . $filename)) return null;

    $db->prepare('INSERT INTO media (filename, original_name, mime_type, file_size, url) VALUES (?,?,?,?,?)')
       ->execute([$filename, $file['name'], $file['type'] ?? '', $file['size'] ?? 0, '/' . $rel_dir . $filename]);
    return (int) $db->lastInsertId();
}

function wiki_get_images(PDO $db, int $entry_id): array {
    $stmt = $db->prepare('SELECT wi.*, m.url, m.original_name FROM wiki_images wi JOIN media m ON wi.media_id = m.id WHERE wi.entry_id = ? ORDER BY wi.sort_order ASC');
    $stmt->execute([$entry_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function wiki_attach_image(PDO $db, int $entry_id, int $media_id, string $role = 'inline'): void {
    $ord = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM wiki_images WHERE entry_id = ?');
    $ord->execute([$entry_id]);
    $db->prepare('INSERT OR IGNORE INTO wiki_images (entry_id, media_id, image_role, sort_order) VALUES (?,?,?,?)')
       ->execute([$entry_id, $media_id, $role, (int) $ord->fetchColumn()]);
}

function wiki_remove_image(PDO $db, int $image_id): void {
    $db->prepare('DELETE FROM wiki_images WHERE id = ?')->execute([$image_id]);
}

// ── Cross-links ───────────────────────────────────────────────────────────────

function wiki_get_links(PDO $db, int $entry_id): array {
    $stmt = $db->prepare('SELECT wl.id, wl.target_entry_id AS linked_id, e.title, e.entry_type, e.slug FROM wiki_links wl JOIN wiki_entries e ON e.id = wl.target_entry_id WHERE wl.source_entry_id = ? ORDER BY e.title ASC');
    $stmt->execute([$entry_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function wiki_add_link(PDO $db, int $a, int $b): void {
    $stmt = $db->prepare('INSERT OR IGNORE INTO wiki_links (source_entry_id, target_entry_id) VALUES (?,?)');
    $stmt->execute([$a, $b]);
    $stmt->execute([$b, $a]);
}

function wiki_remove_link(PDO $db, int $a, int $b): void {
    $db->prepare('DELETE FROM wiki_links WHERE (source_entry_id=? AND target_entry_id=?) OR (source_entry_id=? AND target_entry_id=?)')
       ->execute([$a, $b, $b, $a]);
}

// ── Chapters (soft dependency — podcast module may not be loaded) ─────────────

function wiki_get_chapters(PDO $db): array {
    try {
        return $db->query('SELECT id, title, episode_number FROM chapters ORDER BY episode_number ASC')
                  ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception) {
        return [];
    }
}
