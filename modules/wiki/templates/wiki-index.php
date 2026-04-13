<?php
/**
 * Wiki — Master Index / Landing Page
 * Available: $db, $settings, $wiki_prefix
 */
$types       = wiki_entry_types($db);
$prefix      = $wiki_prefix;
$wiki_title  = $settings['wiki_title'] ?? 'Wiki';

$type_icons = [
    'character' => 'bi-person',       'place'    => 'bi-geo-alt',
    'faction'   => 'bi-shield',       'concept'  => 'bi-lightbulb',
    'creature'  => 'bi-bug',          'artifact' => 'bi-gem',
    'event'     => 'bi-calendar-event',
];

$type_desc = [
    'character' => 'People, heroes, and villains.',
    'place'     => 'Locations, regions, and landmarks.',
    'faction'   => 'Groups, organisations, and allegiances.',
    'concept'   => 'Ideas, history, and lore.',
    'creature'  => 'Beasts and beings.',
    'artifact'  => 'Objects of power and significance.',
    'event'     => 'Moments that shaped the world.',
];

// Count visible published entries per type
$counts = [];
foreach ($types as $t) {
    $all = wiki_get_public_entries($db, ['type' => $t], 200, 0);
    $counts[$t] = count($all);
}

$total = array_sum($counts);
?>

<div class="container" style="max-width:900px; padding:2.5rem 1.5rem;">

    <div style="margin-bottom:2.5rem;">
        <h1 style="font-size:2rem; margin-bottom:0.4rem;"><?= htmlspecialchars($wiki_title) ?></h1>
        <p style="opacity:0.65;"><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?> revealed so far.</p>
    </div>

    <div class="wiki-type-grid">
        <?php foreach ($types as $t):
            if ($counts[$t] === 0) continue; ?>
        <a href="/<?= $prefix ?>/<?= $t ?>" class="wiki-type-card">
            <div class="wiki-type-icon"><i class="bi <?= $type_icons[$t] ?? 'bi-journal' ?>"></i></div>
            <div class="wiki-type-name"><?= ucfirst($t) ?>s</div>
            <div class="wiki-type-count"><?= $counts[$t] ?> entr<?= $counts[$t] === 1 ? 'y' : 'ies' ?></div>
            <div class="wiki-type-desc"><?= $type_desc[$t] ?? '' ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($total === 0): ?>
    <div style="text-align:center; padding:4rem 1rem; opacity:0.4;">
        <i class="bi bi-journal-x" style="font-size:2.5rem; display:block; margin-bottom:0.75rem;"></i>
        <p>No lore revealed yet. Check back after the first episode.</p>
    </div>
    <?php endif; ?>

</div>
