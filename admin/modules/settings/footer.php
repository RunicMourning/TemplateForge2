<?php
/**
 * Settings > Footer
 * Manages social_links and footer_links tables.
 * Layer B (Application) — reads/writes D (Infrastructure) via PDO.
 */

$msg = '';

// ── Social Links: reorder (AJAX) ─────────────────────────────────
if (isset($_POST['reorder_social'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'footer_social_order')) { http_response_code(403); die('Forbidden'); }
    foreach (($_POST['order'] ?? []) as $i => $id) {
        $db->prepare("UPDATE social_links SET sort_order = ? WHERE id = ?")->execute([$i, (int)$id]);
    }
    log_activity($db, 'FOOTER', 'Social Links Reordered', '');
    exit('ok');
}

// ── Social Links: add ────────────────────────────────────────────
if (isset($_POST['add_social'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'footer_social_add')) { http_response_code(403); die('Forbidden'); }
    $platform = trim($_POST['platform'] ?? '');
    $value    = trim($_POST['value'] ?? '');
    $platforms = tf_social_platforms();
    if ($platform && $value && isset($platforms[$platform])) {
        $max = $db->query("SELECT COALESCE(MAX(sort_order),0) FROM social_links")->fetchColumn();
        $db->prepare("INSERT INTO social_links (platform, value, sort_order) VALUES (?,?,?)")
           ->execute([$platform, $value, (int)$max + 1]);
        log_activity($db, 'FOOTER', 'Social Link Added', $platform);
        $msg = "<div class='alert alert-success'><i class='bi bi-check-lg'></i> Social link added.</div>";
    }
}

// ── Social Links: delete ─────────────────────────────────────────
if (isset($_POST['delete_social'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'footer_social_delete')) { http_response_code(403); die('Forbidden'); }
    $db->prepare("DELETE FROM social_links WHERE id = ?")->execute([(int)$_POST['delete_social']]);
    log_activity($db, 'FOOTER', 'Social Link Deleted', '');
    $msg = "<div class='alert alert-warning'><i class='bi bi-trash'></i> Social link removed.</div>";
}

// ── Footer Links: reorder (AJAX) ─────────────────────────────────
if (isset($_POST['reorder_footer'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'footer_links_order')) { http_response_code(403); die('Forbidden'); }
    foreach (($_POST['order'] ?? []) as $i => $id) {
        $db->prepare("UPDATE footer_links SET sort_order = ? WHERE id = ?")->execute([$i, (int)$id]);
    }
    log_activity($db, 'FOOTER', 'Footer Links Reordered', '');
    exit('ok');
}

// ── Footer Links: add ────────────────────────────────────────────
if (isset($_POST['add_footer_link'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'footer_links_add')) { http_response_code(403); die('Forbidden'); }
    $label = trim($_POST['link_label'] ?? '');
    $url   = trim($_POST['link_url'] ?? '');
    if ($label && $url) {
        $max = $db->query("SELECT COALESCE(MAX(sort_order),0) FROM footer_links")->fetchColumn();
        $db->prepare("INSERT INTO footer_links (label, url, sort_order) VALUES (?,?,?)")
           ->execute([$label, $url, (int)$max + 1]);
        log_activity($db, 'FOOTER', 'Footer Link Added', $label);
        $msg = "<div class='alert alert-success'><i class='bi bi-check-lg'></i> Footer link added.</div>";
    }
}

// ── Footer Links: delete ─────────────────────────────────────────
if (isset($_POST['delete_footer_link'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'footer_links_delete')) { http_response_code(403); die('Forbidden'); }
    $db->prepare("DELETE FROM footer_links WHERE id = ?")->execute([(int)$_POST['delete_footer_link']]);
    log_activity($db, 'FOOTER', 'Footer Link Deleted', '');
    $msg = "<div class='alert alert-warning'><i class='bi bi-trash'></i> Footer link removed.</div>";
}

// ── Fetch current data ───────────────────────────────────────────
try {
    $social_links = $db->query("SELECT * FROM social_links ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $social_links = [];
    $msg = "<div class='alert alert-danger'>social_links table not found. Please re-run the installer or add it manually.</div>";
}
try {
    $footer_links = $db->query("SELECT * FROM footer_links ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $footer_links = [];
}

$platforms = tf_social_platforms();
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">Footer</div>
        <div class="page-subtitle">Manage social media links and footer navigation</div>
    </div>
</div>

<?php echo $msg; ?>

<!-- ── Social Media Links ────────────────────────────────────────── -->
<div class="a-card mb-3">
    <div class="a-card-header">
        <div class="a-card-title">
            <i class="bi bi-share" style="color:var(--a-accent);"></i> Social Media Links
        </div>
    </div>
    <div class="a-card-body">

        <!-- Add form -->
        <form method="POST" class="social-add-form mb-3">
            <?php echo csrf_input('footer_social_add'); ?>
            <div class="social-add-row">

                <!-- Platform dropdown -->
                <div class="social-platform-select">
                    <select name="platform" id="platformSelect" required>
                        <option value="">Select platform&hellip;</option>
                        <?php foreach ($platforms as $slug => $p): ?>
                        <option value="<?php echo htmlspecialchars($slug); ?>"
                                data-icon="<?php echo htmlspecialchars($p['icon']); ?>"
                                data-prefix="<?php echo htmlspecialchars($p['url_prefix']); ?>">
                            <?php echo htmlspecialchars($p['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Live icon preview -->
                    <div class="platform-icon-preview" id="platformIconPreview">
                        <i class="bi bi-share" id="platformIcon"></i>
                    </div>
                </div>

                <!-- Username / URL input -->
                <div style="flex:1; min-width:0;">
                    <input type="text" name="value" id="platformValue"
                           placeholder="Username or full URL"
                           required style="width:100%;">
                    <div class="form-help" id="platformHint" style="margin-top:0.3rem;"></div>
                </div>

                <button type="submit" name="add_social" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
        </form>

        <!-- Social links list -->
        <?php if (empty($social_links)): ?>
        <p class="text-muted text-small">No social links yet. Add one above.</p>
        <?php else: ?>
        <div class="drag-hint"><i class="bi bi-grip-vertical"></i> Drag to reorder</div>
        <ul class="footer-admin-list" id="socialSortable">
            <?php foreach ($social_links as $link):
                $p = $platforms[$link['platform']] ?? ['label' => $link['platform'], 'icon' => 'bi-link', 'url_prefix' => ''];
            ?>
            <li class="footer-admin-item" data-id="<?php echo (int)$link['id']; ?>">
                <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>

                <!-- Platform icon -->
                <div class="social-item-icon">
                    <i class="bi <?php echo htmlspecialchars($p['icon']); ?>"></i>
                </div>

                <!-- Label + value -->
                <div class="footer-admin-item-info">
                    <span class="footer-admin-item-label"><?php echo htmlspecialchars($p['label']); ?></span>
                    <span class="footer-admin-item-url"><?php echo htmlspecialchars($link['value']); ?></span>
                </div>

                <!-- Delete -->
                <form method="POST" onsubmit="return confirm('Remove this link?')" style="margin:0;">
                    <?php echo csrf_input('footer_social_delete'); ?>
                    <input type="hidden" name="delete_social" value="<?php echo (int)$link['id']; ?>">
                    <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-trash"></i></button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ── Footer Navigation Links ──────────────────────────────────── -->
<div class="a-card">
    <div class="a-card-header">
        <div class="a-card-title">
            <i class="bi bi-link-45deg" style="color:var(--a-accent);"></i> Footer Links
        </div>
    </div>
    <div class="a-card-body">

        <!-- Add form -->
        <form method="POST" class="mb-3">
            <?php echo csrf_input('footer_links_add'); ?>
            <div class="social-add-row">
                <input type="text" name="link_label" placeholder="Label (e.g. Privacy Policy)" required style="flex:1;">
                <input type="text" name="link_url"   placeholder="URL (e.g. privacy.html)"    required style="flex:1;">
                <button type="submit" name="add_footer_link" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
        </form>

        <!-- Footer links list -->
        <?php if (empty($footer_links)): ?>
        <p class="text-muted text-small">No footer links yet. Add one above.</p>
        <?php else: ?>
        <div class="drag-hint"><i class="bi bi-grip-vertical"></i> Drag to reorder</div>
        <ul class="footer-admin-list" id="footerLinksSortable">
            <?php foreach ($footer_links as $link): ?>
            <li class="footer-admin-item" data-id="<?php echo (int)$link['id']; ?>">
                <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="footer-admin-item-info">
                    <span class="footer-admin-item-label"><?php echo htmlspecialchars($link['label']); ?></span>
                    <span class="footer-admin-item-url"><?php echo htmlspecialchars($link['url']); ?></span>
                </div>
                <form method="POST" onsubmit="return confirm('Remove this link?')" style="margin:0;">
                    <?php echo csrf_input('footer_links_delete'); ?>
                    <input type="hidden" name="delete_footer_link" value="<?php echo (int)$link['id']; ?>">
                    <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-trash"></i></button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<style>
/* ── Add form row ───────────────────────────────────────────────── */
.social-add-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* ── Platform select with icon preview ──────────────────────────── */
.social-platform-select {
    display: flex;
    align-items: center;
    gap: 0;
    border: 1px solid var(--a-border);
    border-radius: var(--a-radius, 6px);
    overflow: hidden;
    background: var(--a-surface);
    flex-shrink: 0;
}
.social-platform-select select {
    border: none;
    border-radius: 0;
    background: transparent;
    width: 185px;
    padding-right: 0.5rem;
    outline: none;
    box-shadow: none;
}
.social-platform-select select:focus {
    box-shadow: none;
    border: none;
}
.platform-icon-preview {
    width: 42px;
    height: 38px;
    background: var(--a-surface-2, #f0f0f0);
    border-left: 1px solid var(--a-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--a-accent);
    flex-shrink: 0;
    transition: color 0.15s;
}

/* ── Sortable list ──────────────────────────────────────────────── */
.footer-admin-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.footer-admin-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: var(--a-surface, #fff);
    border: 1px solid var(--a-border);
    border-radius: var(--a-radius, 6px);
    padding: 0.6rem 0.75rem;
    transition: background 0.12s, box-shadow 0.12s;
}
.footer-admin-item:hover {
    background: var(--a-hover-bg, rgba(0,0,0,0.02));
}
.footer-admin-item.sortable-ghost {
    opacity: 0.4;
    background: var(--a-surface-2);
}
.footer-admin-item.sortable-chosen {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.drag-handle {
    cursor: grab;
    color: var(--a-text-muted);
    font-size: 1.1rem;
    flex-shrink: 0;
    padding: 0 0.15rem;
    user-select: none;
}
.drag-handle:active { cursor: grabbing; }

.social-item-icon {
    width: 34px;
    height: 34px;
    background: var(--a-surface-2, #f5f5f5);
    border: 1px solid var(--a-border);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: var(--a-accent);
    flex-shrink: 0;
}

.footer-admin-item-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}
.footer-admin-item-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--a-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.footer-admin-item-url {
    font-size: 0.75rem;
    color: var(--a-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.drag-hint {
    font-size: 0.72rem;
    color: var(--a-text-muted);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function() {

// ── Platform dropdown → icon preview + hint ──────────────────────
var select  = document.getElementById('platformSelect');
var icon    = document.getElementById('platformIcon');
var hint    = document.getElementById('platformHint');
var input   = document.getElementById('platformValue');

var hints = {
    'twitter':   'Enter your username (without @)',
    'facebook':  'Enter your page name or full URL',
    'instagram': 'Enter your username (without @)',
    'linkedin':  'Enter your profile username or full URL',
    'youtube':   'Enter your channel handle (without @)',
    'tiktok':    'Enter your username (without @)',
    'github':    'Enter your username',
    'discord':   'Enter the full invite URL (e.g. https://discord.gg/...)',
    'twitch':    'Enter your channel name',
    'reddit':    'Enter your username (without u/)',
    'mastodon':  'Enter your full Mastodon URL (e.g. https://mastodon.social/@you)',
    'bluesky':   'Enter your handle (e.g. yourname.bsky.social)',
    'pinterest': 'Enter your username',
    'spotify':   'Enter your full Spotify profile URL',
    'patreon':   'Enter your creator name',
    'email':     'Enter your email address',
    'rss':       'Enter the full RSS feed URL',
    'website':   'Enter the full URL (https://...)',
};

if (select) {
    select.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var iconClass = opt.getAttribute('data-icon') || 'bi-share';
        icon.className = 'bi ' + iconClass;
        hint.textContent = hints[this.value] || '';
        input.placeholder = hints[this.value] || 'Username or full URL';
    });
}

// ── Social links drag-to-reorder ─────────────────────────────────
var socialEl = document.getElementById('socialSortable');
if (socialEl) {
    Sortable.create(socialEl, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function() {
            var order = [];
            socialEl.querySelectorAll('li').forEach(function(li) {
                order.push(li.dataset.id);
            });
            reorder('reorder_social', '<?php echo csrf_token('footer_social_order'); ?>', order);
        }
    });
}

// ── Footer links drag-to-reorder ─────────────────────────────────
var footerEl = document.getElementById('footerLinksSortable');
if (footerEl) {
    Sortable.create(footerEl, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function() {
            var order = [];
            footerEl.querySelectorAll('li').forEach(function(li) {
                order.push(li.dataset.id);
            });
            reorder('reorder_footer', '<?php echo csrf_token('footer_links_order'); ?>', order);
        }
    });
}

// ── Shared AJAX reorder helper ───────────────────────────────────
function reorder(action, token, order) {
    var body = action + '=1&csrf_token=' + encodeURIComponent(token);
    order.forEach(function(id) { body += '&order[]=' + id; });
    fetch('index.php?view=settings&section=footer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    });
}

})();
</script>
