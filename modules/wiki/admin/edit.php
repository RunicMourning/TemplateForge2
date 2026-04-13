<?php
/**
 * Wiki Admin — Edit Form
 * Uses the same HTML editor as posts/pages.
 * Available: $db, $wiki_entry (null for new), $wiki_msg, $wiki_id
 */

$entry      = $wiki_entry ?? [];
$entry_id   = $entry['id'] ?? null;
$is_new     = !$entry_id;
$chapters   = wiki_get_chapters($db);
$images     = $entry_id ? wiki_get_images($db, $entry_id)  : [];
$links      = $entry_id ? wiki_get_links($db, $entry_id)   : [];

// Body: stored as HTML. Migrate old block JSON gracefully.
$body_html = $entry['body'] ?? '';
if (str_starts_with(trim($body_html), '[')) {
    $blocks = json_decode($body_html, true) ?? [];
    $parts  = [];
    foreach ($blocks as $b) {
        if ($b['type'] === 'paragraph') $parts[] = '<p>' . htmlspecialchars($b['content']) . '</p>';
        elseif ($b['type'] === 'heading') $parts[] = '<' . ($b['level'] ?? 'h2') . '>' . htmlspecialchars($b['content']) . '</' . ($b['level'] ?? 'h2') . '>';
        elseif ($b['type'] === 'divider') $parts[] = '<hr>';
    }
    $body_html = implode("\n", $parts);
}

$all_stmt = $db->prepare('SELECT id, title, entry_type FROM wiki_entries WHERE id != ? AND status = ? ORDER BY title ASC');
$all_stmt->execute([$entry_id ?? 0, 'published']);
$all_entries = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
$linked_ids  = array_column($links, 'linked_id');
$linkable    = array_filter($all_entries, fn($e) => !in_array($e['id'], $linked_ids));

$type_badge  = ['character'=>'badge-blue','place'=>'badge-green','faction'=>'badge-purple',
                'concept'=>'badge-yellow','creature'=>'badge-red','artifact'=>'badge-yellow','event'=>'badge-purple'];
$image_roles = ['cover'=>'Cover','portrait'=>'Portrait','map'=>'Map','inline'=>'Inline'];
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">
            <a href="index.php?view=wiki" style="color:var(--a-text-muted);font-weight:400;">Wiki</a>
            <span style="color:var(--a-border);margin:0 0.4rem;">/</span>
            <?= $is_new ? 'New Entry' : htmlspecialchars($entry['title']) ?>
        </div>
        <?php if (!$is_new): ?>
        <div class="page-subtitle">ID #<?= $entry_id ?> &middot; <?= ucfirst($entry['entry_type'] ?? '') ?></div>
        <?php endif; ?>
    </div>
    <a href="index.php?view=wiki" class="btn btn-outline"><i class="bi bi-arrow-left"></i> All Entries</a>
</div>

<?= $wiki_msg ?>

<div class="editor-layout">

    <div class="editor-main">
        <form method="POST" action="index.php?view=wiki" id="wikiForm">
            <?= csrf_input('wiki_entry_save') ?>
            <input type="hidden" name="wiki_entry_id" value="<?= $entry_id ?>">

            <div class="a-card mb-3">
                <div class="a-card-header"><div class="a-card-title"><i class="bi bi-info-circle"></i> Entry Details</div></div>
                <div class="a-card-body">
                    <div class="form-row mb-2">
                        <div class="form-group" style="flex:2;">
                            <label>Title</label>
                            <input type="text" name="title" id="wiki-title" value="<?= htmlspecialchars($entry['title'] ?? '') ?>" required placeholder="Entry title…">
                        </div>
                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" name="slug" id="wiki-slug" value="<?= htmlspecialchars($entry['slug'] ?? '') ?>" placeholder="auto-generated">
                            <div class="form-help">Leave blank to auto-generate.</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Entry Type</label>
                            <select name="entry_type">
                                <?php foreach (wiki_entry_types($db) as $t): ?>
                                <option value="<?= $t ?>" <?= ($entry['entry_type'] ?? 'concept') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="draft"     <?= ($entry['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= ($entry['status'] ?? '')      === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        <?php if (!empty($chapters)): ?>
                        <div class="form-group">
                            <label>Reveal Chapter</label>
                            <select name="reveal_chapter_id">
                                <option value="">Visible immediately</option>
                                <?php foreach ($chapters as $ch): ?>
                                <option value="<?= $ch['id'] ?>" <?= ($entry['reveal_chapter_id'] ?? '') == $ch['id'] ? 'selected' : '' ?>>
                                    Ep. <?= $ch['episode_number'] ?> — <?= htmlspecialchars($ch['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="a-card mb-3">
                <div class="a-card-header">
                    <div class="a-card-title"><i class="bi bi-pencil"></i> Body</div>
                </div>
                <div class="a-card-body" style="padding-bottom:0;">
                    <div class="editor-toolbar">
                        <button type="button" class="btn-tool" onclick="wrapText('strong')" title="Bold"><i class="bi bi-type-bold"></i></button>
                        <button type="button" class="btn-tool" onclick="wrapText('em')" title="Italic"><i class="bi bi-type-italic"></i></button>
                        <button type="button" class="btn-tool" onclick="wrapText('u')" title="Underline"><i class="bi bi-type-underline"></i></button>
                        <button type="button" class="btn-tool" onclick="wrapText('del')" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
                        <div class="tool-sep"></div>
                        <button type="button" class="btn-tool" onclick="insertSnippet('ul')" title="List"><i class="bi bi-list-ul"></i></button>
                        <button type="button" class="btn-tool" onclick="insertSnippet('ol')" title="Numbered List"><i class="bi bi-list-ol"></i></button>
                        <button type="button" class="btn-tool" onclick="wrapText('blockquote')" title="Quote"><i class="bi bi-quote"></i></button>
                        <div class="tool-sep"></div>
                        <button type="button" class="btn-tool" onclick="insertLink()" title="Insert Link"><i class="bi bi-link-45deg"></i></button>
                        <div class="tool-sep"></div>
                        <div style="position:relative;display:inline-block;">
                            <button type="button" class="btn-tool" onclick="toggleWikiPicker()" title="Insert Wiki Link" style="color:var(--a-accent);">
                                <i class="bi bi-journal-bookmark"></i>
                            </button>
                            <div id="wikiPicker" style="display:none;position:absolute;top:calc(100% + 4px);left:0;z-index:200;background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius-lg);box-shadow:var(--a-shadow-lg);min-width:240px;">
                                <div style="padding:0.5rem;">
                                    <input type="text" id="wikiSearch" placeholder="Search entries…" style="width:100%;font-size:0.85rem;" oninput="wikiSearch(this.value)">
                                </div>
                                <div id="wikiResults" style="max-height:200px;overflow-y:auto;"></div>
                            </div>
                        </div>
                    </div>
                    <textarea name="content" id="postEditor" class="editor-textarea" oninput="updatePreview()"><?= htmlspecialchars($body_html) ?></textarea>
                </div>
            </div>

            <div class="a-card mb-3">
                <div class="a-card-header"><div class="a-card-title"><i class="bi bi-eye"></i> Preview</div></div>
                <div class="a-card-body">
                    <div id="preview" class="editor-preview"><?= $body_html ?: '<span style="color:#aaa;">Preview will appear here…</span>' ?></div>
                </div>
            </div>

            <div class="a-flex-between mb-4">
                <a href="index.php?view=wiki" class="btn btn-ghost">Cancel</a>
                <button type="submit" name="wiki_save_entry" class="btn btn-primary">
                    <i class="bi bi-floppy"></i> <?= $is_new ? 'Create Entry' : 'Save Changes' ?>
                </button>
            </div>
        </form>
    </div>

    <div class="editor-sidebar">
        <?php if ($is_new): ?>
        <div class="a-card">
            <div class="a-card-body">
                <div class="empty-state" style="padding:1.5rem;">
                    <span class="empty-icon"><i class="bi bi-images"></i></span>
                    <p style="font-size:0.8rem;">Save the entry first to attach images and cross-links.</p>
                </div>
            </div>
        </div>
        <?php else: ?>

        <div class="a-card mb-3">
            <div class="a-card-header"><div class="a-card-title"><i class="bi bi-images"></i> Images</div></div>
            <div class="a-card-body">
                <?php if (!empty($images)): ?>
                <div class="wiki-image-grid mb-3">
                    <?php foreach ($images as $img): ?>
                    <div class="wiki-image-item">
                        <img src="<?= htmlspecialchars($img['url']) ?>" alt="<?= htmlspecialchars($img['original_name']) ?>">
                        <div class="wiki-image-meta">
                            <span class="badge badge-blue"><?= ucfirst($img['image_role']) ?></span>
                            <form method="POST" action="index.php?view=wiki" style="display:inline;">
                                <?= csrf_input('wiki_image_remove') ?>
                                <input type="hidden" name="wiki_entry_id" value="<?= $entry_id ?>">
                                <input type="hidden" name="wiki_image_id" value="<?= $img['id'] ?>">
                                <button type="submit" name="wiki_remove_image" class="btn btn-sm btn-ghost text-danger"
                                        onclick="return confirm('Remove this image?')"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <form method="POST" action="index.php?view=wiki" enctype="multipart/form-data">
                    <?= csrf_input('wiki_image_upload') ?>
                    <input type="hidden" name="wiki_entry_id" value="<?= $entry_id ?>">
                    <div class="form-group">
                        <input type="file" name="wiki_image" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="image_role">
                            <?php foreach ($image_roles as $val => $label): ?>
                            <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="wiki_upload_image" class="btn btn-outline w-full"><i class="bi bi-upload"></i> Attach Image</button>
                </form>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-header"><div class="a-card-title"><i class="bi bi-link-45deg"></i> Related Entries</div></div>
            <div class="a-card-body">
                <?php if (!empty($links)): ?>
                <div class="mb-3">
                    <?php foreach ($links as $lnk): ?>
                    <div class="wiki-link-row">
                        <span class="badge <?= $type_badge[$lnk['entry_type']] ?? '' ?>"><?= ucfirst($lnk['entry_type']) ?></span>
                        <span style="font-size:0.85rem;"><?= htmlspecialchars($lnk['title']) ?></span>
                        <form method="POST" action="index.php?view=wiki" style="margin-left:auto;">
                            <?= csrf_input('wiki_link') ?>
                            <input type="hidden" name="wiki_entry_id"  value="<?= $entry_id ?>">
                            <input type="hidden" name="link_target_id" value="<?= $lnk['linked_id'] ?>">
                            <button type="submit" name="wiki_remove_link" class="btn btn-sm btn-ghost text-danger"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($linkable)): ?>
                <form method="POST" action="index.php?view=wiki">
                    <?= csrf_input('wiki_link') ?>
                    <input type="hidden" name="wiki_entry_id" value="<?= $entry_id ?>">
                    <select name="link_target_id" class="mb-2 w-full">
                        <option value="">Select entry to link…</option>
                        <?php foreach ($linkable as $le): ?>
                        <option value="<?= $le['id'] ?>"><?= htmlspecialchars($le['title']) ?> (<?= $le['entry_type'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="wiki_add_link" class="btn btn-outline w-full"><i class="bi bi-plus-lg"></i> Add Link</button>
                </form>
                <?php elseif (empty($links)): ?>
                <p class="text-muted" style="font-size:0.85rem;">No other entries to link yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<div id="colorPickerOverlay" style="display:none;position:absolute;z-index:500;background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:0.5rem;box-shadow:var(--a-shadow-lg);">
    <input type="color" id="colorPicker" value="#000000" onchange="applyTextColor()">
</div>

<script>
const textArea = document.getElementById('postEditor');
const preview  = document.getElementById('preview');

function updatePreview() { if (preview) preview.innerHTML = textArea.value; }
function wrapText(tag) {
    const s = textArea.selectionStart, e = textArea.selectionEnd;
    const sel = textArea.value.substring(s, e);
    textArea.value = textArea.value.substring(0, s) + `<${tag}>${sel || 'Text'}</${tag}>` + textArea.value.substring(e);
    textArea.focus(); updatePreview();
}
function insertSnippet(type) {
    const s = textArea.selectionStart;
    const snip = type === 'ul' ? '\n<ul>\n  <li>Item 1</li>\n  <li>Item 2</li>\n</ul>\n'
                               : '\n<ol>\n  <li>Item 1</li>\n  <li>Item 2</li>\n</ol>\n';
    textArea.value = textArea.value.substring(0, s) + snip + textArea.value.substring(textArea.selectionEnd);
    updatePreview();
}
function insertLink() {
    const url = prompt('Enter URL:', 'https://');
    if (!url) return;
    const s = textArea.selectionStart, e = textArea.selectionEnd;
    const sel = textArea.value.substring(s, e);
    textArea.value = textArea.value.substring(0, s) + `<a href="${url}" target="_blank">${sel || 'Link Text'}</a>` + textArea.value.substring(e);
    updatePreview();
}
function showColorPicker(btn) {
    const el = document.getElementById('colorPickerOverlay');
    const r  = btn.getBoundingClientRect();
    el.style.display = 'block';
    el.style.top  = (r.bottom + window.scrollY + 5) + 'px';
    el.style.left = (r.left + window.scrollX) + 'px';
}
function applyTextColor() {
    const color = document.getElementById('colorPicker').value;
    const s = textArea.selectionStart, e = textArea.selectionEnd;
    const sel = textArea.value.substring(s, e);
    textArea.value = textArea.value.substring(0, s) + `<span style="color:${color};">${sel || 'Text'}</span>` + textArea.value.substring(e);
    document.getElementById('colorPickerOverlay').style.display = 'none';
    updatePreview();
}

// Wiki link picker
let wikiPickerOpen = false;
function toggleWikiPicker() {
    const p = document.getElementById('wikiPicker');
    wikiPickerOpen = !wikiPickerOpen;
    p.style.display = wikiPickerOpen ? 'block' : 'none';
    if (wikiPickerOpen) { document.getElementById('wikiSearch').focus(); wikiSearch(''); }
}
let wikiTimer = null;
function wikiSearch(q) {
    clearTimeout(wikiTimer);
    wikiTimer = setTimeout(() => {
        fetch(`index.php?wiki_autocomplete=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(titles => {
                const box = document.getElementById('wikiResults');
                if (!titles.length) { box.innerHTML = '<div style="padding:0.5rem 0.75rem;font-size:0.8rem;color:#aaa;">No entries found</div>'; return; }
                box.innerHTML = titles.map(t =>
                    `<div class="wiki-pick-item" onclick="insertWikiLink('${t.replace(/'/g,"\\'")}')">`
                    + `<i class="bi bi-journal-bookmark" style="opacity:0.4;margin-right:0.4rem;"></i>${t}</div>`
                ).join('');
            });
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
        document.getElementById('wikiPicker').style.display = 'none';
    }
});

// Slug auto-generation
const titleEl = document.getElementById('wiki-title');
const slugEl  = document.getElementById('wiki-slug');
if (titleEl && slugEl) {
    let manual = slugEl.value.length > 0;
    slugEl.addEventListener('input', () => { manual = true; });
    titleEl.addEventListener('input', () => {
        if (manual) return;
        slugEl.value = titleEl.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-|-$/g,'');
    });
}
</script>
<style>
.wiki-pick-item { padding:0.45rem 0.75rem;font-size:0.85rem;cursor:pointer;border-bottom:1px solid var(--a-border); }
.wiki-pick-item:last-child { border-bottom:none; }
.wiki-pick-item:hover { background:var(--a-surface-2); }
</style>
