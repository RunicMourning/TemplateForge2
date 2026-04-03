<?php
/**
 * Shared Media Tray Widget
 * Expects $context_id (e.g., 'page_1' or 'blog_new') to track session state
 */

// 1. Session Context Management
$current_tray_context = $context_id ?? 'default';

if (!isset($_SESSION['active_media_context']) || $_SESSION['active_media_context'] !== $current_tray_context) {
    $_SESSION['media_tray'] = []; 
    $_SESSION['active_media_context'] = $current_tray_context;
}

// 2. Handle Upload
if (isset($_POST['ajax_upload_media'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_media_upload')) {
        die('<div class="alert alert-danger">Invalid request token.</div>');
    }
    if (!empty($_FILES['tray_file']['name'])) {
        $relative_dir = "uploads/" . date('Y/m') . "/";
        // Adjusted path to ensure it finds the root uploads folder
        $upload_dir = __DIR__ . "/../../" . $relative_dir;
        
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['tray_file']['name'], PATHINFO_EXTENSION));
        $new_filename = bin2hex(random_bytes(4)) . "_" . basename($_FILES['tray_file']['name']);
        
        if (move_uploaded_file($_FILES["tray_file"]["tmp_name"], $upload_dir . $new_filename)) {
            $_SESSION['media_tray'][] = [
                'url' => $relative_dir . $new_filename,
                'name' => basename($_FILES['tray_file']['name']),
                'isImg' => in_array($ext, ['jpg','jpeg','png','gif','webp'])
            ];
        }
    }
}
?>

<div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
    <div class="card-header bg-dark text-white py-3">
        <h6 class="mb-0 small"><i class="bi bi-images me-2"></i> Session Media</h6>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" class="mb-3">
            <?php echo csrf_input('admin_media_upload'); ?>
            <div class="input-group input-group-sm">
                <input type="file" name="tray_file" class="" required>
                <button class="btn btn-primary" type="submit" name="ajax_upload_media">Upload</button>
            </div>
        </form>

        <div class="media-list" style="max-height: 450px; overflow-y: auto;">
            <?php if (!empty($_SESSION['media_tray'])): ?>
                <?php foreach (array_reverse($_SESSION['media_tray']) as $item): ?>
                    <div class="p-2 border rounded bg-light mb-2 tray-item" 
                         onclick="insertMedia('<?php echo $item['url']; ?>', <?php echo $item['isImg'] ? 'true' : 'false'; ?>, this)">
                        <div class="d-flex align-items-center">
                            <?php if($item['isImg']): ?>
                                <img src="../<?php echo $item['url']; ?>" class="rounded border me-2" style="width:40px;height:40px;object-fit:cover;">
                            <?php else: ?>
                                <i class="bi bi-file-earmark-code fs-4 me-2"></i>
                            <?php endif; ?>
                            <div class="overflow-hidden">
                                <div class="text-truncate small fw-bold"><?php echo $item['name']; ?></div>
                                <small class="text-muted">Click to insert</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted border border-dashed rounded">
                    <i class="bi bi-cloud-upload fs-2"></i>
                    <p class="small mt-2">No session uploads.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * Inserts the media tag into the parent editor
 */
function insertMedia(path, isImg, el) {
    const editor = document.getElementById("postEditor");
    if (!editor) {
        navigator.clipboard.writeText(path);
        alert("Path copied (Editor not found)");
        return;
    }

    const fullPath = "../" + path;
    let tag = "";

    if (isImg) {
        // New: Ask for Alt Text
        const altText = prompt("Enter a brief description (Alt Text) for this image:", "image description");
        const finalAlt = altText ? altText.replace(/"/g, '&quot;') : "image";
        
        tag = `<img src="${fullPath}" alt="${finalAlt}" class="img-fluid rounded shadow-sm my-3">`;
    } else {
        tag = `<a href="${fullPath}" target="_blank" class="btn btn-outline">Download File</a>`;
    }

    // Insert at cursor position
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    editor.value = editor.value.substring(0, start) + tag + editor.value.substring(end);
    
    // Trigger preview update
    if (typeof updatePreview === 'function') {
        updatePreview();
    }

    // UI Feedback
    const status = el.querySelector('.status-text');
    const original = status.innerText;
    el.classList.add('bg-primary-subtle', 'border-primary');
    status.innerText = 'Inserted!';
    
    setTimeout(() => {
        el.classList.remove('bg-primary-subtle', 'border-primary');
        status.innerText = original;
    }, 1000);

    editor.focus();
}
</script>

<style>
.tray-item { transition: all 0.2s; }
.tray-item:hover { background-color: #fff !important; border-color: #0d6efd !important; cursor: pointer; transform: translateX(3px); }
.border-dashed { border-style: dashed !important; border-width: 2px !important; }
</style>