<?php
$msg = "";

// 1. Handle Settings Update
if (isset($_POST['update_settings'])) {
    foreach ($_POST['config'] as $key => $value) {
        $stmt = $db->prepare("UPDATE settings SET value = ? WHERE key = ?");
        $stmt->execute([$value, $key]);
    }
    log_activity($db, 'SETTINGS', 'Configuration Updated', "Site settings modified");
    $msg = "<div class='alert alert-success border-0 shadow-sm'>Site settings updated!</div>";
}

// 2. Handle Category Addition
if (isset($_POST['add_category'])) {
    $cat = trim($_POST['new_category']);
    if (!empty($cat)) {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$cat]);
            log_activity($db, 'CRUD', 'Category Created', $cat);
            $msg = "<div class='alert alert-success border-0 shadow-sm'>Category <strong>$cat</strong> added!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger border-0 shadow-sm'>Category already exists.</div>";
        }
    }
}

// 3. Handle Category Deletion
if (isset($_GET['delete_cat'])) {
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['delete_cat']]);
    $msg = "<div class='alert alert-warning border-0 shadow-sm'>Category removed.</div>";
}

// Fetch Data
$res = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">System Settings</h2>
        <a href="index.php?view=users" class="btn btn-outline-dark btn-sm shadow-sm">
            <i class="bi bi-people me-2"></i>Manage Users
        </a>
    </div>
    
    <?php echo $msg; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="card-title mb-0">General Configuration</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Site Name</label>
                            <input type="text" name="config[site_name]" value="<?php echo htmlspecialchars($res['site_name'] ?? ''); ?>" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Footer Text</label>
                            <input type="text" name="config[footer_text]" value="<?php echo htmlspecialchars($res['footer_text'] ?? ''); ?>" class="form-control">
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" name="update_settings" class="btn btn-primary px-4">Save Configuration</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="card-title mb-0">Blog Categories</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="input-group mb-3">
                        <input type="text" name="new_category" class="form-control" placeholder="New category name..." required>
                        <button class="btn btn-success" type="submit" name="add_category">Add</button>
                    </form>

                    <ul class="list-group list-group-flush">
                        <?php foreach($categories as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><?php echo htmlspecialchars($c['name']); ?></span>
                            <a href="index.php?view=settings&delete_cat=<?php echo $c['id']; ?>" 
                               class="text-danger" 
                               onclick="return confirm('Remove this category?')">
                               <i class="bi bi-x-circle"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>