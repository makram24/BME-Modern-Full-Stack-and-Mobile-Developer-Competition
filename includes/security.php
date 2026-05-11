<?php
declare(strict_types=1);

require_once __DIR__ . '/session_bootstrap.php';

/**
 * Safe post-login redirect target: same-folder PHP files only (no open redirects).
 */
function sanitize_next(?string $next): string
{
    if ($next === null || $next === '') {
        return 'dashboard.php';
    }
    $next = trim($next);
    if (strlen($next) > 128 || str_contains($next, '..') || str_contains($next, "\0")) {
        return 'dashboard.php';
    }
    if (!preg_match('/^[A-Za-z0-9_-]+\.php$/', $next)) {
        return 'dashboard.php';
    }

    return $next;
}

/**
 * Page that triggered the login redirect (basename of current script).
 */
function resolve_login_redirect_target(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = $script !== '' ? basename($script) : 'dashboard.php';

    return sanitize_next($base);
}

function csrf_token(): string
{
    portal_ensure_session_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_verify_post(): bool
{
    portal_ensure_session_started();
    $sent = $_POST['csrf_token'] ?? '';
    if ($sent === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], (string) $sent);
}

function csrf_rotate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
