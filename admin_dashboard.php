<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

$shell_title = 'Administrator';
$shell_nav_items = [
    ['admin_dashboard.php', 'Home'],
    ['logout.php', 'Log out'],
];
$shell_body_html = <<<'HTML'
<div class="row">
  <div class="col-lg-8">
    <h1 class="h3 mb-3">School administrator</h1>
    <p class="text-muted">Manage students and teachers, subjects, and class assignments for each year. You cannot manage <strong>super-administrator</strong> accounts (that stays with super-admins).</p>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 card-title">Helper in code</h2>
        <p class="card-text small mb-0"><code>admin_can_manage_user_role($role)</code> returns false when a school admin targets a super-admin — use before any user update/delete endpoint.</p>
      </div>
    </div>
  </div>
</div>
HTML;

require __DIR__ . '/includes/dashboard_shell.php';
