<?php
/**
 * Podcast — Single Episode Page
 * Available: $db, $settings, $episode
 */
$prefix     = $settings['podcast_slug_prefix'] ?? 'episodes';
$show_notes = podcast_get_show_notes($db, $episode['linked_post_slug'] ?? null);
$is_embed   = podcast_is_embed($episode['audio_url'] ?? '');

// Wiki entries that first appear in this episode's chapter
$wiki_entries = [];
if (!empty($episode['chapter_id']) && function_exists('wiki_get_entries')) {
    $stmt = $db->prepare("SELECT id, title, slug, entry_type FROM wiki_entries
                          WHERE reveal_chapter_id = ? AND status = 'published'
                          ORDER BY entry_type, title");
    $stmt->execute([$episode['chapter_id']]);
    $wiki_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$type_icons = ['character'=>'bi-person','place'=>'bi-geo-alt','faction'=>'bi-shield',
               'concept'=>'bi-lightbulb','creature'=>'bi-bug','artifact'=>'bi-gem','event'=>'bi-calendar-event'];
$wiki_prefix = $settings['wiki_slug_prefix'] ?? 'lore';
?>

<div class="container" style="max-width:780px; padding:2.5rem 1.5rem;">

    <div style="margin-bottom:0.5rem;">
        <a href="/<?= htmlspecialchars($prefix) ?>" style="font-size:0.85rem; opacity:0.6;">
            <i class="bi bi-mic"></i> ← All Episodes
        </a>
    </div>

    <div class="pod-episode-header">
        <div class="pod-ep-number-lg">Episode <?= $episode['episode_number'] ?></div>
        <h1 class="pod-ep-title-lg"><?= htmlspecialchars($episode['title']) ?></h1>
        <div class="pod-ep-meta"><?= date('F j, Y', strtotime($episode['release_date'])) ?></div>
        <?php if ($episode['description']): ?>
        <p class="pod-ep-description"><?= htmlspecialchars($episode['description']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Audio player -->
    <?php if (!empty($episode['audio_url'])): ?>
    <div class="pod-player">
        <?php if ($is_embed): ?>
            <?= $episode['audio_url'] ?>
        <?php else: ?>
            <audio controls style="width:100%;">
                <source src="<?= htmlspecialchars($episode['audio_url']) ?>">
                Your browser does not support the audio element.
            </audio>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Wiki entries first revealed this episode -->
    <?php if (!empty($wiki_entries)): ?>
    <section class="pod-wiki-reveals">
        <h3 class="pod-section-title"><i class="bi bi-journal-bookmark"></i> First Revealed This Episode</h3>
        <div class="pod-wiki-grid">
            <?php foreach ($wiki_entries as $e): ?>
            <a href="/<?= $wiki_prefix ?>/<?= htmlspecialchars($e['slug']) ?>" class="pod-wiki-card">
                <i class="bi <?= $type_icons[$e['entry_type']] ?? 'bi-journal' ?>" style="opacity:0.5;"></i>
                <span><?= htmlspecialchars($e['title']) ?></span>
                <span style="opacity:0.4; font-size:0.75rem;"><?= ucfirst($e['entry_type']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Show notes from linked post -->
    <?php if ($show_notes): ?>
    <section class="pod-show-notes">
        <h3 class="pod-section-title"><i class="bi bi-card-text"></i> Show Notes</h3>
        <div class="pod-show-notes-body">
            <?= $show_notes['content'] ?>
        </div>
    </section>
    <?php endif; ?>

</div>
