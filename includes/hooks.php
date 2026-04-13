<?php
/**
 * TemplateForge2 Hook Engine
 *
 * Action hooks  — fire side effects, pass context to callbacks.
 *   add_hook($name, $callback)
 *   run_hook($name, ...$args)
 *
 * Filter hooks  — intercept a value, return a modified version.
 *   add_filter($name, $callback, $priority = 10)
 *   apply_filter($name, $value, ...$args)
 *
 * Settings sections (addon API, unchanged):
 *   register_settings_section($id, $section)
 *   get_registered_settings_sections()
 */

$GLOBALS['registered_hooks']            = [];
$GLOBALS['registered_filters']          = [];
$GLOBALS['registered_settings_sections'] = [];

// ─── Action Hooks ─────────────────────────────────────────────────────────────

/**
 * Register a callback on an action hook.
 *
 * @param string   $hook_name
 * @param callable $callback  Receives any args passed to run_hook().
 */
function add_hook(string $hook_name, callable $callback): void {
    $GLOBALS['registered_hooks'][$hook_name][] = $callback;
}

/**
 * Fire all callbacks registered to an action hook.
 * Extra arguments are forwarded to every callback.
 *
 * @param string $hook_name
 * @param mixed  ...$args    Context passed to each callback.
 */
function run_hook(string $hook_name, mixed ...$args): void {
    if (empty($GLOBALS['registered_hooks'][$hook_name])) return;
    foreach ($GLOBALS['registered_hooks'][$hook_name] as $callback) {
        if (is_callable($callback)) {
            call_user_func_array($callback, $args);
        } else {
            echo $callback . PHP_EOL;
        }
    }
}

// ─── Filter Hooks ─────────────────────────────────────────────────────────────

/**
 * Register a callback on a filter hook.
 * Callbacks are sorted by priority (lower runs first) at apply time.
 *
 * @param string   $filter_name
 * @param callable $callback    Must return the (modified) value.
 * @param int      $priority    Default 10. Lower = earlier.
 */
function add_filter(string $filter_name, callable $callback, int $priority = 10): void {
    $GLOBALS['registered_filters'][$filter_name][] = [
        'callback' => $callback,
        'priority' => $priority,
    ];
}

/**
 * Pass a value through all filter callbacks and return the result.
 * Extra arguments provide read-only context (not modified by filters).
 *
 * @param string $filter_name
 * @param mixed  $value        The value to filter.
 * @param mixed  ...$args      Read-only context passed to each callback.
 * @return mixed               The filtered value.
 */
function apply_filter(string $filter_name, mixed $value, mixed ...$args): mixed {
    if (empty($GLOBALS['registered_filters'][$filter_name])) return $value;

    $entries = $GLOBALS['registered_filters'][$filter_name];
    usort($entries, fn($a, $b) => $a['priority'] <=> $b['priority']);

    foreach ($entries as $entry) {
        $value = call_user_func_array($entry['callback'], [$value, ...$args]);
    }
    return $value;
}

// ─── Settings Sections ────────────────────────────────────────────────────────

/**
 * Register an addon settings section for admin/modules/settings.php.
 *
 * Shape: ['title'=>'', 'description'=>'', 'icon'=>'bi bi-gear', 'fields'=>[
 *   ['key'=>'', 'label'=>'', 'type'=>'text|email|password|textarea|select',
 *    'placeholder'=>'', 'default'=>'', 'help'=>'', 'rows'=>4]
 * ]]
 */
function register_settings_section(string $id, array $section): void {
    if (!isset($section['fields']) || !is_array($section['fields'])) {
        $section['fields'] = [];
    }
    $GLOBALS['registered_settings_sections'][$id] = $section;
}

/** Returns all dynamically-registered settings sections. */
function get_registered_settings_sections(): array {
    return $GLOBALS['registered_settings_sections'] ?? [];
}

// ─── Debug ────────────────────────────────────────────────────────────────────

/** Dumps active hook and filter names to the browser console. */
function debug_hooks(): void {
    $hooks   = array_keys($GLOBALS['registered_hooks']   ?? []);
    $filters = array_keys($GLOBALS['registered_filters'] ?? []);
    echo '<script>console.log("Action hooks:", ' . json_encode($hooks)   . ');'
       . 'console.log("Filter hooks:", '          . json_encode($filters) . ');</script>' . PHP_EOL;
}
