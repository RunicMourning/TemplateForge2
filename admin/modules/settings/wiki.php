<?php
/**
 * Settings > Wiki
 * Available: $db, $settings
 */

$msg = '';

// ── Save general settings ─────────────────────────────────────────────────────
if (isset($_POST['save_wiki_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_settings_save')) {
        http_response_code(403); log_activity($db, 'SECURITY', 'CSRF Blocked', 'wiki settings'); die('Forbidden');
    }
    $prefix = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['wiki_slug_prefix'] ?? 'wiki'))) ?: 'wiki';
    $title  = trim($_POST['wiki_title'] ?? 'Wiki') ?: 'Wiki';

    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    $stmt->execute(['wiki_slug_prefix', $prefix]);
    $stmt->execute(['wiki_title', $title]);

    log_activity($db, 'SETTINGS', 'Wiki Settings Saved', "prefix: $prefix, title: $title");
    $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Wiki settings saved.</div>';
    $settings = get_site_settings($db);
}

// ── Add entry type ────────────────────────────────────────────────────────────
if (isset($_POST['wiki_add_type'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_types_edit')) {
        http_response_code(403); die('Forbidden');
    }
    $new_type = strtolower(preg_replace('/[^a-z0-9]/', '', trim($_POST['new_type'] ?? '')));
    if ($new_type) {
        $types = wiki_entry_types($db);
        if (!in_array($new_type, $types, true)) {
            $types[] = $new_type;
            $db->prepare("INSERT INTO settings (key, value) VALUES ('wiki_types', ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value")
               ->execute([implode(',', $types)]);
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Type "' . htmlspecialchars($new_type) . '" added.</div>';
            $settings = get_site_settings($db);
        } else {
            $msg = '<div class="alert alert-warning">Type "' . htmlspecialchars($new_type) . '" already exists.</div>';
        }
    }
}

// ── Remove entry type ─────────────────────────────────────────────────────────
if (isset($_POST['wiki_remove_type'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_types_edit')) {
        http_response_code(403); die('Forbidden');
    }
    $remove = $_POST['wiki_remove_type'] ?? '';
    $types  = array_values(array_filter(wiki_entry_types($db), fn($t) => $t !== $remove));
    if (!empty($types)) {
        $db->prepare("INSERT INTO settings (key, value) VALUES ('wiki_types', ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value")
           ->execute([implode(',', $types)]);
        $msg = '<div class="alert alert-warning"><i class="bi bi-trash"></i> Type "' . htmlspecialchars($remove) . '" removed.</div>';
        $settings = get_site_settings($db);
    } else {
        $msg = '<div class="alert alert-danger">Cannot remove the last remaining type.</div>';
    }
}

$current_prefix = $settings['wiki_slug_prefix'] ?? 'wiki';
$current_title  = $settings['wiki_title']       ?? 'Wiki';
$current_types  = wiki_entry_types($db);

$type_counts = [];
foreach ($current_types as $t) {
    $row = $db->prepare("SELECT COUNT(*) FROM wiki_entries WHERE entry_type = ?");
    $row->execute([$t]);
    $type_counts[$t] = (int) $row->fetchColumn();
}
?>

<?= $msg ?>

<!-- General settings -->
<form method="POST" action="index.php?view=settings&section=wiki">
    <?= csrf_input('wiki_settings_save') ?>
    <div class="a-card mb-3">
        <div class="a-card-header"><div class="a-card-title"><i class="bi bi-journal-bookmark"></i> General</div></div>
        <div class="a-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Wiki Title</label>
                    <input type="text" name="wiki_title" value="<?= htmlspecialchars($current_title) ?>" placeholder="Wiki">
                    <div class="form-help">Shown in page headings and browser title.</div>
                </div>
                <div class="form-group">
                    <label>Slug Prefix</label>
                    <div class="input-group">
                        <span style="display:flex;align-items:center;padding:0 0.75rem;background:var(--a-surface-2);border:1px solid var(--a-border);border-right:none;border-radius:var(--a-radius) 0 0 var(--a-radius);color:var(--a-text-muted);font-size:0.85rem;white-space:nowrap;">yoursite.com /</span>
                        <input type="text" name="wiki_slug_prefix" value="<?= htmlspecialchars($current_prefix) ?>" placeholder="wiki" style="border-radius:0 var(--a-radius) var(--a-radius) 0;">
                    </div>
                    <div class="form-help">Current: <code>/<?= htmlspecialchars($current_prefix) ?></code>. Update nav link if changed.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="a-flex" style="justify-content:flex-end; margin-bottom:2rem;">
        <button type="submit" name="save_wiki_settings" class="btn btn-primary"><i class="bi bi-floppy"></i> Save Settings</button>
    </div>
</form>

<!-- Entry type manager -->
<div class="a-card">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-tags"></i> Entry Types</div>
    </div>
    <div class="a-table-wrap" style="border:none; border-radius:0;">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th style="width:80px; text-align:center;">Entries</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($current_types as $t): ?>
            <tr>
                <td>
                    <span class="badge badge-blue" style="text-transform:capitalize;"><?= htmlspecialchars($t) ?></span>
                </td>
                <td style="text-align:center;">
                    <span class="text-muted" style="font-size:0.85rem;"><?= $type_counts[$t] ?? 0 ?></span>
                </td>
                <td style="text-align:right;">
                    <?php if (($type_counts[$t] ?? 0) === 0): ?>
                    <form method="POST" action="index.php?view=settings&section=wiki"
                          onsubmit="return confirm('Remove type &quot;<?= htmlspecialchars($t) ?>&quot;?')">
                        <?= csrf_input('wiki_types_edit') ?>
                        <button type="submit" name="wiki_remove_type" value="<?= htmlspecialchars($t) ?>"
                                class="btn btn-sm btn-ghost text-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:0.75rem;" title="Has entries — cannot remove">In use</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="a-card-body" style="border-top:1px solid var(--a-border);">
        <form method="POST" action="index.php?view=settings&section=wiki" class="a-flex gap-2">
            <?= csrf_input('wiki_types_edit') ?>
            <input type="text" name="new_type" placeholder="newtype" pattern="[a-z0-9]+"
                   style="max-width:200px;" title="Lowercase letters and numbers only">
            <button type="submit" name="wiki_add_type" class="btn btn-outline">
                <i class="bi bi-plus-lg"></i> Add Type
            </button>
        </form>
        <div class="form-help mt-1">Lowercase letters and numbers only. Cannot remove types that have entries.</div>
    </div>
</div>
