<?php
/**
 * Settings > Podcast
 * Feed-level settings for the podcast module.
 */

$msg = '';

if (isset($_POST['save_podcast_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'podcast_settings_save')) {
        http_response_code(403); die('Forbidden');
    }
    $fields = ['podcast_title', 'podcast_author', 'podcast_description',
               'podcast_cover_url', 'podcast_language', 'podcast_category'];
    $prefix = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['podcast_slug_prefix'] ?? 'episodes'))) ?: 'episodes';
    $fields_all = array_merge($fields, ['podcast_slug_prefix']);

    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)
                          ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    foreach ($fields as $f) {
        $stmt->execute([$f, trim($_POST[$f] ?? '')]);
    }
    $stmt->execute(['podcast_slug_prefix', $prefix]);

    log_activity($db, 'SETTINGS', 'Podcast Settings Saved', '');
    $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Podcast settings saved.</div>';
    $settings = get_site_settings($db);
}
?>

<?= $msg ?>

<form method="POST" action="index.php?view=settings&section=podcast">
    <?= csrf_input('podcast_settings_save') ?>

    <div class="a-card mb-3">
        <div class="a-card-header"><div class="a-card-title"><i class="bi bi-rss"></i> Feed Settings</div></div>
        <div class="a-card-body">
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Podcast Title</label>
                    <input type="text" name="podcast_title" value="<?= htmlspecialchars($settings['podcast_title'] ?? '') ?>" placeholder="My Podcast">
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="podcast_author" value="<?= htmlspecialchars($settings['podcast_author'] ?? '') ?>" placeholder="Your Name">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="podcast_description" rows="3"><?= htmlspecialchars($settings['podcast_description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Cover Art URL</label>
                    <input type="url" name="podcast_cover_url" value="<?= htmlspecialchars($settings['podcast_cover_url'] ?? '') ?>" placeholder="https://…">
                </div>
                <div class="form-group">
                    <label>Language</label>
                    <input type="text" name="podcast_language" value="<?= htmlspecialchars($settings['podcast_language'] ?? 'en') ?>" placeholder="en">
                </div>
                <div class="form-group">
                    <label>iTunes Category</label>
                    <input type="text" name="podcast_category" value="<?= htmlspecialchars($settings['podcast_category'] ?? 'Fiction') ?>" placeholder="Fiction">
                </div>
            </div>
        </div>
    </div>

    <div class="a-card mb-3">
        <div class="a-card-header"><div class="a-card-title"><i class="bi bi-link"></i> URL Settings</div></div>
        <div class="a-card-body">
            <div class="form-group" style="max-width:320px;">
                <label>Episode Slug Prefix</label>
                <div class="input-group">
                    <span style="display:flex;align-items:center;padding:0 0.75rem;background:var(--a-surface-2);border:1px solid var(--a-border);border-right:none;border-radius:var(--a-radius) 0 0 var(--a-radius);color:var(--a-text-muted);font-size:0.85rem;white-space:nowrap;">yoursite.com /</span>
                    <input type="text" name="podcast_slug_prefix" value="<?= htmlspecialchars($settings['podcast_slug_prefix'] ?? 'episodes') ?>" style="border-radius:0 var(--a-radius) var(--a-radius) 0;">
                </div>
                <div class="form-help">Current archive: <code>/<?= htmlspecialchars($settings['podcast_slug_prefix'] ?? 'episodes') ?></code></div>
            </div>
        </div>
    </div>

    <div class="a-flex" style="justify-content:flex-end;">
        <button type="submit" name="save_podcast_settings" class="btn btn-primary"><i class="bi bi-floppy"></i> Save Podcast Settings</button>
    </div>
</form>
