<?php
/**
 * Podcast Admin — List View
 * Available: $db, $pod_msg, $pod_tab
 */

$episodes = podcast_get_episodes($db, '', 50, 0);
$chapters = podcast_get_chapters($db);
$posts    = $db->query("SELECT slug, title FROM posts WHERE status='published' ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
$prefix   = $settings['podcast_slug_prefix'] ?? 'episodes';
?>

<div class="page-title-bar">
    <div>
        <div class="page-title"><i class="bi bi-mic"></i> Podcast</div>
        <div class="page-subtitle"><?= count($episodes) ?> episode<?= count($episodes) !== 1 ? 's' : '' ?> &middot; <?= count($chapters) ?> chapter<?= count($chapters) !== 1 ? 's' : '' ?></div>
    </div>
    <a href="index.php?view=podcast&action=edit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Episode</a>
</div>

<?= $pod_msg ?>

<div class="a-flex gap-2 mb-3">
    <a href="?view=podcast&tab=episodes" class="btn <?= $pod_tab !== 'chapters' ? 'btn-primary' : 'btn-outline' ?>">Episodes</a>
    <a href="?view=podcast&tab=chapters" class="btn <?= $pod_tab === 'chapters' ? 'btn-primary' : 'btn-outline' ?>">Chapters</a>
</div>

<?php if ($pod_tab === 'chapters'): ?>

<!-- Chapters tab -->
<div class="a-card mb-3">
    <div class="a-card-header"><div class="a-card-title"><i class="bi bi-bookmark"></i> Chapters</div></div>
    <?php if (empty($chapters)): ?>
    <div class="empty-state"><span class="empty-icon"><i class="bi bi-bookmark-x"></i></span><p>No chapters yet.</p></div>
    <?php else: ?>
    <div class="a-table-wrap">
        <table>
            <thead><tr><th>Ep.</th><th>Title</th><th>Release Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($chapters as $ch): ?>
            <tr>
                <td class="mono"><?= $ch['episode_number'] ?></td>
                <td><?= htmlspecialchars($ch['title']) ?></td>
                <td style="font-size:0.8rem;"><?= $ch['release_date'] ?></td>
                <td><span class="badge <?= $ch['status'] === 'released' ? 'badge-green' : 'badge-yellow' ?>"><?= ucfirst($ch['status']) ?></span></td>
                <td style="text-align:right;">
                    <form method="POST" action="index.php?view=podcast&tab=chapters" style="display:inline;">
                        <?= csrf_input('pod_chapter_delete') ?>
                        <input type="hidden" name="chapter_id" value="<?= $ch['id'] ?>">
                        <button type="submit" name="pod_delete_chapter" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this chapter?')"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="a-card">
    <div class="a-card-header"><div class="a-card-title"><i class="bi bi-plus-lg"></i> Add Chapter</div></div>
    <div class="a-card-body">
        <form method="POST" action="index.php?view=podcast&tab=chapters">
            <?= csrf_input('pod_chapter_save') ?>
            <div class="form-row">
                <div class="form-group"><label>Episode Number</label><input type="number" name="chapter_episode_number" min="1" required></div>
                <div class="form-group" style="flex:2;"><label>Chapter Title</label><input type="text" name="chapter_title" required placeholder="e.g. The Storm Rises"></div>
                <div class="form-group"><label>Release Date</label><input type="date" name="chapter_release_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="chapter_status">
                        <option value="scheduled">Scheduled</option>
                        <option value="released">Released</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="pod_save_chapter" class="btn btn-outline"><i class="bi bi-plus-lg"></i> Add Chapter</button>
        </form>
    </div>
</div>

<?php else: ?>

<!-- Episodes tab -->
<div class="a-card">
    <?php if (empty($episodes)): ?>
    <div class="empty-state"><span class="empty-icon"><i class="bi bi-mic-mute"></i></span><p>No episodes yet. <a href="index.php?view=podcast&action=edit">Add the first one.</a></p></div>
    <?php else: ?>
    <div class="a-table-wrap">
        <table>
            <thead><tr><th>Ep.</th><th>Title</th><th>Release</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($episodes as $ep): ?>
            <tr>
                <td class="mono"><?= $ep['episode_number'] ?></td>
                <td>
                    <a href="index.php?view=podcast&action=edit&id=<?= $ep['id'] ?>" class="fw-semibold"><?= htmlspecialchars($ep['title']) ?></a>
                    <div style="font-size:0.75rem;color:var(--a-text-muted);">/<?= $prefix ?>/<?= htmlspecialchars($ep['slug']) ?></div>
                </td>
                <td style="font-size:0.8rem;"><?= $ep['release_date'] ?></td>
                <td><span class="badge <?= $ep['status'] === 'published' ? 'badge-green' : '' ?>"><?= ucfirst($ep['status']) ?></span></td>
                <td style="text-align:right;white-space:nowrap;">
                    <a href="index.php?view=podcast&action=edit&id=<?= $ep['id'] ?>" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i> Edit</a>
                    <form method="POST" action="index.php?view=podcast" style="display:inline;"
                          onsubmit="return confirm('Delete this episode?')">
                        <?= csrf_input('pod_episode_delete') ?>
                        <input type="hidden" name="episode_id" value="<?= $ep['id'] ?>">
                        <button type="submit" name="pod_delete_episode" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
