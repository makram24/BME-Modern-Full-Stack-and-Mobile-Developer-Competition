<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('super_administrator');

require_once __DIR__ . '/includes/superadmin_common.php';

$shell_title = 'Super administrator';
$shell_nav_items = superadmin_portal_nav_items();

ob_start();
?>
<div class="portal-page-head">
  <h1>Super administrator</h1>
  <p class="text-muted">Manage privileged accounts. School admins cannot access these screens. Regular users are edited here only when you need full control (role, activation, password).</p>
</div>

<div class="portal-tiles">
  <a class="portal-tile portal-tile--danger" href="superadmin_users.php">
    <i class="bi bi-shield-lock portal-tile__icon" aria-hidden="true"></i>
    <span class="portal-tile__label">Privileged users</span>
    <span class="portal-tile__hint">Administrators and super-admins; role and password changes.</span>
  </a>
  <a class="portal-tile" href="events.php">
    <i class="bi bi-calendar-event portal-tile__icon" aria-hidden="true"></i>
    <span class="portal-tile__label">School events</span>
    <span class="portal-tile__hint">Read-only campus calendar (school admins manage content).</span>
  </a>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
