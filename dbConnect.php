<?php

require_once __DIR__ . '/includes/logger.php';

$mysqli = new mysqli('localhost', 'root', '', 'bme_comp');

if ($mysqli->connect_errno) {
    portal_log('MySQL connection failed', [
        'errno' => $mysqli->connect_errno,
        'error' => $mysqli->connect_error,
    ]);
    if (!PORTAL_DEBUG) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Service unavailable</title>';
        echo '<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">';
        echo '</head><body class="bg-light p-4"><div class="container" style="max-width:36rem">';
        echo '<h1 class="h4">Service temporarily unavailable</h1>';
        echo '<p class="text-muted">We could not reach the database. Please try again in a few minutes.</p>';
        echo '</div></body></html>';
        exit;
    }
    exit('Failed to connect to MySQL: ' . htmlspecialchars($mysqli->connect_error, ENT_QUOTES, 'UTF-8'));
}

if (!$mysqli->set_charset('utf8mb4')) {
    portal_log('mysqli set_charset utf8mb4 failed', ['error' => $mysqli->error]);
}
