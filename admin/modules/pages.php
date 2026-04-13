<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$msg = "";
$edit_page = null;
$show_editor = isset($_GET['edit']) || isset($_GET['action']); 

// 1. Save/Update Logic
if (isset($_POST['save_page'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_pages_save')) {
        http_response_code(403);
        log_activity($db, 'SECURITY', 'CSRF Blocked', 'pages save');
        die('Forbidden');
    }
    $title = $_POST['title'];
    $slug  = $_POST['slug'];
    $content = $_POST['content'];
    $id = $_POST['page_id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("UPDATE pages SET title = ?, slug = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $content, $id]);
        log_activity($db, 'CRUD', 'Page Edited', "Title: $title");
        $msg = "<div class='alert alert-success'>Page updated successfully!</div>";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (?, ?, ?)");
            $stmt->execute([$title, $slug, $content]);
            log_activity($db, 'CRUD', 'Page Created', "Title: $title");
            $msg = "<div class='alert alert-success'>New page published!</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'>Error: Slug must be unique.</div>";
        }
    }
    $show_editor = false; 
}

// 2. Delete Logic
if (isset($_POST['delete_page'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_pages_delete')) {
        http_response_code(403);
        log_activity($db, 'SECURITY', 'CSRF Blocked', 'pages delete');
        die('Forbidden');
    }
    $delete_id = (int) ($_POST['delete_page'] ?? 0);
    $db->prepare("DELETE FROM pages WHERE id = ? AND slug != 'home'")->execute([$delete_id]);
    log_activity($db, 'CRUD', 'Page Deleted', "ID: " . $delete_id);
    $msg = "<div class='alert alert-warning'>Page removed.</div>";
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

<div class="">
    <div class="a-flex gap-2">
        <div>
            <h2 class="fw-bold">Page Manager</h2>
            <p >Edit your website structure and core content</p>
        </div>
        <?php if (!$show_editor): ?>
            <a href="index.php?view=pages&action=new" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Create New Page
            </a>
        <?php else: ?>
            <a href="index.php?view=pages" class="btn btn-ghost btn-sm">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
    
    <?php echo $msg; ?>


    <?php if ($show_editor): ?>
        <div class="editor-layout">

            <!-- Left: form + preview -->
            <div class="editor-main">
                <div class="a-card mb-3">
                    <div class="a-card-header">
                        <div class="a-card-title"><i class="bi bi-file-earmark-text" style="color:var(--a-accent);"></i> Page Details</div>
                    </div>
                    <div class="a-card-body">
                        <form method="POST" action="index.php?view=pages">
                            <?php echo csrf_input('admin_pages_save'); ?>
                            <?php if ($edit_page): ?>
                                <input type="hidden" name="page_id" value="<?php echo (int)$edit_page['id']; ?>">
                            <?php endif; ?>

                            <!-- Title + Slug -->
                            <div class="field-row">
                                <div class="form-group mb-0">
                                    <label>Page Title</label>
                                    <input type="text" name="title" placeholder="e.g. About Us" value="<?php echo htmlspecialchars($edit_page['title'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Slug</label>
                                    <div class="slug-group">
                                        <input type="text" name="slug" placeholder="slug-name"
                                               value="<?php echo htmlspecialchars($edit_page['slug'] ?? ''); ?>"
                                               <?php echo ($edit_page && $edit_page['slug'] === 'home') ? 'readonly' : ''; ?>
                                               required>
                                        <span>.html</span>
                                    </div>
                                    <?php if ($edit_page && $edit_page['slug'] === 'home'): ?>
                                    <div class="form-help"><i class="bi bi-info-circle"></i> The homepage slug cannot be changed.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Content editor -->
                            <div class="form-group mb-0">
                                <label>Page Content</label>
                                <div class="editor-toolbar">
                                    <button type="button" class="btn-tool" onclick="wrapText('strong')" title="Bold"><i class="bi bi-type-bold"></i></button>
                                    <button type="button" class="btn-tool" onclick="wrapText('em')" title="Italic"><i class="bi bi-type-italic"></i></button>
                                    <button type="button" class="btn-tool" onclick="wrapText('u')" title="Underline"><i class="bi bi-type-underline"></i></button>
                                    <button type="button" class="btn-tool" onclick="wrapText('del')" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
                                    <div class="tool-sep"></div>
                                    <button type="button" class="btn-tool" onclick="insertSnippet('ul')" title="Unordered List"><i class="bi bi-list-ul"></i></button>
                                    <button type="button" class="btn-tool" onclick="insertSnippet('ol')" title="Ordered List"><i class="bi bi-list-ol"></i></button>
                                    <button type="button" class="btn-tool" onclick="wrapText('blockquote')" title="Quote"><i class="bi bi-quote"></i></button>
                                    <div class="tool-sep"></div>
                                    <button type="button" class="btn-tool" onclick="insertLink()" title="Insert Link"><i class="bi bi-link-45deg"></i></button>
                                    <button type="button" class="btn-tool btn-color-picker" onclick="showColorPicker(this)" title="Text Color"><i class="bi bi-palette"></i></button>
                                    <div class="tool-sep"></div>
                                    <button type="button" class="btn-tool" onclick="setAlignment('left')" title="Align Left"><i class="bi bi-text-left"></i></button>
                                    <button type="button" class="btn-tool" onclick="setAlignment('center')" title="Align Center"><i class="bi bi-text-center"></i></button>
                                    <button type="button" class="btn-tool" onclick="setAlignment('right')" title="Align Right"><i class="bi bi-text-right"></i></button>
                                </div>
                                <textarea name="content" id="postEditor" class="editor-textarea" oninput="updatePreview()"><?php echo htmlspecialchars($edit_page['content'] ?? ''); ?></textarea>
                            </div>

                            <div style="margin-top:1rem; text-align:right;">
                                <button type="submit" name="save_page" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Save Page Structure
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Page preview -->
                <div class="a-card">
                    <div class="a-card-header">
                        <div class="a-card-title"><i class="bi bi-eye" style="color:var(--a-accent);"></i> Page Preview</div>
                    </div>
                    <div class="a-card-body">
                        <div id="preview" class="editor-preview">
                            <?php echo $edit_page['content'] ?? '<span style="color:#aaa;">Editor content will appear here...</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: media widget -->
            <div class="editor-sidebar">
                <?php
                    $context_id = 'page_' . ($edit_page['id'] ?? 'new');
                    include 'includes/media_widget.php';
                ?>
            </div>

        </div>

        <!-- Color picker overlay -->
        <div id="colorPickerOverlay" class="color-picker-overlay">
            <input type="color" id="colorPicker" value="#000000">
            <button type="button" class="btn btn-ghost btn-sm" onclick="applyTextColor()">Apply</button>
        </div>

    <?php else: ?>
        <div class="a-card">
            <div class="">
                <table class="">
                    <thead >
                        <tr>
                            <th style="padding-left:1.5rem;">Page Title</th>
                            <th >Route / URL</th>
                            <th >Status</th>
                            <th >Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $p): ?>
                            <tr class="">
                                <td style="padding-left:1.5rem;">
                                    <div class="a-flex gap-2">
                                        <div style="width:35px;height:35px;background:var(--a-surface-2);border:1px solid var(--a-border);border-radius:var(--a-radius);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--a-accent);">
                                            <i class="bi <?php echo ($p['slug'] == 'home') ? 'bi-house-door' : 'bi-file-earmark-text'; ?>"></i>
                                        </div>
                                        <span class="fw-bold"><?php echo htmlspecialchars($p['title']); ?></span>
                                    </div>
                                </td>
                                <td><code class="text-secondary small">/<?php echo $p['slug']; ?>.html</code></td>
                                <td>
                                    <?php if($p['slug'] == 'home'): ?>
                                        <span class="badge">System Index</span>
                                    <?php else: ?>
                                        <span class="badge">Standard Page</span>
                                    <?php endif; ?>
                                </td>
                                <td >
                                    <a href="index.php?view=pages&edit=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                        <i class="bi bi-pencil text-primary"></i>
                                    </a>
                                    <?php if($p['slug'] !== 'home'): ?>
                                        <form method="POST" action="index.php?view=pages" class="d-inline" onsubmit="return confirm('Delete this page permanently?')">
                                            <?php echo csrf_input('admin_pages_delete'); ?>
                                            <input type="hidden" name="delete_page" value="<?php echo (int) $p['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm" disabled title="Locked">
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
        preview.innerHTML = textArea.value || '<p >Editor content will appear here...</p>';
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

