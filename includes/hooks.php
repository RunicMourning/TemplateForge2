<?php
/**
 * Hook Engine for CMS Addons - Superglobal Version
 */

// 1. Initialize the storage in the master global array
$GLOBALS['registered_hooks'] = [];

/**
 * Registers a function or HTML string to a specific hook location.
 * Uses $GLOBALS to ensure cross-file persistence.
 */
function add_hook($hook_name, $callback) {
    $GLOBALS['registered_hooks'][$hook_name][] = $callback;
}

/**
 * Executes all items registered to a specific hook.
 */
function run_hook($hook_name) {
    if (isset($GLOBALS['registered_hooks'][$hook_name]) && is_array($GLOBALS['registered_hooks'][$hook_name])) {
        foreach ($GLOBALS['registered_hooks'][$hook_name] as $callback) {
            if (is_callable($callback)) {
                call_user_func($callback);
            } else {
                echo $callback . PHP_EOL;
            }
        }
    }
}

/**
 * DEBUG HELPER: Call this in your footer or a template to see all active hooks.
 */
function debug_hooks() {
    $keys = isset($GLOBALS['registered_hooks']) ? array_keys($GLOBALS['registered_hooks']) : [];
    echo '' . PHP_EOL;
    echo '<script>console.log("Active Hooks Registry:", ' . json_encode($keys) . ');</script>';
    echo '' . PHP_EOL;
}