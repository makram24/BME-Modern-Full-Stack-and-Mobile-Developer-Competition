<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('student');

$shell_title = 'Student';
$shell_nav_items = [
    ['student_dashboard.php', 'Home'],
    ['student_record.php', 'My profile'],
    ['logout.php', 'Log out'],
];
$shell_body_html = <<<'HTML'
<div class="row">
  <div class="col-lg-8">
    <h1 class="h3 mb-3">Student workspace</h1>
    <p class="text-muted">This area is only available to accounts with the <strong>student</strong> role (enforced on the server).</p>
    <p><a class="btn btn-sm btn-outline-primary" href="student_record.php">My profile</a> <span class="text-muted small">(only your own user id is allowed — IDOR guard)</span></p>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 card-title">Coming next (Phase 4)</h2>
        <p class="card-text small mb-0">Subjects for your class for the current year, and your grades — read-only.</p>
      </div>
    </div>
  </div>
</div>
HTML;

require __DIR__ . '/includes/dashboard_shell.php';
