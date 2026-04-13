<?php
/**
 * Shared Media Tray Widget
 * Layer B — expects $context_id (e.g. 'blog_1' or 'page_new')
 */

$current_tray_context = $context_id ?? 'default';

if (!isset($_SESSION['active_media_context']) || $_SESSION['active_media_context'] !== $current_tray_context) {
    $_SESSION['media_tray'] = [];
    $_SESSION['active_media_context'] = $current_tray_context;
}

if (isset($_POST['ajax_upload_media'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_media_upload')) {
        die('<div class="alert alert-danger">Invalid request token.</div>');
    }
    if (!empty($_FILES['tray_file']['name'])) {
        $relative_dir = 'uploads/' . date('Y/m') . '/';
        $upload_dir   = __DIR__ . '/../../' . $relative_dir;
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext          = strtolower(pathinfo($_FILES['tray_file']['name'], PATHINFO_EXTENSION));
        $new_filename = bin2hex(random_bytes(4)) . '_' . basename($_FILES['tray_file']['name']);
        if (move_uploaded_file($_FILES['tray_file']['tmp_name'], $upload_dir . $new_filename)) {
            $_SESSION['media_tray'][] = [
                'url'   => $relative_dir . $new_filename,
                'name'  => basename($_FILES['tray_file']['name']),
                'isImg' => in_array($ext, ['jpg','jpeg','png','gif','webp']),
            ];
        }
    }
}
?>

<div class="a-card">
    <div class="a-card-header">
        <div class="a-card-title">
            <i class="bi bi-images" style="color:var(--a-accent);"></i> Session Media
        </div>
    </div>
    <div class="a-card-body">

        <!-- Upload form -->
        <form method="POST" enctype="multipart/form-data" class="media-upload-form">
            <?php echo csrf_input('admin_media_upload'); ?>
            <input type="file" name="tray_file" required style="flex:1; min-width:0;">
            <button class="btn btn-primary btn-sm" type="submit" name="ajax_upload_media">
                <i class="bi bi-upload"></i> Upload
            </button>
        </form>

        <!-- Media list -->
        <div class="media-tray">
            <?php if (!empty($_SESSION['media_tray'])): ?>
                <?php foreach (array_reverse($_SESSION['media_tray']) as $item): ?>
                <div class="tray-item"
                     onclick="insertMedia('<?php echo htmlspecialchars($item['url']); ?>', <?php echo $item['isImg'] ? 'true' : 'false'; ?>, this)">
                    <?php if ($item['isImg']): ?>
                        <img src="../<?php echo htmlspecialchars($item['url']); ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             style="width:40px;height:40px;object-fit:cover;border-radius:var(--a-radius);border:1px solid var(--a-border);flex-shrink:0;">
                    <?php else: ?>
                        <div style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--a-surface-2);border:1px solid var(--a-border);border-radius:var(--a-radius);flex-shrink:0;">
                            <i class="bi bi-file-earmark-code" style="font-size:1.2rem;color:var(--a-accent);"></i>
                        </div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div class="tray-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="tray-item-hint status-text">Click to insert</div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="media-empty">
                    <i class="bi bi-cloud-upload"></i>
                    <p>No session uploads.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function insertMedia(path, isImg, el) {
    var editor = document.getElementById('postEditor');
    if (!editor) {
        navigator.clipboard.writeText(path);
        alert('Path copied (editor not found)');
        return;
    }
    var fullPath = '../' + path;
    var tag = '';
    if (isImg) {
        var alt = prompt('Enter alt text for this image:', 'image description');
        var finalAlt = alt ? alt.replace(/"/g, '&quot;') : 'image';
        tag = '<img src="' + fullPath + '" alt="' + finalAlt + '" style="max-width:100%;height:auto;border-radius:4px;margin:0.5rem 0;">';
    } else {
        tag = '<a href="' + fullPath + '" target="_blank" class="btn btn-outline">Download File</a>';
    }
    var start = editor.selectionStart;
    var end   = editor.selectionEnd;
    editor.value = editor.value.substring(0, start) + tag + editor.value.substring(end);
    if (typeof updatePreview === 'function') updatePreview();

    var hint = el.querySelector('.status-text');
    var orig = hint ? hint.innerText : '';
    el.classList.add('tray-item--active');
    if (hint) hint.innerText = 'Inserted!';
    setTimeout(function() {
        el.classList.remove('tray-item--active');
        if (hint) hint.innerText = orig;
    }, 1000);
    editor.focus();
}
</script>
