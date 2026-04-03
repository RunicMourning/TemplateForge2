<?php
/**
 * Theme Switcher — Public AJAX Endpoint
 * Accepts POST { theme: string } and saves to settings DB.
 * Returns JSON { success: bool, theme: string }
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$allowed_themes = ['broadsheet', 'inkwell', 'blueprint', 'fieldnotes', 'terminal', 'magazine'];
$theme = trim($_POST['theme'] ?? '');

if (!in_array($theme, $allowed_themes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid theme']);
    exit;
}

$db_path = __DIR__ . '/db/cms.db';
if (!file_exists($db_path)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare(
        "INSERT INTO settings (key, value) VALUES ('active_theme', ?)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value"
    );
    $stmt->execute([$theme]);
    echo json_encode(['success' => true, 'theme' => $theme]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
