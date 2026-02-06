<?php
// --- 1. Background AJAX Handler for Reordering ---
if (isset($_POST['update_order'])) {
    $order = $_POST['order']; // Array of IDs in new sequence
    foreach ($order as $index => $id) {
        $stmt = $db->prepare("UPDATE navigation SET sort_order = ? WHERE id = ?");
        $stmt->execute([$index, $id]);
    }
    log_activity($db, 'NAV', 'Nav Reordered', 'Menu items rearranged via drag-and-drop');
    exit('success'); // Stop further rendering for AJAX
}

// Handle Adding/Updating (as before)
if (isset($_POST['save_nav'])) {
    $id = $_POST['nav_id'] ?? null;
    $label = $_POST['label'];
    $url = $_POST['url'];
    $data = [$label, $url, $_POST['css_class'], $_POST['css_id'], $_POST['sort_order']];

    if ($id) {
        $stmt = $db->prepare("UPDATE navigation SET label=?, url=?, css_class=?, css_id=?, sort_order=? WHERE id=?");
        $data[] = $id;
        $stmt->execute($data);
        log_activity($db, 'NAV', 'Nav Item Updated', "Label: $label");
    } else {
        $stmt = $db->prepare("INSERT INTO navigation (label, url, css_class, css_id, sort_order) VALUES (?,?,?,?,?)");
        $stmt->execute($data);
        log_activity($db, 'NAV', 'Nav Item Created', "Label: $label");
    }
}

// Handle Delete (as before)
if (isset($_GET['delete_nav'])) {
    $del_id = $_GET['delete_nav'];
    $db->prepare("DELETE FROM navigation WHERE id = ?")->execute([$del_id]);
    log_activity($db, 'NAV', 'Nav Item Deleted', "ID: $del_id");
}

$nav_items = $db->query("SELECT * FROM navigation ORDER BY sort_order ASC")->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Manage Navigation</h2>
        <span class="badge bg-info text-dark shadow-sm"><i class="bi bi-info-circle me-1"></i> Drag rows to reorder</span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Label</label>
                    <input type="text" name="label" class="form-control" placeholder="About" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">URL</label>
                    <input type="text" name="url" class="form-control" placeholder="about.php" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">CSS Class</label>
                    <input type="text" name="css_class" class="form-control">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" name="save_nav" class="btn btn-primary">Add Link</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50"></th>
                        <th>Label</th>
                        <th>URL</th>
                        <th class="text-center">Order</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="sortable-nav">
                    <?php foreach ($nav_items as $item): ?>
                    <tr data-id="<?php echo $item['id']; ?>" style="cursor: grab;">
                        <td class="text-muted text-center"><i class="bi bi-grip-vertical fs-5"></i></td>
                        <td><span class="fw-bold"><?php echo htmlspecialchars($item['label']); ?></span></td>
                        <td><code><?php echo htmlspecialchars($item['url']); ?></code></td>
                        <td class="text-center sort-val"><?php echo $item['sort_order']; ?></td>
                        <td class="text-center">
                            <a href="index.php?view=navigation&delete_nav=<?php echo $item['id']; ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Remove link?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('sortable-nav');
    Sortable.create(el, {
        animation: 150,
        handle: '.bi-grip-vertical', // Only drag by the icon
        ghostClass: 'bg-light',
        onEnd: function() {
            // Get array of IDs in new order
            let order = [];
            el.querySelectorAll('tr').forEach(row => {
                order.push(row.dataset.id);
            });

            // Send to PHP via AJAX
            fetch('index.php?view=navigation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'update_order=1&' + order.map(id => 'order[]=' + id).join('&')
            })
            .then(response => response.text())
            .then(data => {
                // Optionally update the visible numbers in the table
                el.querySelectorAll('tr').forEach((row, index) => {
                    row.querySelector('.sort-val').innerText = index;
                });
            });
        }
    });
});
</script>