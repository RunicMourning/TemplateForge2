<?php
/**
 * Wiki — Single Entry Page
 * Available: $db, $settings, $wiki_prefix, $entry (full row)
 */
$prefix  = $wiki_prefix;
$images  = wiki_get_images($db, $entry['id']);
$links   = wiki_get_links($db, $entry['id']);
$appears = function_exists('wiki_get_appearances') ? wiki_get_appearances($db, $entry['id']) : [];

// Filter linked entries through gating — don't expose gated titles in related section
$visible_links = array_filter($links, function($lnk) use ($db) {
    $e = wiki_get_entry($db, (int) $lnk['linked_id']);
    return $e && wiki_entry_is_visible($db, $e);
});

// Sort images by role
$cover    = null; $portrait = null; $map = null; $inline_imgs = [];
foreach ($images as $img) {
    if (!$cover    && $img['image_role'] === 'cover')    $cover    = $img;
    elseif (!$portrait && $img['image_role'] === 'portrait') $portrait = $img;
    elseif (!$map  && $img['image_role'] === 'map')      $map      = $img;
    else $inline_imgs[] = $img;
}

$type_icons = [
    'character'=>'bi-person','place'=>'bi-geo-alt','faction'=>'bi-shield',
    'concept'=>'bi-lightbulb','creature'=>'bi-bug','artifact'=>'bi-gem','event'=>'bi-calendar-event',
];
?>

<?php if ($cover): ?>
<div class="wiki-cover" style="background-image:url('<?= htmlspecialchars($cover['url']) ?>');">
    <div class="wiki-cover-overlay"></div>
</div>
<?php endif; ?>

<div class="container" style="max-width:860px; padding:2rem 1.5rem;">

    <div style="margin-bottom:0.5rem;">
        <a href="/<?= htmlspecialchars($prefix) ?>/<?= htmlspecialchars($entry['entry_type']) ?>"
           style="font-size:0.8rem; opacity:0.6;">
            <i class="bi <?= $type_icons[$entry['entry_type']] ?? 'bi-journal' ?>"></i>
            ← <?= ucfirst($entry['entry_type']) ?>s
        </a>
    </div>

    <div class="wiki-entry-layout <?= $portrait ? 'wiki-entry-layout--has-portrait' : '' ?>">

        <?php if ($portrait): ?>
        <aside class="wiki-portrait-col">
            <img src="<?= htmlspecialchars($portrait['url']) ?>" alt="<?= htmlspecialchars($entry['title']) ?>" class="wiki-portrait">
        </aside>
        <?php endif; ?>

        <div class="wiki-entry-main">
            <h1 class="wiki-entry-title"><?= htmlspecialchars($entry['title']) ?></h1>

            <div class="wiki-entry-body">
            <?php
                $rendered = apply_filter('wiki_entry_render', $entry['body'] ?? '', $entry);
                echo $rendered;
            ?>
            </div>

            <?php if ($map): ?>
            <figure class="wiki-map">
                <img src="<?= htmlspecialchars($map['url']) ?>" alt="Map: <?= htmlspecialchars($entry['title']) ?>">
            </figure>
            <?php endif; ?>

            <?php if (!empty($inline_imgs)): ?>
            <div class="wiki-inline-images">
                <?php foreach ($inline_imgs as $img): ?>
                <figure>
                    <img src="<?= htmlspecialchars($img['url']) ?>" alt="<?= htmlspecialchars($img['original_name']) ?>">
                </figure>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- .wiki-entry-main -->
    </div><!-- .wiki-entry-layout -->

    <?php if (!empty($visible_links)): ?>
    <section class="wiki-related">
        <h3 class="wiki-related-title">Related Entries</h3>
        <div class="wiki-related-grid">
            <?php foreach ($visible_links as $lnk): ?>
            <a href="/<?= htmlspecialchars($prefix) ?>/<?= htmlspecialchars($lnk['slug']) ?>" class="wiki-related-card">
                <span class="wiki-related-type"><i class="bi <?= $type_icons[$lnk['entry_type']] ?? 'bi-journal' ?>"></i></span>
                <span class="wiki-related-name"><?= htmlspecialchars($lnk['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($appears)): ?>
    <section class="wiki-related">
        <h3 class="wiki-related-title">Appears In</h3>
        <div class="wiki-related-grid">
            <?php foreach ($appears as $ap):
                if (empty($ap['title'])) continue;
                $href = $ap['post_type'] === 'post'
                    ? '/blog-' . htmlspecialchars($ap['post_slug']) . '.html'
                    : '/' . htmlspecialchars($ap['post_slug']) . '.html';
            ?>
            <a href="<?= $href ?>" class="wiki-related-card">
                <span class="wiki-related-type"><i class="bi bi-journal-text"></i></span>
                <span class="wiki-related-name"><?= htmlspecialchars($ap['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>
