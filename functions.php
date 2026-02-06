<?php
/**
 * Activity Logger
 */
function log_activity($db, $category, $event, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    
    if (isset($_SESSION['username'])) {
        $user = $_SESSION['username'];
    } else {
        $user = ($category === '404' || $category === 'PHP Error') ? 'Guest' : 'Anonymous';
    }

    $url = $_SERVER['REQUEST_URI'] ?? 'N/A';
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'None';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $full_details = "Details: $details | Referrer: $referrer | UA: $ua";

    try {
        $stmt = $db->prepare("INSERT INTO logs (category, event, details, user, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$category, $event, $full_details, $user, $ip]);
    } catch (Exception $e) {
        // Silently fail if logs table doesn't exist yet
    }
}

/**
 * Site Settings Loader
 */
function get_site_settings($db) {
    if (is_object($db) && get_class($db) === 'class@anonymous') {
        return ['site_name' => 'CMS Installer'];
    }

    try {
        $query = $db->query("SELECT * FROM settings");
        if ($query) {
            return $query->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    } catch (Exception $e) {
        return ['site_name' => 'CMS Initializing...'];
    }
    return [];
}

/**
 * Analytics Tracker
 */
function track_visit($db) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $url = $_SERVER['REQUEST_URI'] ?? '/';
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
    
    $visitor_id = hash('sha256', $ip . $agent);

    $os = "Unknown OS";
    $os_array = [
        '/windows nt 10/i'      => 'Windows 10/11',
        '/windows nt 6.1/i'      => 'Windows 7',
        '/macintosh|mac os x/i' => 'Mac OS',
        '/linux/i'              => 'Linux',
        '/iphone|ipad/i'        => 'iOS',
        '/android/i'            => 'Android'
    ];
    foreach ($os_array as $regex => $value) { 
        if (preg_match($regex, $agent)) $os = $value; 
    }

    $browser = "Unknown Browser";
    $browser_array = [
        '/msie/i'      => 'Internet Explorer',
        '/firefox/i'   => 'Firefox',
        '/safari/i'    => 'Safari',
        '/chrome/i'    => 'Chrome',
        '/edge/i'      => 'Edge',
        '/opera/i'     => 'Opera'
    ];
    foreach ($browser_array as $regex => $value) { 
        if (preg_match($regex, $agent)) $browser = $value; 
    }

    $device = (preg_match('/mobile|android|iphone|ipad/i', $agent)) ? 'Mobile' : 'Desktop';

    try {
        $stmt = $db->prepare("INSERT INTO analytics (visitor_id, page_url, referrer, browser, os, device) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$visitor_id, $url, $referrer, $browser, $os, $device]);
    } catch (Exception $e) { }
}

// Sidebar Buffer
$custom_sidebar_content = '';

function set_page_sidebar($html) {
    global $custom_sidebar_content;
    $custom_sidebar_content = $html;
}

function get_page_sidebar() {
    global $custom_sidebar_content;
    return $custom_sidebar_content;
}

/**
 * FIXED: CSS Registry Functions
 * Wrapped in if(!function_exists) to prevent Fatal Errors on IONOS
 */
if (!function_exists('queue_css')) {
    function queue_css($css_string) {
        if (!isset($GLOBALS['css_registry'])) {
            $GLOBALS['css_registry'] = [];
        }
        if (!in_array($css_string, $GLOBALS['css_registry'])) {
            $GLOBALS['css_registry'][] = $css_string;
        }
    }
}

if (!function_exists('render_queued_css')) {
    function render_queued_css() {
        if (isset($GLOBALS['css_registry']) && !empty($GLOBALS['css_registry'])) {
            echo "\n<style>\n";
            foreach ($GLOBALS['css_registry'] as $css) {
                echo $css . "\n";
            }
            echo "</style>\n";
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(string $context = 'global'): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
            $_SESSION['_csrf_tokens'] = [];
        }

        if (empty($_SESSION['_csrf_tokens'][$context])) {
            $_SESSION['_csrf_tokens'][$context] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_tokens'][$context];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $submitted_token, string $context = 'global'): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['_csrf_tokens'][$context]) || !is_string($submitted_token)) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_tokens'][$context], $submitted_token);
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(string $context = 'global'): string {
        $token = htmlspecialchars(csrf_token($context), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
