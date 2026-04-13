<?php
/**
 * TemplateForge2 Module Loader
 *
 * Discovers modules in /modules/<slug>/module.json, syncs them to the DB,
 * and includes each enabled module's entry file.
 *
 * Module folder convention:
 *   /modules/
 *     wiki/
 *       module.json   ← required manifest
 *       wiki.php      ← entry point (value of "entry" key in manifest)
 *
 * module.json shape:
 * {
 *   "id":          "wiki",
 *   "name":        "Wiki / Lore Bible",
 *   "version":     "1.0.0",
 *   "description": "Lore entries, chapter gating, cross-links.",
 *   "entry":       "wiki.php",
 *   "hooks":       ["wiki_entry_render", "chapter_released"]
 * }
 */

/**
 * Scan /modules/, sync to DB, include enabled modules.
 *
 * @param PDO    $db
 * @param string $modules_dir  Absolute path to the /modules/ directory.
 */
function load_modules(PDO $db, string $modules_dir): void {
    if (!is_dir($modules_dir)) return;

    _ensure_modules_table($db);

    foreach (glob($modules_dir . '/*/module.json') as $manifest_path) {
        $manifest = _read_manifest($manifest_path);
        if ($manifest === null) continue;

        $module_dir = dirname($manifest_path);
        _sync_module_record($db, $manifest);

        if (!_module_enabled($db, $manifest['id'])) continue;

        $entry = $module_dir . '/' . $manifest['entry'];
        if (is_file($entry)) {
            include_once $entry;
        }
    }
}

/**
 * Return all module records from the DB (for admin UI).
 *
 * @param PDO $db
 * @return array  Keyed by module id.
 */
function get_all_modules(PDO $db): array {
    _ensure_modules_table($db);
    $rows = $db->query("SELECT * FROM modules ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    return array_column($rows, null, 'id');
}

/**
 * Enable or disable a module by id.
 *
 * @param PDO    $db
 * @param string $id
 * @param bool   $enabled
 */
function set_module_enabled(PDO $db, string $id, bool $enabled): void {
    $db->prepare("UPDATE modules SET enabled = ? WHERE id = ?")
       ->execute([(int) $enabled, $id]);
}

// ─── Internal helpers ─────────────────────────────────────────────────────────

function _ensure_modules_table(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS modules (
        id           TEXT PRIMARY KEY,
        name         TEXT NOT NULL,
        version      TEXT NOT NULL DEFAULT '0.0.0',
        description  TEXT NOT NULL DEFAULT '',
        enabled      INTEGER NOT NULL DEFAULT 1,
        installed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

function _read_manifest(string $path): ?array {
    $raw = file_get_contents($path);
    if ($raw === false) return null;

    $data = json_decode($raw, true);
    if (!is_array($data)) return null;

    $required = ['id', 'name', 'version', 'entry'];
    foreach ($required as $key) {
        if (empty($data[$key])) return null;
    }

    $data['description'] = $data['description'] ?? '';
    return $data;
}

function _sync_module_record(PDO $db, array $manifest): void {
    // Insert if new (enabled by default); update name/version if already known.
    $db->prepare("INSERT INTO modules (id, name, version, description, enabled)
                  VALUES (?, ?, ?, ?, 1)
                  ON CONFLICT(id) DO UPDATE SET
                      name        = excluded.name,
                      version     = excluded.version,
                      description = excluded.description")
       ->execute([$manifest['id'], $manifest['name'], $manifest['version'], $manifest['description']]);
}

function _module_enabled(PDO $db, string $id): bool {
    $row = $db->prepare("SELECT enabled FROM modules WHERE id = ?");
    $row->execute([$id]);
    $result = $row->fetchColumn();
    return $result !== false && (bool) $result;
}
