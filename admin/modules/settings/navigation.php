<?php
/**
 * Settings > Navigation
 * Moved from admin/modules/navigation.php
 */

// AJAX reorder handler
if (isset($_POST['update_order'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_navigation_order')) { http_response_code(403); die('Forbidden'); }
    foreach (($_POST['order'] ?? []) as $index => $id) {
        $db->prepare("UPDATE navigation SET sort_order = ? WHERE id = ?")->execute([$index, (int)$id]);
    }
    log_activity($db, 'NAV', 'Nav Reordered', 'Menu items rearranged');
    exit('success');
}

if (isset($_POST['save_nav'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_navigation_save')) { http_response_code(403); die('Forbidden'); }
    $id    = $_POST['nav_id'] ?? null;
    $label = trim($_POST['label'] ?? '');
    $url   = trim($_POST['url'] ?? '');
    $data  = [$label, $url, $_POST['css_class'] ?? '', $_POST['css_id'] ?? '', (int)($_POST['sort_order'] ?? 0)];
    if ($id) {
        $stmt = $db->prepare("UPDATE navigation SET label=?, url=?, css_class=?, css_id=?, sort_order=? WHERE id=?");
        $data[] = (int)$id;
        $stmt->execute($data);
        log_activity($db, 'NAV', 'Nav Item Updated', "Label: $label");
    } else {
        $db->prepare("INSERT INTO navigation (label, url, css_class, css_id, sort_order) VALUES (?,?,?,?,?)")->execute($data);
        log_activity($db, 'NAV', 'Nav Item Created', "Label: $label");
    }
}

if (isset($_POST['delete_nav'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_navigation_delete')) { http_response_code(403); die('Forbidden'); }
    $db->prepare("DELETE FROM navigation WHERE id = ?")->execute([(int)($_POST['delete_nav'] ?? 0)]);
    log_activity($db, 'NAV', 'Nav Item Deleted', '');
}

$nav_items = $db->query("SELECT * FROM navigation ORDER BY sort_order ASC")->fetchAll();
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">Navigation</div>
        <div class="page-subtitle">Manage the main navigation menu &mdash; drag rows to reorder</div>
    </div>
</div>

<div class="a-card mb-3">
    <div class="a-card-header">
        <div class="a-card-title"><i class="bi bi-plus-circle" style="color:var(--a-accent);"></i> Add Link</div>
    </div>
    <div class="a-card-body">
        <form method="POST" style="display:grid; grid-template-columns:1fr 1fr auto auto auto; gap:0.75rem; align-items:end;">
            <?php echo csrf_input('admin_navigation_save'); ?>
            <div class="form-group mb-0">
                <label>Label</label>
                <input type="text" name="label" placeholder="About" required>
            </div>
            <div class="form-group mb-0">
                <label>URL</label>
                <input type="text" name="url" placeholder="about.html" required>
            </div>
            <div class="form-group mb-0">
                <label>Order</label>
                <input type="number" name="sort_order" value="<?php echo count($nav_items); ?>" style="width:70px;">
            </div>
            <div class="form-group mb-0">
                <label>CSS Class</label>
                <input type="text" name="css_class" style="width:110px;">
            </div>
            <div>
                <button type="submit" name="save_nav" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
        </form>
    </div>
</div>

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
                        <th style="width:40px;"></th>
                        <th>Label</th>
                        <th>URL</th>
                        <th style="width:70px; text-align:center;">Order</th>
                        <th style="width:80px; text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody id="sortable-nav">
                    <?php foreach ($nav_items as $item): ?>
                    <tr data-id="<?php echo (int)$item['id']; ?>" style="cursor:grab;">
                        <td style="color:var(--a-text-muted); text-align:center;">
                            <i class="bi bi-grip-vertical"></i>
                        </td>
                        <td><strong><?php echo htmlspecialchars($item['label']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($item['url']); ?></code></td>
                        <td style="text-align:center;" class="sort-val"><?php echo (int)$item['sort_order']; ?></td>
                        <td style="text-align:center;">
                            <form method="POST" onsubmit="return confirm('Remove this link?')">
                                <?php echo csrf_input('admin_navigation_delete'); ?>
                                <input type="hidden" name="delete_nav" value="<?php echo (int)$item['id']; ?>">
                                <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-trash"></i></button>
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
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('sortable-nav');
    if (!el) return;
    Sortable.create(el, {
        animation: 150,
        handle: '.bi-grip-vertical',
        onEnd: function() {
            var order = [];
            el.querySelectorAll('tr').forEach(function(row) { order.push(row.dataset.id); });
            var body = 'update_order=1&csrf_token=' + encodeURIComponent('<?php echo csrf_token('admin_navigation_order'); ?>');
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
