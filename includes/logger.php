<?php
declare(strict_types=1);

/**
 * Server-side logging only (never expose raw DB/SQL details to browsers in production).
 */

if (!defined('PORTAL_DEBUG')) {
    define('PORTAL_DEBUG', (getenv('PORTAL_DEBUG') ?: '') === '1');
}

/**
 * @param array<string, mixed> $context
 */
function portal_log(string $message, array $context = []): void
{
    $line = '[' . gmdate('Y-m-d\TH:i:s\Z') . '] ' . $message;
    if ($context !== []) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    error_log($line);
}
