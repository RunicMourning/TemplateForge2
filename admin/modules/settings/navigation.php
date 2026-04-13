<?php
/**
 * Settings > Navigation
 */

// AJAX reorder
if (isset($_POST['update_order'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_navigation_order')) { http_response_code(403); die('Forbidden'); }
    foreach (($_POST['order'] ?? []) as $index => $id) {
        $db->prepare("UPDATE navigation SET sort_order = ? WHERE id = ?")->execute([$index, (int)$id]);
    }
    log_activity($db, 'NAV', 'Nav Reordered', 'Menu items rearranged');
    exit('success');
}

// Save (add or edit)
if (isset($_POST['save_nav'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_navigation_save')) { http_response_code(403); die('Forbidden'); }
    $id    = (int)($_POST['nav_id'] ?? 0) ?: null;
    $label = trim($_POST['label'] ?? '');
    $url   = trim($_POST['url'] ?? '');
    // Ensure leading slash for relative URLs
    if ($url && !str_starts_with($url, '/') && !str_starts_with($url, 'http') && !str_starts_with($url, '#') && !str_starts_with($url, 'mailto:')) {
        $url = '/' . $url;
    }
    $data = [$label, $url, $_POST['css_class'] ?? '', $_POST['css_id'] ?? '', (int)($_POST['sort_order'] ?? 0)];
    if ($id) {
        $stmt = $db->prepare("UPDATE navigation SET label=?, url=?, css_class=?, css_id=?, sort_order=? WHERE id=?");
        $data[] = $id;
        $stmt->execute($data);
        log_activity($db, 'NAV', 'Nav Item Updated', "Label: $label");
    } else {
        $db->prepare("INSERT INTO navigation (label, url, css_class, css_id, sort_order) VALUES (?,?,?,?,?)")->execute($data);
        log_activity($db, 'NAV', 'Nav Item Created', "Label: $label");
    }
}

// Delete
if (isset($_POST['delete_nav'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_navigation_delete')) { http_response_code(403); die('Forbidden'); }
    $db->prepare("DELETE FROM navigation WHERE id = ?")->execute([(int)($_POST['delete_nav'] ?? 0)]);
    log_activity($db, 'NAV', 'Nav Item Deleted', '');
}

$nav_items  = $db->query("SELECT * FROM navigation ORDER BY sort_order ASC")->fetchAll();
$editing_id = isset($_GET['edit_nav']) ? (int)$_GET['edit_nav'] : null;
$editing    = $editing_id ? array_values(array_filter($nav_items, fn($n) => $n['id'] === $editing_id))[0] ?? null : null;
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">Navigation</div>
        <div class="page-subtitle">Manage the main navigation menu &mdash; drag rows to reorder</div>
    </div>
</div>

<!-- Add / Edit form -->
<div class="a-card mb-3">
    <div class="a-card-header">
        <div class="a-card-title">
            <i class="bi bi-<?= $editing ? 'pencil' : 'plus-circle' ?>" style="color:var(--a-accent);"></i>
            <?= $editing ? 'Edit Link' : 'Add Link' ?>
        </div>
        <?php if ($editing): ?>
        <a href="index.php?view=settings&section=navigation" class="btn btn-ghost btn-sm">Cancel</a>
        <?php endif; ?>
    </div>
    <div class="a-card-body">
        <form method="POST" style="display:grid; grid-template-columns:1fr 1fr auto auto auto; gap:0.75rem; align-items:end;">
            <?= csrf_input('admin_navigation_save') ?>
            <?php if ($editing): ?>
            <input type="hidden" name="nav_id" value="<?= $editing['id'] ?>">
            <?php endif; ?>
            <div class="form-group mb-0">
                <label>Label</label>
                <input type="text" name="label" placeholder="About" required
                       value="<?= htmlspecialchars($editing['label'] ?? '') ?>">
            </div>
            <div class="form-group mb-0">
                <label>URL</label>
                <input type="text" name="url" id="nav-url" placeholder="/about.html" required
                       value="<?= htmlspecialchars($editing['url'] ?? '') ?>">
            </div>
            <div class="form-group mb-0">
                <label>Order</label>
                <input type="number" name="sort_order" style="width:70px;"
                       value="<?= $editing ? (int)$editing['sort_order'] : count($nav_items) ?>">
            </div>
            <div class="form-group mb-0">
                <label>CSS Class</label>
                <input type="text" name="css_class" style="width:110px;"
                       value="<?= htmlspecialchars($editing['css_class'] ?? '') ?>">
            </div>
            <div>
                <button type="submit" name="save_nav" class="btn btn-primary btn-sm">
                    <i class="bi bi-<?= $editing ? 'floppy' : 'plus-lg' ?>"></i>
                    <?= $editing ? 'Save' : 'Add' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Current menu items -->
<div class="a-card">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-list-ul" style="color:var(--a-accent);"></i> Menu Items</div>
    </div>
    <div class="a-card-body" style="padding:0;">
        <?php if (empty($nav_items)): ?>
        <p class="text-muted text-small" style="padding:1.5rem;">No navigation items yet.</p>
        <?php else: ?>
        <div class="table-wrap" style="border:none; border-radius:0;">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Label</th>
                        <th>URL</th>
                        <th style="width:60px; text-align:center;">Order</th>
                        <th style="width:100px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-nav">
                    <?php foreach ($nav_items as $item):
                        $is_editing = $editing_id === (int)$item['id'];
                    ?>
                    <tr data-id="<?= (int)$item['id'] ?>"
                        style="cursor:grab; <?= $is_editing ? 'background:rgba(79,126,248,0.06);' : '' ?>">
                        <td style="color:var(--a-text-muted); text-align:center;">
                            <i class="bi bi-grip-vertical"></i>
                        </td>
                        <td><strong><?= htmlspecialchars($item['label']) ?></strong></td>
                        <td><code><?= htmlspecialchars($item['url']) ?></code></td>
                        <td style="text-align:center;" class="sort-val"><?= (int)$item['sort_order'] ?></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="index.php?view=settings&section=navigation&edit_nav=<?= (int)$item['id'] ?>"
                               class="btn btn-ghost btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Remove «<?= htmlspecialchars(addslashes($item['label'])) ?>»?')">
                                <?= csrf_input('admin_navigation_delete') ?>
                                <input type="hidden" name="delete_nav" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Auto-prepend slash on blur
document.getElementById('nav-url')?.addEventListener('blur', function() {
    var v = this.value.trim();
    if (v && !v.startsWith('/') && !v.startsWith('http') && !v.startsWith('#') && !v.startsWith('mailto:')) {
        this.value = '/' + v;
    }
});

// Drag reorder
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('sortable-nav');
    if (!el) return;
    Sortable.create(el, {
        animation: 150,
        handle: '.bi-grip-vertical',
        onEnd: function() {
            var order = [];
            el.querySelectorAll('tr').forEach(function(row) { order.push(row.dataset.id); });
            var body = 'update_order=1&csrf_token=' + encodeURIComponent('<?= csrf_token('admin_navigation_order') ?>');
            order.forEach(function(id) { body += '&order[]=' + id; });
            fetch('index.php?view=settings&section=navigation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function() {
                el.querySelectorAll('tr').forEach(function(row, i) {
                    var sv = row.querySelector('.sort-val');
                    if (sv) sv.textContent = i;
                });
            });
        }
    });
});
</script>
