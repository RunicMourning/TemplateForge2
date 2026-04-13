<?php
/**
 * Podcast Module — CRUD & Helper Functions
 */

// ── Episodes ──────────────────────────────────────────────────────────────────

function podcast_get_episodes(PDO $db, string $status = '', int $limit = 50, int $offset = 0): array {
    $where  = $status ? "WHERE status = ?" : "WHERE 1=1";
    $params = $status ? [$status] : [];
    $stmt   = $db->prepare("SELECT * FROM episodes $where ORDER BY episode_number DESC LIMIT ? OFFSET ?");
    $stmt->execute([...$params, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function podcast_count_episodes(PDO $db, string $status = ''): int {
    $where  = $status ? "WHERE status = ?" : "";
    $stmt   = $db->prepare("SELECT COUNT(*) FROM episodes $where");
    $stmt->execute($status ? [$status] : []);
    return (int) $stmt->fetchColumn();
}

function podcast_get_episode(PDO $db, int $id): ?array {
    $stmt = $db->prepare('SELECT * FROM episodes WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function podcast_get_episode_by_slug(PDO $db, string $slug): ?array {
    $stmt = $db->prepare("SELECT * FROM episodes WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function podcast_save_episode(PDO $db, array $d): int {
    $slug    = podcast_unique_slug($db, $d['slug'] ?: podcast_slugify($d['title']), $d['id'] ?? null);
    $chapter = (int) ($d['chapter_id'] ?? 0) ?: null;
    $post    = $d['linked_post_slug'] ?: null;

    if (!empty($d['id'])) {
        $db->prepare('UPDATE episodes SET episode_number=?, title=?, slug=?, audio_url=?, description=?,
                      linked_post_slug=?, chapter_id=?, release_date=?, status=?, updated_at=CURRENT_TIMESTAMP
                      WHERE id=?')
           ->execute([$d['episode_number'], $d['title'], $slug, $d['audio_url'], $d['description'],
                      $post, $chapter, $d['release_date'], $d['status'], $d['id']]);
        return (int) $d['id'];
    }

    $db->prepare('INSERT INTO episodes (episode_number, title, slug, audio_url, description,
                  linked_post_slug, chapter_id, release_date, status) VALUES (?,?,?,?,?,?,?,?,?)')
       ->execute([$d['episode_number'], $d['title'], $slug, $d['audio_url'], $d['description'],
                  $post, $chapter, $d['release_date'], $d['status']]);
    return (int) $db->lastInsertId();
}

function podcast_delete_episode(PDO $db, int $id): void {
    $db->prepare('DELETE FROM episodes WHERE id = ?')->execute([$id]);
}

function podcast_slugify(string $title): string {
    $s = mb_strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    return trim(preg_replace('/[\s-]+/', '-', $s), '-');
}

function podcast_unique_slug(PDO $db, string $base, ?int $exclude = null): string {
    $slug = $base; $i = 1;
    while (true) {
        $sql  = 'SELECT id FROM episodes WHERE slug = ?' . ($exclude ? ' AND id != ?' : '');
        $stmt = $db->prepare($sql);
        $stmt->execute($exclude ? [$slug, $exclude] : [$slug]);
        if (!$stmt->fetchColumn()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

// ── Chapters ──────────────────────────────────────────────────────────────────

function podcast_get_chapters(PDO $db): array {
    return $db->query('SELECT * FROM chapters ORDER BY episode_number ASC')
              ->fetchAll(PDO::FETCH_ASSOC);
}

function podcast_get_chapter(PDO $db, int $id): ?array {
    $stmt = $db->prepare('SELECT * FROM chapters WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function podcast_save_chapter(PDO $db, array $d): int {
    if (!empty($d['id'])) {
        $db->prepare('UPDATE chapters SET title=?, episode_number=?, release_date=?, status=? WHERE id=?')
           ->execute([$d['title'], $d['episode_number'], $d['release_date'], $d['status'], $d['id']]);
        return (int) $d['id'];
    }
    $db->prepare('INSERT INTO chapters (title, episode_number, release_date, status) VALUES (?,?,?,?)')
       ->execute([$d['title'], $d['episode_number'], $d['release_date'], $d['status']]);
    return (int) $db->lastInsertId();
}

function podcast_delete_chapter(PDO $db, int $id): void {
    $db->prepare('DELETE FROM chapters WHERE id = ?')->execute([$id]);
}

// ── Show notes from linked post ───────────────────────────────────────────────

function podcast_get_show_notes(PDO $db, ?string $post_slug): ?array {
    if (!$post_slug) return null;
    $stmt = $db->prepare("SELECT title, content FROM posts WHERE slug = ? AND status = 'published'");
    $stmt->execute([$post_slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ── RSS helpers ───────────────────────────────────────────────────────────────

function podcast_is_embed(string $audio_url): bool {
    return str_contains($audio_url, '<') && str_contains($audio_url, '>');
}
