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
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $url     = $_SERVER['REQUEST_URI'] ?? '/';
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';

    $visitor_id = hash('sha256', $ip . $agent);

    // Session tracking — 30-minute cookie-based session
    $session_id  = $_COOKIE['tf_sid'] ?? null;
    $session_key = 'tf_sess_' . $visitor_id;
    $now         = time();

    if (!$session_id || !isset($_COOKIE[$session_key]) || ($now - (int)$_COOKIE[$session_key]) > 1800) {
        // New or expired session
        $session_id = bin2hex(random_bytes(8));
        $entry_page = $url;
        setcookie('tf_sid',      $session_id,  $now + 1800, '/');
        setcookie($session_key,  (string)$now, $now + 1800, '/');
        setcookie('tf_entry',    $url,          $now + 1800, '/');
    } else {
        // Continuing session
        $entry_page = $_COOKIE['tf_entry'] ?? $url;
        setcookie($session_key, (string)$now, $now + 1800, '/'); // refresh expiry
    }

    $os = 'Unknown OS';
    foreach ([
        '/windows nt 10/i'      => 'Windows 10/11',
        '/windows nt 6.1/i'     => 'Windows 7',
        '/macintosh|mac os x/i' => 'Mac OS',
        '/linux/i'              => 'Linux',
        '/iphone|ipad/i'        => 'iOS',
        '/android/i'            => 'Android',
    ] as $regex => $value) {
        if (preg_match($regex, $agent)) { $os = $value; break; }
    }

    $browser = 'Unknown Browser';
    foreach ([
        '/edg/i'     => 'Edge',
        '/chrome/i'  => 'Chrome',
        '/firefox/i' => 'Firefox',
        '/safari/i'  => 'Safari',
        '/opera/i'   => 'Opera',
        '/msie/i'    => 'Internet Explorer',
    ] as $regex => $value) {
        if (preg_match($regex, $agent)) { $browser = $value; break; }
    }

    $device = preg_match('/mobile|android|iphone|ipad/i', $agent) ? 'Mobile' : 'Desktop';

    try {
        $stmt = $db->prepare(
            "INSERT INTO analytics (visitor_id, session_id, page_url, entry_page, referrer, browser, os, device)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$visitor_id, $session_id, $url, $entry_page, $referrer, $browser, $os, $device]);
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

/**
 * Social Media Platform Registry
 * Maps platform slugs to display label, Bootstrap Icon class, and URL pattern.
 * 'url_prefix' — prepended to the stored value if it doesn't start with http.
 * 'url_prefix' = '' means the stored value must be a full URL.
 */
function tf_social_platforms(): array {
    return [
        'twitter'   => ['label' => 'Twitter / X',   'icon' => 'bi-twitter-x',       'url_prefix' => 'https://x.com/'],
        'facebook'  => ['label' => 'Facebook',       'icon' => 'bi-facebook',         'url_prefix' => 'https://facebook.com/'],
        'instagram' => ['label' => 'Instagram',      'icon' => 'bi-instagram',        'url_prefix' => 'https://instagram.com/'],
        'linkedin'  => ['label' => 'LinkedIn',       'icon' => 'bi-linkedin',         'url_prefix' => 'https://linkedin.com/in/'],
        'youtube'   => ['label' => 'YouTube',        'icon' => 'bi-youtube',          'url_prefix' => 'https://youtube.com/@'],
        'tiktok'    => ['label' => 'TikTok',         'icon' => 'bi-tiktok',           'url_prefix' => 'https://tiktok.com/@'],
        'github'    => ['label' => 'GitHub',         'icon' => 'bi-github',           'url_prefix' => 'https://github.com/'],
        'discord'   => ['label' => 'Discord',        'icon' => 'bi-discord',          'url_prefix' => ''],
        'twitch'    => ['label' => 'Twitch',         'icon' => 'bi-twitch',           'url_prefix' => 'https://twitch.tv/'],
        'reddit'    => ['label' => 'Reddit',         'icon' => 'bi-reddit',           'url_prefix' => 'https://reddit.com/u/'],
        'mastodon'  => ['label' => 'Mastodon',       'icon' => 'bi-mastodon',         'url_prefix' => ''],
        'bluesky'   => ['label' => 'Bluesky',        'icon' => 'bi-bluesky',          'url_prefix' => 'https://bsky.app/profile/'],
        'pinterest' => ['label' => 'Pinterest',      'icon' => 'bi-pinterest',        'url_prefix' => 'https://pinterest.com/'],
        'spotify'   => ['label' => 'Spotify',        'icon' => 'bi-spotify',          'url_prefix' => ''],
        'patreon'   => ['label' => 'Patreon',        'icon' => 'bi-heart-fill',       'url_prefix' => 'https://patreon.com/'],
        'email'     => ['label' => 'Email',          'icon' => 'bi-envelope-fill',    'url_prefix' => 'mailto:'],
        'rss'       => ['label' => 'RSS Feed',       'icon' => 'bi-rss-fill',         'url_prefix' => ''],
        'website'   => ['label' => 'Website',        'icon' => 'bi-globe',            'url_prefix' => ''],
    ];
}

/**
 * Render Social Links
 * Pulls from social_links table and outputs icon anchor tags.
 * Call from any template: <?php echo render_social_links($db); ?>
 */
function render_social_links($db): string {
    try {
        $rows = $db->query(
            "SELECT platform, value FROM social_links ORDER BY sort_order ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return '';
    }

    if (empty($rows)) return '';

    $platforms = tf_social_platforms();
    $out = '<div class="footer-social">';
    foreach ($rows as $row) {
        $slug  = $row['platform'];
        $value = trim($row['value']);
        if (empty($value) || !isset($platforms[$slug])) continue;

        $p   = $platforms[$slug];
        $url = (str_starts_with($value, 'http') || str_starts_with($value, 'mailto:') || empty($p['url_prefix']))
             ? $value
             : $p['url_prefix'] . ltrim($value, '@/');

        $label = htmlspecialchars($p['label']);
        $icon  = htmlspecialchars($p['icon']);
        $href  = htmlspecialchars($url);
        $out  .= "<a href=\"{$href}\" aria-label=\"{$label}\" target=\"_blank\" rel=\"noopener noreferrer\">"
               . "<i class=\"bi {$icon}\"></i></a>";
    }
    $out .= '</div>';
    return $out;
}

/**
 * Render Footer Links
 * Pulls from footer_links table and outputs a <ul> of links.
 * Call from any template: <?php echo render_footer_links($db); ?>
 */
function render_footer_links($db): string {
    try {
        $rows = $db->query(
            "SELECT label, url FROM footer_links ORDER BY sort_order ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return '';
    }

    if (empty($rows)) return '';

    $out = '<ul class="footer-links">';
    foreach ($rows as $row) {
        $label = htmlspecialchars($row['label']);
        $url   = htmlspecialchars($row['url']);
        $out  .= "<li><a href=\"{$url}\">{$label}</a></li>";
    }
    $out .= '</ul>';
    return $out;
}
