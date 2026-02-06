<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$msg = "";
$edit_page = null;
$show_editor = isset($_GET['edit']) || isset($_GET['action']); 

// 1. Save/Update Logic
if (isset($_POST['save_page'])) {
    $title = $_POST['title'];
    $slug  = $_POST['slug'];
    $content = $_POST['content'];
    $id = $_POST['page_id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("UPDATE pages SET title = ?, slug = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $content, $id]);
        log_activity($db, 'CRUD', 'Page Edited', "Title: $title");
        $msg = "<div class='alert alert-success border-0 shadow-sm rounded-4'>Page updated successfully!</div>";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (?, ?, ?)");
            $stmt->execute([$title, $slug, $content]);
            log_activity($db, 'CRUD', 'Page Created', "Title: $title");
            $msg = "<div class='alert alert-success border-0 shadow-sm rounded-4'>New page published!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger border-0 shadow-sm rounded-4'>Error: Slug must be unique.</div>";
        }
    }
    $show_editor = false; 
}

// 2. Delete Logic
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM pages WHERE id = ? AND slug != 'home'")->execute([$_GET['delete']]);
    log_activity($db, 'CRUD', 'Page Deleted', "ID: " . $_GET['delete']);
    $msg = "<div class='alert alert-warning border-0 shadow-sm rounded-4'>Page removed.</div>";
}

// 3. Fetch Edit Data
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_page = $stmt->fetch();
}

// 4. Fetch All Pages for List
$pages = $db->query("SELECT * FROM pages ORDER BY slug='home' DESC, id DESC")->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Page Manager</h2>
            <p class="text-muted small mb-0">Edit your website structure and core content</p>
        </div>
        <?php if (!$show_editor): ?>
            <a href="index.php?view=pages&action=new" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i>Create New Page
            </a>
        <?php else: ?>
            <a href="index.php?view=pages" class="btn btn-light border rounded-pill px-4">
                <i class="bi bi-arrow-left me-2"></i>Back to List
            </a>
        <?php endif; ?>
    </div>
    
    <?php echo $msg; ?>

    <?php if ($show_editor): ?>
        <div class="row g-4 animate__animated animate__fadeIn">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <form method="POST" action="index.php?view=pages">
                            <?php if ($edit_page): ?>
                                <input type="hidden" name="page_id" value="<?php echo $edit_page['id']; ?>">
                            <?php endif; ?>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Page Title</label>
                                    <input type="text" name="title" class="form-control form-control-lg border-0 bg-light rounded-3" placeholder="e.g. About Us" value="<?php echo $edit_page['title'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Slug</label>
                                    <div class="input-group input-group-lg">
                                        <input type="text" name="slug" class="form-control border-0 bg-light rounded-3" placeholder="slug-name" value="<?php echo $edit_page['slug'] ?? ''; ?>" <?php echo ($edit_page && $edit_page['slug'] == 'home') ? 'readonly' : ''; ?> required>
                                        <span class="input-group-text border-0 bg-light text-muted small">.html</span>
                                    </div>
                                    <?php if ($edit_page && $edit_page['slug'] == 'home'): ?>
                                        <div class="x-small text-info mt-1"><i class="bi bi-info-circle me-1"></i> The homepage slug cannot be changed.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Page Content</label>
<div class="card border-0 bg-white shadow-sm mb-2">
    <div class="card-body p-2 d-flex flex-wrap gap-1">
        <button type="button" class="btn btn-light btn-sm" onclick="wrapText('strong')" title="Bold"><i class="bi bi-type-bold"></i></button>
        <button type="button" class="btn btn-light btn-sm" onclick="wrapText('em')" title="Italic"><i class="bi bi-type-italic"></i></button>
        <button type="button" class="btn btn-light btn-sm" onclick="wrapText('u')" title="Underline"><i class="bi bi-type-underline"></i></button>
        <button type="button" class="btn btn-light btn-sm" onclick="wrapText('del')" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
        
        <div class="vr mx-1"></div>
        
        <button type="button" class="btn btn-light btn-sm" onclick="insertSnippet('ul')" title="Unordered List"><i class="bi bi-list-ul"></i></button>
        <button type="button" class="btn btn-light btn-sm" onclick="insertSnippet('ol')" title="Ordered List"><i class="bi bi-list-ol"></i></button>
        <button type="button" class="btn btn-light btn-sm" onclick="wrapText('blockquote')" title="Quote"><i class="bi bi-quote"></i></button>
        
        <div class="vr mx-1"></div>
        
        <button type="button" class="btn btn-light btn-sm" onclick="insertLink()" title="Insert Link"><i class="bi bi-link-45deg"></i></button>
        <button type="button" class="btn btn-light btn-sm btn-color-picker" onclick="showColorPicker(this)" title="Text Color"><i class="bi bi-palette"></i></button>
        
        <div class="vr mx-1"></div>
        
        <button type="button" class="btn btn-light btn-sm" onclick="setAlignment('left')" title="Align Left"><i class="bi bi-text-left"></i></button>
        <button type="button" class="btn btn-light btn-sm" onclick="setAlignment('center')" title="Align Center"><i class="bi bi-text-center"></i></button>
        <button type="button" class="btn btn-light btn-sm" onclick="setAlignment('right')" title="Align Right"><i class="bi bi-text-right"></i></button>
    </div>
</div>
                                    <textarea name="content" id="postEditor" rows="18" class="form-control border-0 bg-light rounded-3 font-monospace" oninput="updatePreview()"><?php echo $edit_page['content'] ?? ''; ?></textarea>
                                </div>

                                <div class="col-12 text-end mt-4">
                                    <button type="submit" name="save_page" class="btn btn-primary px-5 rounded-pill shadow">Save Page Structure</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mt-4 mb-5">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0 text-muted text-uppercase small">Page Preview</h5>
                    </div>
                    <div class="card-body p-4">
                        <div id="preview" class="p-4 border rounded-3 bg-white" style="min-height: 200px; color: #333;">
                            <?php echo $edit_page['content'] ?? '<p class="text-muted">Editor content will appear here...</p>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <?php 
                    $context_id = 'page_' . ($edit_page['id'] ?? 'new'); 
                    include 'includes/media_widget.php'; 
                ?>
            </div>
        </div>

        <div id="colorPickerOverlay" style="display: none; position: absolute; background-color: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 10px; z-index: 999; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <input type="color" id="colorPicker" class="form-control form-control-color mb-2" value="#000000">
            <button type="button" class="btn btn-sm btn-dark w-100" onclick="applyTextColor()">Apply</button>
        </div>

    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-muted small text-uppercase py-3">Page Title</th>
                            <th class="text-muted small text-uppercase py-3">Route / URL</th>
                            <th class="text-muted small text-uppercase py-3">Status</th>
                            <th class="text-end pe-4 text-muted small text-uppercase py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $p): ?>
                            <tr class="hover-bg-light transition-all">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-shape bg-primary-subtle text-primary rounded-3 me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="bi <?php echo ($p['slug'] == 'home') ? 'bi-house-door' : 'bi-file-earmark-text'; ?>"></i>
                                        </div>
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($p['title']); ?></span>
                                    </div>
                                </td>
                                <td><code class="text-secondary small">/<?php echo $p['slug']; ?>.html</code></td>
                                <td>
                                    <?php if($p['slug'] == 'home'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">System Index</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill">Standard Page</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="index.php?view=pages&edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-white border-0 shadow-sm rounded-3 me-1" title="Edit">
                                        <i class="bi bi-pencil text-primary"></i>
                                    </a>
                                    <?php if($p['slug'] !== 'home'): ?>
                                        <a href="index.php?view=pages&delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-white border-0 shadow-sm rounded-3 text-danger" onclick="return confirm('Delete this page permanently?')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-white border-0 shadow-sm rounded-3 opacity-50" disabled title="Locked">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const textArea = document.getElementById("postEditor");
const preview = document.getElementById("preview");

function updatePreview() {
    if (textArea && preview) {
        preview.innerHTML = textArea.value || '<p class="text-muted">Editor content will appear here...</p>';
    }
}

function wrapText(tag) {
    const start = textArea.selectionStart;
    const end = textArea.selectionEnd;
    const selectedText = textArea.value.substring(start, end);
    const replacement = `<${tag}>${selectedText || (tag === 'blockquote' ? 'Quoted Text' : 'Text')}</${tag}>`;
    
    textArea.value = textArea.value.substring(0, start) + replacement + textArea.value.substring(end);
    textArea.focus();
    updatePreview();
}

function insertSnippet(type) {
    const start = textArea.selectionStart;
    let snippet = "";
    if(type === 'ul') snippet = "\n<ul>\n  <li>Item 1</li>\n  <li>Item 2</li>\n</ul>\n";
    if(type === 'ol') snippet = "\n<ol>\n  <li>Item 1</li>\n  <li>Item 2</li>\n</ol>\n";
    
    textArea.value = textArea.value.substring(0, start) + snippet + textArea.value.substring(textArea.selectionEnd);
    updatePreview();
}

function insertLink() {
    const url = prompt("Enter URL:", "https://");
    if(url) {
        const start = textArea.selectionStart;
        const end = textArea.selectionEnd;
        const selectedText = textArea.value.substring(start, end);
        const link = `<a href="${url}" target="_blank">${selectedText || 'Link Text'}</a>`;
        textArea.value = textArea.value.substring(0, start) + link + textArea.value.substring(end);
        updatePreview();
    }
}

function setAlignment(dir) {
    const start = textArea.selectionStart;
    const end = textArea.selectionEnd;
    const selectedText = textArea.value.substring(start, end);
    const div = `<div style="text-align: ${dir};">${selectedText || 'Aligned text'}</div>`;
    textArea.value = textArea.value.substring(0, start) + div + textArea.value.substring(end);
    updatePreview();
}

function showColorPicker(btn) {
    const overlay = document.getElementById("colorPickerOverlay");
    const rect = btn.getBoundingClientRect();
    overlay.style.display = "block";
    overlay.style.top = (rect.bottom + window.scrollY + 5) + "px";
    overlay.style.left = (rect.left + window.scrollX) + "px";
}

function applyTextColor() {
    const color = document.getElementById("colorPicker").value;
    const start = textArea.selectionStart;
    const end = textArea.selectionEnd;
    const selectedText = textArea.value.substring(start, end);
    const colored = `<span style="color: ${color};">${selectedText || 'Colored Text'}</span>`;
    
    textArea.value = textArea.value.substring(0, start) + colored + textArea.value.substring(end);
    document.getElementById("colorPickerOverlay").style.display = "none";
    updatePreview();
}
</script>

<style>
    .x-small { font-size: 0.72rem; }
    .transition-all { transition: all 0.2s ease-in-out; }
    .hover-bg-light:hover { background-color: #fbfbfb; }
    .btn-white { background: #fff; }
    .rounded-4 { border-radius: 1rem !important; }
    .icon-shape { flex-shrink: 0; }
    #preview img { max-width: 100%; height: auto; border-radius: 8px; }
    #preview blockquote { border-left: 4px solid #dee2e6; padding-left: 1rem; color: #6c757d; }
</style>