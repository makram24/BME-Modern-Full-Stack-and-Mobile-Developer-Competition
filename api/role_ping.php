<?php
declare(strict_types=1);

/**
 * Authenticated JSON example: same auth rules as HTML pages; JSON body shape { ok, error?, role? }.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/includes/security.php';

portal_ensure_session_started();

if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not signed in.', 'role' => null]);
    exit;
}

$role = isset($_SESSION['role']) ? (string) $_SESSION['role'] : null;
echo json_encode(['ok' => true, 'error' => null, 'role' => $role]);
