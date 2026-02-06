<?php
/**
 * CSS Registry & Hook Integration
 * Prevents "Redeclare" errors and connects to the theme hook system.
 */

// 1. Define the Global Storage if not already set
if (!isset($GLOBALS['page_custom_css'])) {
    $GLOBALS['page_custom_css'] = [];
}

/**
 * Function to add CSS from any template or addon
 * Wrapped to prevent Fatal Errors if defined elsewhere
 */
if (!function_exists('queue_css')) {
    function queue_css($css_string) {
        // Use the global variable defined above
        if (!in_array($css_string, $GLOBALS['page_custom_css'])) {
            $GLOBALS['page_custom_css'][] = $css_string;
        }
    }
}

/**
 * Register the hook that header.php calls.
 * This looks for 'do_hook("custom_inline_css")' in your header.
 */
if (function_exists('add_hook')) {
    add_hook('custom_inline_css', function() {
        if (!empty($GLOBALS['page_custom_css'])) {
            echo "\n";
            foreach ($GLOBALS['page_custom_css'] as $css) {
                echo "\n/* Queued Style Block */\n";
                echo $css . "\n";
            }
            echo "\n";
        }
    });
}