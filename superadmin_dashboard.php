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
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-3">Super administrator</h1>
    <p class="text-muted">Create and manage <strong>administrator</strong> and <strong>super-administrator</strong> accounts. You can also open any user and change role, activation, or password. Regular school admins receive <strong>403</strong> on these routes.</p>

    <div class="row g-3 mt-2">
      <div class="col-md-6">
        <div class="card shadow-sm h-100 border-danger">
          <div class="card-body">
            <h2 class="h5 card-title"><a href="superadmin_users.php">Privileged users</a></h2>
            <p class="card-text small text-muted mb-0">Create school admins or other super-admins, and edit any account (including role changes).</p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <h2 class="h5 card-title"><a href="events.php">School events</a></h2>
            <p class="card-text small text-muted mb-0">Read-only campus calendar (school administrators manage events).</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
