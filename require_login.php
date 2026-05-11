<?php
declare(strict_types=1);

/**
 * Include at the top of protected pages (after optional ob_start).
 * Redirects guests to sign-in with ?next=<current script>, validated in auth.php / index.php.
 */
require_once __DIR__ . '/includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
    return;
}

$target = resolve_login_redirect_target();
header('Location: index.php?next=' . rawurlencode($target));
exit;
