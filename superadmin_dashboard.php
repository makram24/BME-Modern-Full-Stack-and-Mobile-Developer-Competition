<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('super_administrator');

$shell_title = 'Super administrator';
$shell_nav_items = [
    ['superadmin_dashboard.php', 'Home'],
    ['logout.php', 'Log out'],
];
$shell_body_html = <<<'HTML'
<div class="row">
  <div class="col-lg-8">
    <h1 class="h3 mb-3">Super administrator</h1>
    <p class="text-muted">Full control over <strong>administrator</strong> and <strong>super-administrator</strong> accounts. Regular school admins cannot open this page.</p>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 card-title">Coming next</h2>
        <p class="card-text small mb-0">Promote/demote admins, audit sensitive actions.</p>
      </div>
    </div>
  </div>
</div>
HTML;

require __DIR__ . '/includes/dashboard_shell.php';
