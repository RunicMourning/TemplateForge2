<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$msg = "";
$edit_post = null;
$current_user = $_SESSION['username'] ?? 'Admin';
$show_editor = isset($_GET['edit']) || isset($_GET['action']); 

// --- DELETE LOGIC ---
if (isset($_POST['delete_post'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_blog_delete')) {
        http_response_code(403);
        log_activity($db, 'SECURITY', 'CSRF Blocked', 'blog delete');
        die('Forbidden');
    }
    $delete_id = (int) ($_POST['delete_post'] ?? 0);
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$delete_id]);
    log_activity($db, 'CRUD', 'Post Deleted', "ID: " . $delete_id);
    $msg = "<div class='alert alert-warning border-0 shadow-sm rounded-4'>Post deleted successfully.</div>";
}

// --- SAVE/UPDATE LOGIC ---
if (isset($_POST['save_post'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'admin_blog_save')) {
        http_response_code(403);
        log_activity($db, 'SECURITY', 'CSRF Blocked', 'blog save');
        die('Forbidden');
    }
    $title    = $_POST['title'];
    $slug     = $_POST['slug'];
    $category = $_POST['category'];
    $content  = $_POST['content'];
    $excerpt  = substr(strip_tags($content), 0, 150) . '...';
    $id       = $_POST['post_id'] ?? null;

    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE posts SET title=?, slug=?, category=?, content=?, excerpt=?, author=? WHERE id=?");
            $stmt->execute([$title, $slug, $category, $content, $excerpt, $current_user, $id]);
            log_activity($db, 'CRUD', 'Post Updated', $title);
            $msg = '<div class="alert alert-success">Post updated!</div>';
        } else {
            $stmt = $db->prepare("INSERT INTO posts (title, slug, category, content, excerpt, author) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$title, $slug, $category, $content, $excerpt, $current_user]);
            log_activity($db, 'CRUD', 'Post Created', $title);
            $msg = '<div class="alert alert-success">Post published successfully!</div>';
        }
        $show_editor = false; 
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// --- FETCH EDIT DATA ---
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_post = $stmt->fetch();
}

// --- LISTING LOGIC ---
$search = $_GET['s'] ?? '';
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 8;
$offset = ($page_num - 1) * $limit;

$where_sql = $search ? "WHERE title LIKE :search OR content LIKE :search" : "";
$count_stmt = $db->prepare("SELECT COUNT(*) FROM posts $where_sql");
if ($search) $count_stmt->bindValue(':search', "%$search%");
$count_stmt->execute();
$total_posts = $count_stmt->fetchColumn();
$pages_total = ceil($total_posts / $limit);

$stmt = $db->prepare("SELECT * FROM posts $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
if ($search) $stmt->bindValue(':search', "%$search%");
$stmt->execute();
$all_posts = $stmt->fetchAll();
?>

<div class="">
    <div class="a-flex gap-2">
        <div>
            <h2 class="fw-bold">Blog Manager</h2>
            <p class="text-muted">Manage your articles and news updates</p>
        </div>
        <?php if (!$show_editor): ?>
            <a href="index.php?view=blog&action=new" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Create New Post
            </a>
        <?php else: ?>
            <a href="index.php?view=blog" class="btn btn-light border rounded-pill px-4">
                <i class="bi bi-arrow-left me-2"></i>Back to List
            </a>
        <?php endif; ?>
    </div>

    <?php echo $msg; ?>

    <?php if ($show_editor): ?>
        <div class="a-flex-between flex-wrap gap-2">
            <div class="col-lg-8">
                <div class="a-card">
                    <div class="a-card">
                        <form method="POST" action="index.php?view=blog" id="postForm">
                            <?php echo csrf_input('admin_blog_save'); ?>
                            <?php if($edit_post): ?>
                                <input type="hidden" name="post_id" value="<?php echo $edit_post['id']; ?>">
                            <?php endif; ?>

                            <div class="a-flex-between flex-wrap gap-2">
                                <div class="col-md-8">
                                    <label class="form-label">Post Title</label>
                                    <input type="text" name="title" class="" placeholder="Enter catchy title..." value="<?php echo $edit_post['title'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="">
                                        <option value="General" <?php echo ($edit_post['category'] ?? '') == 'General' ? 'selected' : ''; ?>>General</option>
                                        <option value="News" <?php echo ($edit_post['category'] ?? '') == 'News' ? 'selected' : ''; ?>>News</option>
                                        <option value="Tutorial" <?php echo ($edit_post['category'] ?? '') == 'Tutorial' ? 'selected' : ''; ?>>Tutorial</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label">URL Slug</label>
                                <div class="input-group">
                                    <span class="input-group">/blog-</span>
                                    <input type="text" name="slug" class="" placeholder="url-path" value="<?php echo $edit_post['slug'] ?? ''; ?>" required>
                                    <span class="input-group">.html</span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Content Editor</label>
<div class="a-card">
    <div class="a-card">
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
                                <textarea name="content" id="postEditor" rows="15" class="" oninput="updatePreview()"><?php echo $edit_post['content'] ?? ''; ?></textarea>
                            </div>

                            <div class="d-grid d-md-flex justify-content-md-end gap-2">
                                <button type="submit" name="save_post" class="btn btn-primary">Save & Publish Post</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card">
                        <h5 class="fw-bold">Live Preview</h5>
                    </div>
                    <div class="a-card">
                        <div id="preview" class="p-3 border rounded-3 bg-white" style="min-height: 150px;">
                            <?php echo $edit_post['content'] ?? ''; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <?php 
                    $context_id = 'blog_' . ($edit_post['id'] ?? 'new');
                    include 'includes/media_widget.php'; 
                ?>
            </div>
        </div>

        <div id="colorPickerOverlay" style="display: none; position: absolute; background-color: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 10px; z-index: 999; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <input type="color" id="colorPicker" class="" value="#000000">
            <button type="button" class="btn btn-outline btn-sm" onclick="applyTextColor()">Apply</button>
        </div>

    <?php else: ?>
        <div class="a-card">
            <div class="a-card">
                <form method="GET" class="a-flex-between flex-wrap gap-2">
                    <input type="hidden" name="view" value="blog">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="s" class="" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-light border-0 px-4">Filter</button>
                    </div>
                    <?php if($search): ?>
                        <div class="col-auto">
                            <a href="index.php?view=blog" class="text-muted">Clear Search</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="">
                <table class="">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-muted small text-uppercase py-3">Article</th>
                            <th class="text-muted">Category</th>
                            <th class="text-muted">Author</th>
                            <th class="text-muted">Date</th>
                            <th >Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($all_posts)): ?>
                            <tr><td colspan="5" class="text-center">No blog posts found.</td></tr>
                        <?php endif; ?>
                        <?php foreach($all_posts as $p): ?>
                        <tr class="hover-bg-light transition-all">
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($p['title']); ?></div>
                                <div class="text-muted">/blog-<?php echo $p['slug']; ?>.html</div>
                            </td>
                            <td><span class="badge"><?php echo $p['category'] ?? 'General'; ?></span></td>
                            <td>
                                <div class="a-flex gap-2">
                                    <div class="bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-2" style="width:24px; height:24px;">
                                        <i class="bi bi-person text-secondary" style="font-size: 0.8rem;"></i>
                                    </div>
                                    <span class="small"><?php echo htmlspecialchars($p['author']); ?></span>
                                </div>
                            </td>
                            <td><small class="text-muted"><?php echo date('M j, Y', strtotime($p['created_at'])); ?></small></td>
                            <td >
                                <a href="index.php?view=blog&edit=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                    <i class="bi bi-pencil text-primary"></i>
                                </a>
                                <form method="POST" action="index.php?view=blog" class="d-inline" onsubmit="return confirm('Permanent delete this post?')">
                                    <?php echo csrf_input('admin_blog_delete'); ?>
                                    <input type="hidden" name="delete_post" value="<?php echo (int) $p['id']; ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($pages_total > 1): ?>
            <div class="a-card">
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <?php for($i=1; $i<=$pages_total; $i++): ?>
                            <li class="page-item <?php echo ($i == $page_num) ? 'active' : ''; ?> mx-1">
                                <a class="page-link rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" href="index.php?view=blog&p=<?php echo $i; ?>&s=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
const textArea = document.getElementById("postEditor");
const preview = document.getElementById("preview");

function updatePreview() {
    preview.innerHTML = textArea.value;
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
    const url = prompt("Enter Image URL:", "https://");
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

// Color Picker Logic
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
    .pagination .page-link { border: none; color: #6c757d; font-weight: 500; }
    .pagination .page-item.active .page-link { background-color: #0d6efd; color: white; }
    .btn-white { background: #fff; }
    #preview { font-family: inherit; line-height: 1.6; }
    .btn-group .btn { border-color: #eee; }
</style>