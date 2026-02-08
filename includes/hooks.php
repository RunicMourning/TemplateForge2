<?php
/**
 * Hook Engine for CMS Addons - Superglobal Version
 */

// 1. Initialize the storage in the master global array
$GLOBALS['registered_hooks'] = [];
$GLOBALS['registered_settings_sections'] = [];

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

/**
 * Registers a settings section that can be rendered in admin/modules/settings.php.
 *
 * Expected shape:
 * [
 *   'title' => 'Section title',
 *   'description' => 'Optional helper text',
 *   'icon' => 'bi bi-gear',
 *   'fields' => [
 *      [
 *          'key' => 'setting_key',
 *          'label' => 'Field Label',
 *          'type' => 'text|email|password|textarea',
 *          'placeholder' => 'Optional placeholder',
 *          'default' => 'Default value',
 *          'help' => 'Optional helper text',
 *          'rows' => 4 // textarea only
 *      ]
 *   ]
 * ]
 */
function register_settings_section(string $id, array $section): void {
    if (!isset($section['fields']) || !is_array($section['fields'])) {
        $section['fields'] = [];
    }

    $GLOBALS['registered_settings_sections'][$id] = $section;
}

/**
 * Returns all dynamically-registered settings sections.
 */
function get_registered_settings_sections(): array {
    return $GLOBALS['registered_settings_sections'] ?? [];
}
