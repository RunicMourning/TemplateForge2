<?php
/**
 * Podcast Admin — Episode Edit Form
 * Available: $db, $pod_episode (null for new), $pod_msg
 */

$ep     = $pod_episode ?? [];
$ep_id  = $ep['id'] ?? null;
$is_new = !$ep_id;

$chapters = podcast_get_chapters($db);
$posts    = $db->query("SELECT slug, title FROM posts WHERE status='published' ORDER BY title ASC")
               ->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-title-bar">
    <div>
        <div class="page-title">
            <a href="index.php?view=podcast" style="color:var(--a-text-muted);font-weight:400;">Podcast</a>
            <span style="color:var(--a-border);margin:0 0.4rem;">/</span>
            <?= $is_new ? 'New Episode' : 'Ep. ' . $ep['episode_number'] . ': ' . htmlspecialchars($ep['title']) ?>
        </div>
    </div>
    <a href="index.php?view=podcast" class="btn btn-outline"><i class="bi bi-arrow-left"></i> All Episodes</a>
</div>

<?= $pod_msg ?>

<form method="POST" action="index.php?view=podcast">
    <?= csrf_input('pod_episode_save') ?>
    <input type="hidden" name="episode_id" value="<?= $ep_id ?>">

    <div class="a-card mb-3">
        <div class="a-card-header"><div class="a-card-title"><i class="bi bi-info-circle"></i> Episode Details</div></div>
        <div class="a-card-body">
            <div class="form-row mb-2">
                <div class="form-group" style="max-width:120px;">
                    <label>Episode #</label>
                    <input type="number" name="episode_number" min="1" value="<?= $ep['episode_number'] ?? '' ?>" required>
                </div>
                <div class="form-group" style="flex:2;">
                    <label>Title</label>
                    <input type="text" name="title" id="pod-title" value="<?= htmlspecialchars($ep['title'] ?? '') ?>" required placeholder="Episode title…">
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" id="pod-slug" value="<?= htmlspecialchars($ep['slug'] ?? '') ?>" placeholder="auto-generated">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Release Date</label>
                    <input type="date" name="release_date" value="<?= $ep['release_date'] ?? date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft"     <?= ($ep['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($ep['status'] ?? '') === 'published'      ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <?php if (!empty($chapters)): ?>
                <div class="form-group">
                    <label>Chapter</label>
                    <select name="chapter_id">
                        <option value="">No chapter</option>
                        <?php foreach ($chapters as $ch): ?>
                        <option value="<?= $ch['id'] ?>" <?= ($ep['chapter_id'] ?? '') == $ch['id'] ? 'selected' : '' ?>>
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
        <div class="a-card-header"><div class="a-card-title"><i class="bi bi-volume-up"></i> Audio</div></div>
        <div class="a-card-body">
            <div class="form-group">
                <label>Audio URL or Embed Code</label>
                <textarea name="audio_url" rows="3" placeholder="https://… or paste an iframe/player embed code"><?= htmlspecialchars($ep['audio_url'] ?? '') ?></textarea>
                <div class="form-help">Direct mp3/m4a URL for a native player, or paste embed HTML from Buzzsprout, Anchor, Spotify, etc.</div>
            </div>
        </div>
    </div>

    <div class="a-card mb-3">
        <div class="a-card-header"><div class="a-card-title"><i class="bi bi-card-text"></i> Description & Show Notes</div></div>
        <div class="a-card-body">
            <div class="form-group">
                <label>Episode Description</label>
                <textarea name="description" rows="4" placeholder="Brief episode summary…"><?= htmlspecialchars($ep['description'] ?? '') ?></textarea>
                <div class="form-help">Shown in the episode header and RSS feed.</div>
            </div>
            <div class="form-group">
                <label>Linked Post (Show Notes)</label>
                <select name="linked_post_slug">
                    <option value="">No linked post</option>
                    <?php foreach ($posts as $post): ?>
                    <option value="<?= htmlspecialchars($post['slug']) ?>" <?= ($ep['linked_post_slug'] ?? '') === $post['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($post['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-help">Full show notes will be pulled from this post and displayed on the episode page.</div>
            </div>
        </div>
    </div>

    <div class="a-flex-between mb-4">
        <a href="index.php?view=podcast" class="btn btn-ghost">Cancel</a>
        <button type="submit" name="pod_save_episode" class="btn btn-primary">
            <i class="bi bi-floppy"></i> <?= $is_new ? 'Create Episode' : 'Save Changes' ?>
        </button>
    </div>
</form>

<script>
const podTitle = document.getElementById('pod-title');
const podSlug  = document.getElementById('pod-slug');
if (podTitle && podSlug) {
    let manual = podSlug.value.length > 0;
    podSlug.addEventListener('input', () => { manual = true; });
    podTitle.addEventListener('input', () => {
        if (manual) return;
        podSlug.value = podTitle.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-|-$/g,'');
    });
}
</script>
