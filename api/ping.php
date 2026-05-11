<?php
declare(strict_types=1);

/**
 * Public JSON health check — consistent { ok, error? } shape for API-style endpoints.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['ok' => true, 'error' => null]);
