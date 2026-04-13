<?php
/**
 * Podcast — Episode Archive
 * Available: $db, $settings
 */
$prefix   = $settings['podcast_slug_prefix'] ?? 'episodes';
$episodes = podcast_get_episodes($db, 'published', 100, 0);
?>

<div class="container" style="max-width:780px; padding:2.5rem 1.5rem;">

    <div style="margin-bottom:2rem;">
        <h1 style="font-size:2rem; margin-bottom:0.25rem;">
            <i class="bi bi-mic" style="opacity:0.5; margin-right:0.4rem;"></i>
            <?= htmlspecialchars($settings['podcast_title'] ?: ($settings['site_name'] ?? 'Podcast')) ?>
        </h1>
        <?php if (!empty($settings['podcast_description'])): ?>
        <p style="opacity:0.65; margin-top:0.4rem;"><?= htmlspecialchars($settings['podcast_description']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (empty($episodes)): ?>
    <div style="text-align:center; padding:4rem; opacity:0.4;">
        <i class="bi bi-mic-mute" style="font-size:2.5rem; display:block; margin-bottom:1rem;"></i>
        <p>No episodes yet. Check back soon.</p>
    </div>
    <?php else: ?>
    <div class="pod-episode-list">
        <?php foreach ($episodes as $ep): ?>
        <a href="/<?= $prefix ?>/<?= htmlspecialchars($ep['slug']) ?>" class="pod-episode-row">
            <div class="pod-ep-number">Ep. <?= $ep['episode_number'] ?></div>
            <div class="pod-ep-body">
                <div class="pod-ep-title"><?= htmlspecialchars($ep['title']) ?></div>
                <?php if ($ep['description']): ?>
                <div class="pod-ep-desc"><?= htmlspecialchars(mb_substr($ep['description'], 0, 120)) . (mb_strlen($ep['description']) > 120 ? '…' : '') ?></div>
                <?php endif; ?>
                <div class="pod-ep-date"><?= date('F j, Y', strtotime($ep['release_date'])) ?></div>
            </div>
            <i class="bi bi-play-circle" style="font-size:1.5rem; opacity:0.35; flex-shrink:0;"></i>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
