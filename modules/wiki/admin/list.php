<?php
/**
 * Wiki Admin — List View
 * Available: $db, $wiki_msg
 */

$filters = [
    'type'   => $_GET['type']   ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['s']      ?? '',
];
$page_num   = max(1, (int) ($_GET['p'] ?? 1));
$limit      = 15;
$offset     = ($page_num - 1) * $limit;
$entries    = wiki_get_entries($db, $filters, $limit, $offset);
$total      = wiki_count_entries($db, $filters);
$page_count = max(1, (int) ceil($total / $limit));
$qs_filters = '&type=' . urlencode($filters['type']) . '&status=' . urlencode($filters['status']) . '&s=' . urlencode($filters['search']);

$type_badge = [
    'character' => 'badge-blue',   'place'    => 'badge-green',
    'faction'   => 'badge-purple', 'concept'  => 'badge-yellow',
    'creature'  => 'badge-red',    'artifact' => 'badge-yellow',
    'event'     => 'badge-purple',
];
?>

<div class="page-title-bar">
    <div>
        <div class="page-title"><i class="bi bi-journal-bookmark"></i> <?= htmlspecialchars($settings['wiki_title'] ?? 'Wiki') ?></div>
        <div class="page-subtitle"><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?></div>
    </div>
    <div class="a-flex gap-2">
        <?php if (wiki_is_preview_mode()): ?>
            <a href="index.php?view=wiki&wiki_preview=0" class="btn btn-outline" style="color:var(--a-warning);border-color:var(--a-warning);">
                <i class="bi bi-eye"></i> Preview ON — Disable
            </a>
        <?php else: ?>
            <a href="index.php?view=wiki&wiki_preview=1" class="btn btn-ghost">
                <i class="bi bi-eye-slash"></i> Preview Gating
            </a>
        <?php endif; ?>
        <a href="index.php?view=wiki&wiki_export=1" class="btn btn-ghost" title="Export all entries as JSON">
            <i class="bi bi-download"></i> Export
        </a>
        <a href="index.php?view=wiki&action=edit" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Entry
        </a>
    </div>
</div>

<?= $wiki_msg ?>

<?php
$orphans = function_exists('wiki_get_orphaned_links') ? wiki_get_orphaned_links($db) : [];
if (!empty($orphans)):
?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle"></i>
    <?= count($orphans) ?> orphaned cross-link<?= count($orphans) !== 1 ? 's' : '' ?> detected — entries were deleted but links remain.
    <form method="POST" action="index.php?view=wiki" style="display:inline; margin-left:0.75rem;">
        <?= csrf_input('wiki_orphans') ?>
        <button type="submit" name="wiki_prune_orphans" class="btn btn-sm btn-warning">Prune Now</button>
    </form>
</div>
<?php endif; ?>

<div class="a-card mb-3">
    <div class="a-card-body">
        <form method="GET" action="index.php" class="a-flex gap-2 flex-wrap">
            <input type="hidden" name="view" value="wiki">
            <input type="text" name="s" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Search entries…" style="max-width:220px;">
            <select name="type">
                <option value="">All types</option>
                <?php foreach (wiki_entry_types($db) as $t): ?>
                <option value="<?= $t ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All statuses</option>
                <option value="draft"     <?= $filters['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
            <button type="submit" class="btn btn-outline"><i class="bi bi-funnel"></i> Filter</button>
            <?php if (array_filter($filters)): ?>
            <a href="index.php?view=wiki" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="a-card">
    <?php if (empty($entries)): ?>
    <div class="empty-state">
        <span class="empty-icon"><i class="bi bi-journal-x"></i></span>
        <p>No entries found. <a href="index.php?view=wiki&action=edit">Create the first one.</a></p>
    </div>
    <?php else: ?>
    <div class="a-table-wrap">
        <form method="POST" action="index.php?view=wiki" id="bulkForm">
            <?= csrf_input('wiki_bulk') ?>
            <div class="a-flex gap-2 a-card-body" style="border-bottom:1px solid var(--a-border);padding:0.6rem 1rem;">
                <select name="bulk_status" style="width:auto;">
                    <option value="">— Bulk action —</option>
                    <option value="published">Set Published</option>
                    <option value="draft">Set Draft</option>
                </select>
                <button type="submit" name="wiki_bulk_status" class="btn btn-sm btn-outline"
                        onclick="return document.querySelectorAll('.bulk-cb:checked').length > 0 || (alert('Select at least one entry.'), false)">
                    Apply
                </button>
                <label style="margin-left:auto;font-size:0.8rem;color:var(--a-text-muted);cursor:pointer;">
                    <input type="checkbox" id="bulk-all" onchange="document.querySelectorAll('.bulk-cb').forEach(c=>c.checked=this.checked)">
                    Select all
                </label>
            </div>
        <table>
            <thead>
                <tr>
                    <th style="width:32px;"></th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $e): ?>
            <tr>
                <td><input type="checkbox" name="bulk_ids[]" value="<?= $e['id'] ?>" class="bulk-cb" form="bulkForm"></td>
                <td>
                    <a href="index.php?view=wiki&action=edit&id=<?= $e['id'] ?>" class="fw-semibold">
                        <?= htmlspecialchars($e['title']) ?>
                    </a>
                    <div style="font-size:0.75rem;color:var(--a-text-muted);">/<?= htmlspecialchars($e['slug']) ?></div>
                </td>
                <td><span class="badge <?= $type_badge[$e['entry_type']] ?? '' ?>"><?= ucfirst($e['entry_type']) ?></span></td>
                <td><span class="badge <?= $e['status'] === 'published' ? 'badge-green' : '' ?>"><?= ucfirst($e['status']) ?></span></td>
                <td class="text-muted" style="font-size:0.8rem;"><?= date('M j, Y', strtotime($e['updated_at'])) ?></td>
                <td style="text-align:right;white-space:nowrap;">
                    <a href="index.php?view=wiki&action=edit&id=<?= $e['id'] ?>" class="btn btn-sm btn-outline">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form method="POST" action="index.php?view=wiki" style="display:inline;"
                          onsubmit="return confirm('Delete «<?= htmlspecialchars(addslashes($e['title'])) ?>»? This cannot be undone.');">
                        <input type="hidden" name="wiki_entry_id" value="<?= $e['id'] ?>">
                        <?= csrf_input('wiki_entry_delete') ?>
                        <button type="submit" name="wiki_delete_entry" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </form>
    </div>
    <?php if ($page_count > 1): ?>
    <div class="a-card-body" style="border-top:1px solid var(--a-border);">
        <div class="a-flex-between">
            <span class="text-muted" style="font-size:0.8rem;">Page <?= $page_num ?> of <?= $page_count ?></span>
            <div class="a-flex gap-1">
                <?php if ($page_num > 1): ?>
                <a href="?view=wiki&p=<?= $page_num-1 . $qs_filters ?>" class="btn btn-sm btn-outline">← Prev</a>
                <?php endif; ?>
                <?php if ($page_num < $page_count): ?>
                <a href="?view=wiki&p=<?= $page_num+1 . $qs_filters ?>" class="btn btn-sm btn-outline">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
