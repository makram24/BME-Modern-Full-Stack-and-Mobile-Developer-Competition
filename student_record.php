<?php
declare(strict_types=1);

/**
 * Example of server-side scope: a student may only view their own record (Phase 3 IDOR rule).
 * Extend this pattern to grade lists: compare route student_id to current_user_id().
 */
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('student');

require_once __DIR__ . '/includes/student_data.php';

$self = current_user_id();
$requested = isset($_GET['id']) ? (int) $_GET['id'] : $self;

if ($requested !== $self) {
    exit_forbidden('You can only view your own profile and results.');
}

$yearNav = isset($_GET['year_id']) ? (int) $_GET['year_id'] : 0;
$shell_title = 'My profile';
$shell_nav_items = student_portal_nav_links($yearNav);
$uid = (string) $self;
$uname = htmlspecialchars((string) (current_username() ?? ''), ENT_QUOTES, 'UTF-8');
$shell_body_html = <<<HTML
<div class="row">
  <div class="col-lg-6">
    <h1 class="h3 mb-3">My profile</h1>
    <dl class="row small">
      <dt class="col-sm-4">User ID</dt><dd class="col-sm-8">{$uid}</dd>
      <dt class="col-sm-4">Username</dt><dd class="col-sm-8">{$uname}</dd>
    </dl>
  </div>
</div>
HTML;

require __DIR__ . '/includes/dashboard_shell.php';
