<?php
/**
 * Theme Registry
 * Scans /themes/ for *.css files with @tf-meta headers.
 * Returns structured theme data — no hardcoded theme list.
 *
 * Each theme CSS file should start with:
 *   /*!
 *    * @tf-label:   Display Name
 *    * @tf-variant:  Light|Dark
 *    * @tf-group:    Group Name (pairs light+dark)
 *    * @tf-colors:   #hex1,#hex2,#hex3,#hex4  (for gradient icon)
 *    * @tf-layout:   Layout Description
 *    * @tf-source:   Bootswatch XYZ
 *    *\/
 */

function tf_get_theme_registry(): array {
    $themes_dir = dirname(__DIR__) . '/themes';
    $themes     = [];

    foreach (glob($themes_dir . '/*.css') as $path) {
        $filename = basename($path, '.css');

        // Skip core stylesheet
        if ($filename === 'core') continue;

        // Skip legacy files (no hyphen — e.g. "broadsheet.css" without -light/-dark)
        // These are kept for backwards compat but not shown in switcher
        if (!preg_match('/^(.+)-(light|dark)$/', $filename, $m)) continue;

        $group   = $m[1];
        $variant = $m[2]; // 'light' or 'dark'

        // Read just the first 600 bytes to find the meta block
        $fh      = fopen($path, 'r');
        $header  = fread($fh, 600);
        fclose($fh);

        // Parse @tf-* tags
        $meta = [
            'slug'    => $filename,
            'group'   => ucfirst($group),
            'variant' => ucfirst($variant),
            'label'   => ucfirst($group),
            'colors'  => [],
            'layout'  => '',
            'source'  => '',
        ];

        if (preg_match('/@tf-label:\s*(.+)/',   $header, $x)) $meta['label']  = trim($x[1]);
        if (preg_match('/@tf-group:\s*(.+)/',   $header, $x)) $meta['group']  = trim($x[1]);
        if (preg_match('/@tf-layout:\s*(.+)/',  $header, $x)) $meta['layout'] = trim($x[1]);
        if (preg_match('/@tf-source:\s*(.+)/',  $header, $x)) $meta['source'] = trim($x[1]);
        if (preg_match('/@tf-colors:\s*(.+)/',  $header, $x)) {
            $meta['colors'] = array_map('trim', explode(',', trim($x[1])));
        }

        $themes[$filename] = $meta;
    }

    // Sort: group alphabetically, light before dark within group
    uasort($themes, function($a, $b) {
        $gc = strcmp($a['group'], $b['group']);
        if ($gc !== 0) return $gc;
        // light before dark
        return strcmp($a['variant'], $b['variant']); // 'Dark' > 'Light' alphabetically — invert
    });

    return $themes;
}

function tf_is_valid_theme(string $slug): bool {
    if (!preg_match('/^[a-z0-9\-]+$/', $slug)) return false;
    $path = dirname(__DIR__) . '/themes/' . $slug . '.css';
    return file_exists($path);
}
