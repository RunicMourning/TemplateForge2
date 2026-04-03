<?php
/**
 * Theme Switcher — Public AJAX Endpoint
 * Validates theme against the dynamic registry (no hardcoded list).
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/includes/theme-registry.php';

$slug = trim($_POST['theme'] ?? '');

if (!tf_is_valid_theme($slug)) {
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
    $stmt->execute([$slug]);
    echo json_encode(['success' => true, 'theme' => $slug]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
