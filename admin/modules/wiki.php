<?php
/**
 * Wiki Admin — Router & POST Handlers
 * $db and $settings injected by admin/index.php
 */

$wiki_mod_dir = __DIR__ . '/../../modules/wiki';
$wiki_msg     = '';
$wiki_action  = $_GET['action'] ?? 'list';
$wiki_id      = isset($_GET['id']) ? (int) $_GET['id'] : null;

// ── Bulk status update ────────────────────────────────────────────────────────
if (isset($_POST['wiki_bulk_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_bulk')) {
        http_response_code(403); die('Forbidden');
    }
    $ids    = array_map('intval', $_POST['bulk_ids'] ?? []);
    $status = $_POST['bulk_status'] ?? '';
    if ($ids && $status) {
        wiki_bulk_set_status($db, $ids, $status);
        $wiki_msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . count($ids) . ' entr' . (count($ids) === 1 ? 'y' : 'ies') . ' set to ' . htmlspecialchars($status) . '.</div>';
    }
    $wiki_action = 'list';
}

// ── Prune orphaned links ──────────────────────────────────────────────────────
if (isset($_POST['wiki_prune_orphans'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_orphans')) {
        http_response_code(403); die('Forbidden');
    }
    $pruned  = wiki_prune_orphaned_links($db);
    $wiki_msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . $pruned . ' orphaned link' . ($pruned === 1 ? '' : 's') . ' removed.</div>';
    $wiki_action = 'list';
}

// ── JSON export ───────────────────────────────────────────────────────────────
if (isset($_GET['wiki_export'])) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="wiki-export-' . date('Y-m-d') . '.json"');
    echo wiki_export_json($db);
    exit;
}
if (isset($_GET['wiki_preview'])) {
    wiki_set_preview_mode($_GET['wiki_preview'] === '1');
    $dest = $_SERVER['HTTP_REFERER'] ?? 'index.php?view=wiki';
    header("Location: $dest"); exit;
}

// ── Save entry ────────────────────────────────────────────────────────────────
if (isset($_POST['wiki_save_entry'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_entry_save')) {
        http_response_code(403); log_activity($db, 'SECURITY', 'CSRF Blocked', 'wiki save'); die('Forbidden');
    }
    $id = (int) ($_POST['wiki_entry_id'] ?? 0) ?: null;
    $html_body = $_POST['content'] ?? '[]';
    $saved_id = wiki_save_entry($db, [
        'id'                => $id,
        'title'             => trim($_POST['title'] ?? ''),
        'slug'              => trim($_POST['slug'] ?? ''),
        'entry_type'        => $_POST['entry_type'] ?? 'concept',
        'body'              => $html_body,
        'status'            => $_POST['status'] ?? 'draft',
        'reveal_chapter_id' => (int) ($_POST['reveal_chapter_id'] ?? 0) ?: null,
    ]);
    // Index [[links]] for appears-in tracking
    if (function_exists('wiki_index_links')) {
        $saved_entry = wiki_get_entry($db, $saved_id);
        $saved_slug  = $saved_entry['slug'] ?? '';
        wiki_index_links($db, $saved_slug, 'wiki', $html_body);
        // Record slug redirect if slug changed on an existing entry
        if ($id && function_exists('wiki_record_slug_change')) {
            $old_slug = $db->prepare('SELECT slug FROM wiki_entries WHERE id = ?');
            // slug already saved; compare against POST original
            $original_slug = trim($_POST['slug'] ?? '');
            if ($original_slug && $original_slug !== $saved_slug) {
                wiki_record_slug_change($db, $saved_id, $original_slug, $saved_slug);
            }
        }
    }
    log_activity($db, 'CRUD', $id ? 'Wiki Entry Updated' : 'Wiki Entry Created', "ID: $saved_id");
    $wiki_msg    = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Entry saved.</div>';
    $wiki_action = 'edit';
    $wiki_id     = $saved_id;
}

// ── Delete entry ──────────────────────────────────────────────────────────────
if (isset($_POST['wiki_delete_entry'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_entry_delete')) {
        http_response_code(403); die('Forbidden');
    }
    $del_id = (int) ($_POST['wiki_entry_id'] ?? 0);
    wiki_delete_entry($db, $del_id);
    log_activity($db, 'CRUD', 'Wiki Entry Deleted', "ID: $del_id");
    $wiki_msg    = '<div class="alert alert-warning"><i class="bi bi-trash"></i> Entry deleted.</div>';
    $wiki_action = 'list';
    $wiki_id     = null;
}

// ── Upload & attach image ─────────────────────────────────────────────────────
if (isset($_POST['wiki_upload_image'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_image_upload')) {
        http_response_code(403); die('Forbidden');
    }
    $entry_id = (int) ($_POST['wiki_entry_id'] ?? 0);
    if ($entry_id && !empty($_FILES['wiki_image']['name'])) {
        $doc_root = __DIR__ . '/../..';
        $media_id = wiki_save_media($db, $_FILES['wiki_image'], $doc_root);
        if ($media_id) {
            wiki_attach_image($db, $entry_id, $media_id, $_POST['image_role'] ?? 'inline');
            $wiki_msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Image attached.</div>';
        } else {
            $wiki_msg = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Upload failed — images only (jpg, png, gif, webp).</div>';
        }
    }
    $wiki_action = 'edit';
    $wiki_id     = $entry_id;
}

// ── Remove image ──────────────────────────────────────────────────────────────
if (isset($_POST['wiki_remove_image'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_image_remove')) {
        http_response_code(403); die('Forbidden');
    }
    wiki_remove_image($db, (int) ($_POST['wiki_image_id'] ?? 0));
    $wiki_action = 'edit';
    $wiki_id     = (int) ($_POST['wiki_entry_id'] ?? 0);
}

// ── Add cross-link ────────────────────────────────────────────────────────────
if (isset($_POST['wiki_add_link'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_link')) {
        http_response_code(403); die('Forbidden');
    }
    $src = (int) ($_POST['wiki_entry_id'] ?? 0);
    $tgt = (int) ($_POST['link_target_id'] ?? 0);
    if ($src && $tgt && $src !== $tgt) wiki_add_link($db, $src, $tgt);
    $wiki_action = 'edit';
    $wiki_id     = $src;
}

// ── Remove cross-link ─────────────────────────────────────────────────────────
if (isset($_POST['wiki_remove_link'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'wiki_link')) {
        http_response_code(403); die('Forbidden');
    }
    $src = (int) ($_POST['wiki_entry_id'] ?? 0);
    $tgt = (int) ($_POST['link_target_id'] ?? 0);
    wiki_remove_link($db, $src, $tgt);
    $wiki_action = 'edit';
    $wiki_id     = $src;
}

// ── Sub-routing ───────────────────────────────────────────────────────────────
if ($wiki_action === 'edit') {
    $wiki_entry = $wiki_id ? wiki_get_entry($db, $wiki_id) : null;
    include $wiki_mod_dir . '/admin/edit.php';
} else {
    include $wiki_mod_dir . '/admin/list.php';
}
