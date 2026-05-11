<?php
declare(strict_types=1);

/**
 * Entry point after login: sends each role to their own dashboard (server-enforced split).
 */
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';

$role = current_role() ?? 'student';

$targets = [
    'student' => 'student_dashboard.php',
    'teacher' => 'teacher_dashboard.php',
    'administrator' => 'admin_dashboard.php',
    'super_administrator' => 'superadmin_dashboard.php',
];

$dest = $targets[$role] ?? 'student_dashboard.php';
header('Location: ' . $dest);
exit;
