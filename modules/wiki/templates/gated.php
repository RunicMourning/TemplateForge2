<?php
/**
 * Wiki — Gated Entry Placeholder
 * Shown when a visitor directly accesses a URL for a not-yet-released entry.
 * $entry_title is set by the router when _gated flag is present.
 */
$prefix = $settings['wiki_slug_prefix'] ?? 'wiki';
?>
<div class="container" style="padding:4rem 1.5rem; text-align:center; max-width:560px; margin:0 auto;">
    <div style="font-size:3rem; margin-bottom:1rem; opacity:0.35;"><i class="bi bi-lock"></i></div>
    <h1 style="font-size:1.5rem; margin-bottom:0.5rem;">Not Yet Available</h1>
    <?php if (!empty($entry_title)): ?>
    <p style="margin-bottom:1.5rem; opacity:0.7;">
        <strong><?= htmlspecialchars($entry_title) ?></strong> will be revealed in a future episode.
    </p>
    <?php else: ?>
    <p style="margin-bottom:1.5rem; opacity:0.7;">This entry will be revealed in a future episode.</p>
    <?php endif; ?>
    <a href="/<?= htmlspecialchars($prefix) ?>" class="btn btn-secondary">← Back to Lore</a>
</div>
