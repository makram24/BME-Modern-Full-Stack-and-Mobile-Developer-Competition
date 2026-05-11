<?php
declare(strict_types=1);

/**
 * Phase 7 — super-administrator navigation (only routes using require_roles('super_administrator')).
 */

function superadmin_portal_nav(): array
{
    return [
        ['superadmin_dashboard.php', 'Home'],
        ['superadmin_users.php', 'Privileged users'],
        ['logout.php', 'Log out'],
    ];
}

/**
 * @return array<int, array{0: string, 1: string}>
 */
function superadmin_portal_nav_items(): array
{
    return superadmin_portal_nav();
}

function superadmin_redirect(string $path, string $query = ''): void
{
    $q = $query !== '' ? ('?' . ltrim($query, '?')) : '';
    header('Location: ' . $path . $q);
    exit;
}
