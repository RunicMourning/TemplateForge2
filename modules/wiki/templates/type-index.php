<?php
/**
 * Wiki — Type Index Page
 * Available: $db, $settings, $wiki_prefix, $wiki_type
 */
$prefix      = $wiki_prefix;
$type        = $wiki_type;
$entries     = wiki_get_public_entries($db, ['type' => $type], 200, 0);
$wiki_title  = $settings['wiki_title'] ?? 'Wiki';

$type_icons = [
    'character' => 'bi-person',       'place'    => 'bi-geo-alt',
    'faction'   => 'bi-shield',       'concept'  => 'bi-lightbulb',
    'creature'  => 'bi-bug',          'artifact' => 'bi-gem',
    'event'     => 'bi-calendar-event',
];
?>

<div class="container" style="max-width:900px; padding:2.5rem 1.5rem;">

    <div style="margin-bottom:2rem;">
        <a href="/<?= htmlspecialchars($prefix) ?>" style="font-size:0.85rem; opacity:0.6;">← <?= htmlspecialchars($wiki_title) ?></a>
        <h1 style="font-size:1.75rem; margin-top:0.5rem;">
            <i class="bi <?= $type_icons[$type] ?? 'bi-journal' ?>"></i>
            <?= ucfirst($type) ?>s
        </h1>
        <p style="opacity:0.65;"><?= count($entries) ?> entr<?= count($entries) === 1 ? 'y' : 'ies' ?></p>
    </div>

    <?php if (empty($entries)): ?>
    <div style="text-align:center; padding:3rem; opacity:0.4;">
        <i class="bi bi-journal-x" style="font-size:2rem; display:block; margin-bottom:0.75rem;"></i>
        <p>No <?= $type ?>s revealed yet.</p>
    </div>
    <?php else: ?>
    <div class="wiki-entry-list">
        <?php foreach ($entries as $e):
            $images = wiki_get_images($db, $e['id']);
            $cover  = null;
            foreach ($images as $img) {
                if (in_array($img['image_role'], ['portrait', 'cover'])) { $cover = $img; break; }
            }
        ?>
        <a href="/<?= htmlspecialchars($prefix) ?>/<?= htmlspecialchars($e['slug']) ?>" class="wiki-list-item">
            <?php if ($cover): ?>
            <div class="wiki-list-thumb">
                <img src="<?= htmlspecialchars($cover['url']) ?>" alt="<?= htmlspecialchars($e['title']) ?>">
            </div>
            <?php else: ?>
            <div class="wiki-list-thumb wiki-list-thumb--empty">
                <i class="bi <?= $type_icons[$type] ?? 'bi-journal' ?>"></i>
            </div>
            <?php endif; ?>
            <div class="wiki-list-body">
                <div class="wiki-list-title"><?= htmlspecialchars($e['title']) ?></div>
                <?php
                    $blocks = json_decode($e['body'] ?? '[]', true) ?? [];
                    $excerpt = '';
                    foreach ($blocks as $b) {
                        if ($b['type'] === 'paragraph' && !empty($b['content'])) {
                            $excerpt = mb_substr(strip_tags($b['content']), 0, 140);
                            if (mb_strlen($b['content']) > 140) $excerpt .= '…';
                            break;
                        }
                    }
                    if ($excerpt): ?>
                <div class="wiki-list-excerpt"><?= htmlspecialchars($excerpt) ?></div>
                <?php endif; ?>
            </div>
            <i class="bi bi-chevron-right" style="opacity:0.3; flex-shrink:0;"></i>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
