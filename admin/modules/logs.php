<?php
// 1. Handle "Prune Logs"
if (isset($_POST['prune_logs'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_logs_prune')) { http_response_code(403); die('Forbidden'); }
    $stmt = $db->prepare("DELETE FROM logs WHERE timestamp < datetime('now', '-7 days')");
    $stmt->execute();
    $count = $stmt->rowCount();
    log_activity($db, 'SYSTEM', 'Logs Pruned', "Cleaned up $count entries older than 7 days.");
    $success = "Database optimized! $count old log entries were removed.";
}

// 2. Filter Logic
$filter = $_GET['cat'] ?? '';
$query = "SELECT * FROM logs";
$params = [];

if ($filter) {
    $query .= " WHERE category = ?";
    $params[] = $filter;
}
$query .= " ORDER BY id DESC LIMIT 500";

$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique categories
$categories = $db->query("SELECT DISTINCT category FROM logs")->fetchAll(PDO::FETCH_COLUMN);

// Category Color Map
$colors = [
    'AUTH'        => ['bg' => '#27ae60', 'text' => '#fff'],
    'CRUD'        => ['bg' => '#3498db', 'text' => '#fff'],
    '404'         => ['bg' => '#f39c12', 'text' => '#fff'],
    'PHP Error'   => ['bg' => '#e74c3c', 'text' => '#fff'],
    'Fatal Error' => ['bg' => '#000', 'text' => '#ff0000'],
    'BLOG'        => ['bg' => '#9b59b6', 'text' => '#fff'],
    'SYSTEM'      => ['bg' => '#95a5a6', 'text' => '#fff'],
];
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>System Logs</h2>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete logs older than 7 days?');">
            <?php echo csrf_input('admin_logs_prune'); ?>
            <button type="submit" name="prune_logs" class="btn btn-warning">
                🧹 Prune Older Than 7 Days
            </button>
        </form>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card mb-3 p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="view" value="logs">
            <div class="col-auto">
                <label for="cat" class="col-form-label">Filter by Category:</label>
            </div>
            <div class="col-auto">
                <select name="cat" id="cat" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($filter == $cat) ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if($filter): ?>
                <div class="col-auto">
                    <a href="index.php?view=logs" class="link-secondary small">Clear Filter</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="card overflow-auto">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Timestamp</th>
                    <th>Category</th>
                    <th>Event</th>
                    <th>User/IP</th>
                    <th>Priority</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l):
                        $style = $colors[$l['category']] ?? ['bg' => '#eee', 'text' => '#333'];
                    ?>
                        <?php $is_high_priority = is_high_priority_log((string)$l['category'], (string)$l['event']); ?>
                        <tr class="<?php echo $is_high_priority ? 'table-danger' : ''; ?>">
                            <td class="text-nowrap text-secondary">
                                [<?php echo date('Y-m-d H:i:s', strtotime($l['timestamp'])); ?>]
                            </td>
                            <td>
                                <span class="badge" style="background: <?php echo $style['bg']; ?>; color: <?php echo $style['text']; ?>;">
                                    <?php echo strtoupper($l['category']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($l['event']); ?></strong></td>
                            <td>
                                <small><strong><?php echo htmlspecialchars((string)$l['user']); ?></strong><br><?php echo htmlspecialchars((string)$l['ip']); ?></small>
                            </td>
                            <td>
                                <?php if ($is_high_priority): ?>
                                    <span class="badge rounded-pill text-bg-danger">High</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill text-bg-secondary">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: 'Consolas', monospace; font-size: 0.85rem; word-wrap: break-word;">
                                <?php echo htmlspecialchars($l['details']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
