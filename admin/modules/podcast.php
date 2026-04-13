<?php
/**
 * Podcast Admin — Router & POST Handlers
 * $db and $settings injected by admin/index.php
 */

$pod_dir    = __DIR__ . '/../../modules/podcast';
$pod_msg    = '';
$pod_action = $_GET['action'] ?? 'list';
$pod_id     = isset($_GET['id']) ? (int) $_GET['id'] : null;
$pod_tab    = $_GET['tab'] ?? 'episodes'; // 'episodes' or 'chapters'

// ── Save episode ──────────────────────────────────────────────────────────────
if (isset($_POST['pod_save_episode'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'pod_episode_save')) {
        http_response_code(403); die('Forbidden');
    }
    $id = (int) ($_POST['episode_id'] ?? 0) ?: null;
    $saved_id = podcast_save_episode($db, [
        'id'               => $id,
        'episode_number'   => (int) ($_POST['episode_number'] ?? 0),
        'title'            => trim($_POST['title'] ?? ''),
        'slug'             => trim($_POST['slug'] ?? ''),
        'audio_url'        => trim($_POST['audio_url'] ?? ''),
        'description'      => $_POST['description'] ?? '',
        'linked_post_slug' => trim($_POST['linked_post_slug'] ?? ''),
        'chapter_id'       => (int) ($_POST['chapter_id'] ?? 0) ?: null,
        'release_date'     => $_POST['release_date'] ?? date('Y-m-d'),
        'status'           => $_POST['status'] ?? 'draft',
    ]);
    log_activity($db, 'CRUD', $id ? 'Episode Updated' : 'Episode Created', "ID: $saved_id");
    $pod_msg    = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Episode saved.</div>';
    $pod_action = 'edit';
    $pod_id     = $saved_id;
}

// ── Delete episode ────────────────────────────────────────────────────────────
if (isset($_POST['pod_delete_episode'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'pod_episode_delete')) {
        http_response_code(403); die('Forbidden');
    }
    podcast_delete_episode($db, (int) ($_POST['episode_id'] ?? 0));
    $pod_msg    = '<div class="alert alert-warning"><i class="bi bi-trash"></i> Episode deleted.</div>';
    $pod_action = 'list';
}

// ── Save chapter ──────────────────────────────────────────────────────────────
if (isset($_POST['pod_save_chapter'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'pod_chapter_save')) {
        http_response_code(403); die('Forbidden');
    }
    podcast_save_chapter($db, [
        'id'             => (int) ($_POST['chapter_id'] ?? 0) ?: null,
        'title'          => trim($_POST['chapter_title'] ?? ''),
        'episode_number' => (int) ($_POST['chapter_episode_number'] ?? 0),
        'release_date'   => $_POST['chapter_release_date'] ?? date('Y-m-d'),
        'status'         => $_POST['chapter_status'] ?? 'scheduled',
    ]);
    $pod_msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Chapter saved.</div>';
    $pod_tab = 'chapters';
}

// ── Delete chapter ────────────────────────────────────────────────────────────
if (isset($_POST['pod_delete_chapter'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'pod_chapter_delete')) {
        http_response_code(403); die('Forbidden');
    }
    podcast_delete_chapter($db, (int) ($_POST['chapter_id'] ?? 0));
    $pod_msg = '<div class="alert alert-warning"><i class="bi bi-trash"></i> Chapter deleted.</div>';
    $pod_tab = 'chapters';
}

// ── Sub-routing ───────────────────────────────────────────────────────────────
if ($pod_action === 'edit') {
    $pod_episode = $pod_id ? podcast_get_episode($db, $pod_id) : null;
    include $pod_dir . '/admin/edit.php';
} else {
    include $pod_dir . '/admin/list.php';
}
