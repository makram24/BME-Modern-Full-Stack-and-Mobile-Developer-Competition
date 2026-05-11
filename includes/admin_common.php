<?php
declare(strict_types=1);

/**
 * Phase 6 — school administrator area (navigation only; logic lives in each admin_*.php).
 */

function admin_portal_nav(): array
{
    return [
        ['admin_dashboard.php', 'Home'],
        ['admin_users.php', 'Users'],
        ['admin_classes.php', 'Classes'],
        ['admin_subjects.php', 'Subjects'],
        ['admin_enrollments.php', 'Enrolments'],
        ['admin_assignments.php', 'Assignments'],
        ['admin_events.php', 'Events'],
        ['logout.php', 'Log out'],
    ];
}

/**
 * @return array<int, array{0: string, 1: string}>
 */
function admin_portal_nav_items(): array
{
    return admin_portal_nav();
}

function admin_redirect(string $path, string $query = ''): void
{
    $q = $query !== '' ? ('?' . ltrim($query, '?')) : '';
    header('Location: ' . $path . $q);
    exit;
}
