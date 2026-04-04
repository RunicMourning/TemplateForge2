<?php
/**
 * Theme Switcher Modal
 * Included by templates/header.php after the nav script.
 * Layer A (Presentation) — reads $tf_registry and $active_theme from header context.
 */
?>
<!-- ── Theme Switcher Modal ──────────────────────────────────────── -->
<div id="themeSwitcherOverlay" class="ts-overlay" aria-hidden="true">
    <div class="ts-modal" role="dialog" aria-modal="true" aria-labelledby="tsTitle">

        <div class="ts-header">
            <span id="tsTitle"><i class="bi bi-palette"></i> Choose a Theme</span>
            <button class="ts-close" id="tsClose" aria-label="Close">&times;</button>
        </div>

        <p class="ts-subtitle">Select any theme — changes apply instantly. Add a <code>name-light.css</code> or <code>name-dark.css</code> to <code>/themes/</code> to register it automatically.</p>

        <div class="ts-list">
<?php
// Group themes by group name, light before dark
$ts_groups = [];
foreach ($tf_registry as $slug => $t) {
    $ts_groups[$t['group']][$t['variant']] = array_merge(['slug' => $slug], $t);
}
ksort($ts_groups);

foreach ($ts_groups as $group_name => $variants):
    // Show light first, then dark
    $ordered = [];
    if (isset($variants['Light'])) $ordered[] = $variants['Light'];
    if (isset($variants['Dark']))  $ordered[] = $variants['Dark'];

    foreach ($ordered as $t):
        $slug      = $t['slug'];
        $is_active = ($active_theme === $slug);
        $colors    = $t['colors'];
        // Build CSS gradient stops from the 4 color values
        $c1 = $colors[0] ?? '#888';
        $c2 = $colors[1] ?? '#555';
        $c3 = $colors[2] ?? '#333';
        $c4 = $colors[3] ?? '#111';
        $gradient = "linear-gradient(135deg, {$c1} 0%, {$c1} 30%, {$c2} 30%, {$c2} 55%, {$c3} 55%, {$c3} 78%, {$c4} 78%)";
        $variant_lower = strtolower($t['variant']);
?>
            <button class="ts-row <?php echo $is_active ? 'ts-active' : ''; ?>"
                    data-theme="<?php echo htmlspecialchars($slug); ?>"
                    aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
                    title="Apply <?php echo htmlspecialchars($t['label'] . ' ' . $t['variant']); ?>">

                <!-- CSS gradient color icon -->
                <div class="ts-icon" style="background: <?php echo $gradient; ?>;" aria-hidden="true">
                    <?php if ($is_active): ?>
                    <div class="ts-icon-check"><i class="bi bi-check-lg"></i></div>
                    <?php endif; ?>
                </div>

                <div class="ts-info">
                    <div class="ts-name">
                        <?php echo htmlspecialchars($t['label']); ?>
                        <span class="ts-variant ts-variant--<?php echo $variant_lower; ?>"><?php echo htmlspecialchars($t['variant']); ?></span>
                    </div>
                    <div class="ts-meta">
                        <span class="ts-layout"><i class="bi bi-layout-sidebar"></i> <?php echo htmlspecialchars($t['layout']); ?></span>
                    </div>
                </div>

                <?php if ($is_active): ?>
                <div class="ts-active-mark" aria-label="Currently active"></div>
                <?php endif; ?>

            </button>
<?php
    endforeach;
endforeach;
?>
        </div>

        <div class="ts-footer">
            <span id="tsStatus" class="ts-status"></span>
        </div>
    </div>
</div>


<script>
(function () {
    var overlay   = document.getElementById('themeSwitcherOverlay');
    var openBtn   = document.getElementById('themeToggle');
    var closeBtn  = document.getElementById('tsClose');
    var status    = document.getElementById('tsStatus');
    var themeLink = document.getElementById('tf-theme-stylesheet');

    function openModal() {
        overlay.classList.add('ts-open');
        overlay.setAttribute('aria-hidden', 'false');
        closeBtn.focus();
    }

    function closeModal() {
        overlay.classList.remove('ts-open');
        overlay.setAttribute('aria-hidden', 'true');
        openBtn.focus();
    }

    if (openBtn)  openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('ts-open')) closeModal();
    });

    document.querySelectorAll('.ts-row').forEach(function (row) {
        row.addEventListener('click', function () {
            var theme = row.dataset.theme;
            if (!theme) return;

            // Swap stylesheet immediately (optimistic)
            if (themeLink) themeLink.href = '/themes/' + theme + '.css';

            // Update active states
            document.querySelectorAll('.ts-row').forEach(function (r) {
                r.classList.remove('ts-active');
                r.setAttribute('aria-pressed', 'false');
                var chk = r.querySelector('.ts-icon-check');
                if (chk) chk.remove();
                var dot = r.querySelector('.ts-active-mark');
                if (dot) dot.remove();
            });

            row.classList.add('ts-active');
            row.setAttribute('aria-pressed', 'true');

            // Add check overlay to icon
            var icon = row.querySelector('.ts-icon');
            if (icon && !icon.querySelector('.ts-icon-check')) {
                var chk = document.createElement('div');
                chk.className = 'ts-icon-check';
                chk.innerHTML = '<i class="bi bi-check-lg"></i>';
                icon.appendChild(chk);
            }
            // Add active dot
            if (!row.querySelector('.ts-active-mark')) {
                var dot = document.createElement('div');
                dot.className = 'ts-active-mark';
                row.appendChild(dot);
            }

            status.textContent = 'Applying\u2026';
            status.className = 'ts-status ts-saving';

            var fd = new FormData();
            fd.append('theme', theme);
            fetch('/theme-switcher.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        status.textContent = '\u2713 Applied.';
                        status.className = 'ts-status ts-success';
                    } else {
                        status.textContent = '\u26a0 Could not save preference.';
                        status.className = 'ts-status ts-error';
                    }
                    setTimeout(function () { status.textContent = ''; status.className = 'ts-status'; }, 3000);
                })
                .catch(function () {
                    status.textContent = '\u26a0 Network error — theme applied visually but not saved.';
                    status.className = 'ts-status ts-error';
                    setTimeout(function () { status.textContent = ''; status.className = 'ts-status'; }, 4000);
                });
        });
    });
})();
</script>
