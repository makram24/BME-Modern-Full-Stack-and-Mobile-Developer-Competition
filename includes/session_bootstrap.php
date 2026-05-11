<?php
declare(strict_types=1);

/**
 * Session hardening (Phase 9.2). Must run before session_start().
 */
function portal_session_configure(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($https) {
        ini_set('session.cookie_secure', '1');
    }
}

function portal_ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    portal_session_configure();
    session_start();
}
