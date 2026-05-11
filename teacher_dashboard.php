<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('teacher');

$shell_title = 'Teacher';
$shell_nav_items = [
    ['teacher_dashboard.php', 'Home'],
    ['logout.php', 'Log out'],
];
$shell_body_html = <<<'HTML'
<div class="row">
  <div class="col-lg-8">
    <h1 class="h3 mb-3">Teacher workspace</h1>
    <p class="text-muted">Only <strong>teacher</strong> accounts can open this URL. Other roles receive HTTP 403.</p>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 card-title">Coming next (Phase 5)</h2>
        <p class="card-text small mb-0">Assignments you teach this year, grade entry, and year-end grades — scoped to your classes only.</p>
      </div>
    </div>
  </div>
</div>
HTML;

require __DIR__ . '/includes/dashboard_shell.php';
