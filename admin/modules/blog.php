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
    $msg = "<div class='alert alert-warning'>Post deleted successfully.</div>";
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
        // Index [[wiki links]] for appears-in tracking
        if (function_exists('wiki_index_links')) {
            wiki_index_links($db, $slug, 'post', $content);
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
                <i class="bi bi-plus-lg"></i> Create New Post
            </a>
        <?php else: ?>
            <a href="index.php?view=blog" class="btn btn-ghost btn-sm">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>

    <?php echo $msg; ?>


    <?php if ($show_editor): ?>
        <?php
        // Fetch categories from DB for the dropdown
        $blog_categories = [];
        try {
            $blog_categories = $db->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {}
        if (empty($blog_categories)) $blog_categories = ['General'];
        ?>
        <div class="editor-layout">

            <!-- Left: form + preview -->
            <div class="editor-main">
                <div class="a-card mb-3">
                    <div class="a-card-header">
                        <div class="a-card-title"><i class="bi bi-pencil" style="color:var(--a-accent);"></i> Post Details</div>
                    </div>
                    <div class="a-card-body">
                        <form method="POST" action="index.php?view=blog" id="postForm">
                            <?php echo csrf_input('admin_blog_save'); ?>
                            <?php if ($edit_post): ?>
                                <input type="hidden" name="post_id" value="<?php echo (int)$edit_post['id']; ?>">
                            <?php endif; ?>

                            <!-- Title + Category -->
                            <div class="field-row">
                                <div class="form-group mb-0">
                                    <label>Post Title</label>
                                    <input type="text" name="title" placeholder="Enter catchy title..." value="<?php echo htmlspecialchars($edit_post['title'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Category</label>
                                    <select name="category">
                                        <?php foreach ($blog_categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($edit_post['category'] ?? 'General') === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Slug -->
                            <div class="form-group">
                                <label>URL Slug</label>
                                <div class="slug-group">
                                    <span>/blog-</span>
                                    <input type="text" name="slug" placeholder="url-path" value="<?php echo htmlspecialchars($edit_post['slug'] ?? ''); ?>" required>
                                    <span>.html</span>
                                </div>
                            </div>

                            <!-- Content editor -->
                            <div class="form-group mb-0">
                                <label>Content Editor</label>
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
                                    <?php if (function_exists('wiki_autocomplete_response')): ?>
                                    <div style="position:relative;display:inline-block;">
                                        <button type="button" class="btn-tool" onclick="toggleWikiPicker()" title="Insert Wiki Link" style="color:var(--a-accent);"><i class="bi bi-journal-bookmark"></i></button>
                                        <div id="wikiPicker" style="display:none;position:absolute;top:calc(100% + 4px);left:0;z-index:200;background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius-lg);box-shadow:var(--a-shadow-lg);min-width:240px;">
                                            <div style="padding:0.5rem;"><input type="text" id="wikiSearch" placeholder="Search wiki entries…" style="width:100%;font-size:0.85rem;" oninput="wikiSearchFn(this.value)"></div>
                                            <div id="wikiResults" style="max-height:200px;overflow-y:auto;"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="tool-sep"></div>
                                    <button type="button" class="btn-tool" onclick="setAlignment('left')" title="Align Left"><i class="bi bi-text-left"></i></button>
                                    <button type="button" class="btn-tool" onclick="setAlignment('center')" title="Align Center"><i class="bi bi-text-center"></i></button>
                                    <button type="button" class="btn-tool" onclick="setAlignment('right')" title="Align Right"><i class="bi bi-text-right"></i></button>
                                </div>
                                <textarea name="content" id="postEditor" class="editor-textarea" oninput="updatePreview()"><?php echo htmlspecialchars($edit_post['content'] ?? ''); ?></textarea>
                            </div>

                            <div style="margin-top:1rem; text-align:right;">
                                <button type="submit" name="save_post" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Save &amp; Publish Post
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Live preview -->
                <div class="a-card">
                    <div class="a-card-header">
                        <div class="a-card-title"><i class="bi bi-eye" style="color:var(--a-accent);"></i> Live Preview</div>
                    </div>
                    <div class="a-card-body">
                        <div id="preview" class="editor-preview">
                            <?php echo $edit_post['content'] ?? '<span style="color:#aaa;">Editor content will appear here...</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: media widget -->
            <div class="editor-sidebar">
                <?php
                    $context_id = 'blog_' . ($edit_post['id'] ?? 'new');
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
            <div class="a-card">
                <form method="GET" class="a-flex-between flex-wrap gap-2">
                    <input type="hidden" name="view" value="blog">
                    <div style="flex:1;">
                        <div class="input-group">
                            <span class="input-group"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="s" class="" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div style="flex-shrink:0;">
                        <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
                    </div>
                    <?php if($search): ?>
                        <div style="flex-shrink:0;">
                            <a href="index.php?view=blog" class="text-muted">Clear Search</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="">
                <table class="">
                    <thead >
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
                        <tr class="">
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($p['title']); ?></div>
                                <div class="text-muted">/blog-<?php echo $p['slug']; ?>.html</div>
                            </td>
                            <td><span class="badge"><?php echo $p['category'] ?? 'General'; ?></span></td>
                            <td>
                                <div class="a-flex gap-2">
                                    <div style="width:24px;height:24px;background:var(--a-surface-2);border:1px solid var(--a-border);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-right:0.5rem;">
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
                    <ul class="pagination" style="justify-content:center;">
                        <?php for($i=1; $i<=$pages_total; $i++): ?>
                            <li class="page-item <?php echo ($i == $page_num) ? 'active' : ''; ?> mx-1">
                                <a class="" href="index.php?view=blog&p=<?php echo $i; ?>&s=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
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

// Wiki link picker
let wikiPickerOpen = false;
function toggleWikiPicker() {
    const p = document.getElementById('wikiPicker');
    if (!p) return;
    wikiPickerOpen = !wikiPickerOpen;
    p.style.display = wikiPickerOpen ? 'block' : 'none';
    if (wikiPickerOpen) { document.getElementById('wikiSearch').focus(); wikiSearchFn(''); }
}
let wikiTimer = null;
function wikiSearchFn(q) {
    clearTimeout(wikiTimer);
    wikiTimer = setTimeout(() => {
        fetch(`index.php?wiki_autocomplete=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(titles => {
                const box = document.getElementById('wikiResults');
                if (!box) return;
                if (!titles.length) { box.innerHTML = '<div style="padding:0.5rem 0.75rem;font-size:0.8rem;color:#aaa;">No entries found</div>'; return; }
                box.innerHTML = titles.map(t =>
                    `<div style="padding:0.45rem 0.75rem;font-size:0.85rem;cursor:pointer;border-bottom:1px solid var(--a-border);"
                          onmouseover="this.style.background='var(--a-surface-2)'" onmouseout="this.style.background=''"
                          onclick="insertWikiLink('${t.replace(/'/g,"\\'")}')">
                        <i class="bi bi-journal-bookmark" style="opacity:0.4;margin-right:0.4rem;"></i>${t}
                    </div>`
                ).join('');
            }).catch(() => {});
    }, 200);
}
function insertWikiLink(title) {
    const s = textArea.selectionStart;
    textArea.value = textArea.value.substring(0, s) + `[[${title}]]` + textArea.value.substring(textArea.selectionEnd);
    updatePreview(); toggleWikiPicker(); textArea.focus();
}
document.addEventListener('click', e => {
    if (wikiPickerOpen && !e.target.closest('#wikiPicker') && !e.target.closest('.btn-tool')) {
        wikiPickerOpen = false;
        const p = document.getElementById('wikiPicker');
        if (p) p.style.display = 'none';
    }
});
</script>

