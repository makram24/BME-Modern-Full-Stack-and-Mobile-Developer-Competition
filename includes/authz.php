<?php
declare(strict_types=1);

/**
 * Phase 3 — server-side authorization. Include after require_login.php on protected pages.
 */

function current_user_id(): ?int
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}

function current_username(): ?string
{
    return isset($_SESSION['username']) ? (string) $_SESSION['username'] : null;
}

function current_role(): ?string
{
    return isset($_SESSION['role']) ? (string) $_SESSION['role'] : null;
}

/** URL of the role-specific home (used after 403 and in nav). */
function role_home_url(?string $role = null): string
{
    $r = $role ?? current_role() ?? 'student';

    switch ($r) {
        case 'teacher':
            return 'teacher_dashboard.php';
        case 'administrator':
            return 'admin_dashboard.php';
        case 'super_administrator':
            return 'superadmin_dashboard.php';
        default:
            return 'student_dashboard.php';
    }
}

/**
 * Abort unless the logged-in user has one of the given roles (exact match to session / DB ENUM).
 */
function require_roles(string ...$allowed): void
{
    if (empty($_SESSION['logged_in']) || current_user_id() === null) {
        header('Location: index.php');
        exit;
    }

    $role = current_role();
    if ($role === null || !in_array($role, $allowed, true)) {
        exit_forbidden();
    }
}

function exit_forbidden(string $message = 'You do not have permission to view this page.'): void
{
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $home = htmlspecialchars(role_home_url(), ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>403 Forbidden</title>';
    echo '<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">';
    echo '<div class="container py-5"><h1 class="h4 text-danger">403 Forbidden</h1><p>' . $safe . '</p>';
    echo '<p><a class="btn btn-primary" href="' . $home . '">Return to your dashboard</a></p></div></body></html>';
    exit;
}

/**
 * Whether an administrator may act on a user with the given role (super-admins are off-limits to school admins).
 */
function admin_can_manage_user_role(?string $targetUserRole): bool
{
    $me = current_role();
    if ($me === 'super_administrator') {
        return true;
    }
    if ($me !== 'administrator') {
        return false;
    }

    return $targetUserRole !== 'super_administrator';
}

/**
 * Teacher-only: whether this teacher is the one allowed to edit grades for an assignment.
 * Phase 5 should verify $assignmentId exists and teacher_user_id matches in the database.
 */
function can_edit_grade(int $teacherUserId, int $assignmentId): bool
{
    unset($assignmentId);
    if (current_role() !== 'teacher' || current_user_id() !== $teacherUserId) {
        return false;
    }

    return true;
}
