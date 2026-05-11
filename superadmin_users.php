<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('super_administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/superadmin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_privileged') {
    if (!csrf_verify_post()) {
        superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('Invalid session token.'));
    }
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = trim((string) ($_POST['role'] ?? ''));
    if (!in_array($role, ['administrator', 'super_administrator'], true)) {
        superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('Role must be administrator or super-administrator.'));
    }
    if ($username === '' || $password === '') {
        superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('Username and password are required.'));
    }
    if (strlen($username) < 3 || strlen($username) > 64 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('Invalid username format.'));
    }
    if (strlen($password) < 8) {
        superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('Password must be at least 8 characters.'));
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('INSERT INTO users (username, password, role, is_active) VALUES (?,?,?,1)');
    if ($stmt) {
        $stmt->bind_param('sss', $username, $hash, $role);
        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                $stmt->close();
                superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('Username already taken.'));
            }
        }
        $stmt->close();
    }
    csrf_rotate();
    superadmin_redirect('superadmin_users.php', 'saved=1');
}

$res = $mysqli->query('SELECT id, username, role, is_active, created_at FROM users ORDER BY FIELD(role, \'super_administrator\', \'administrator\', \'teacher\', \'student\'), username ASC');
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$shell_title = 'Privileged users';
$shell_nav_items = superadmin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <h1 class="h3 mb-3">Administrators &amp; super-administrators</h1>
    <p class="text-muted small">Only this area (super-admin session) can <strong>create</strong> school administrators and super-administrators. School admins cannot open these URLs.</p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4 border-warning">
      <div class="card-header bg-warning bg-opacity-25">Create administrator or super-administrator</div>
      <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="create_privileged">
          <div class="col-md-3">
            <label class="form-label small">Username</label>
            <input class="form-control form-control-sm" name="username" required pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="64">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Password</label>
            <input class="form-control form-control-sm" type="password" name="password" required minlength="8">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Role</label>
            <select class="form-select form-select-sm" name="role" required>
              <option value="administrator">School administrator</option>
              <option value="super_administrator">Super administrator</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-warning">Create</button>
          </div>
        </form>
      </div>
    </div>

    <h2 class="h6 text-muted">All accounts (edit any)</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Active</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo (int) $u['id']; ?></td>
              <td><?php echo htmlspecialchars((string) $u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td>
                <?php if ($u['role'] === 'super_administrator'): ?>
                  <span class="badge text-bg-danger">super</span>
                <?php elseif ($u['role'] === 'administrator'): ?>
                  <span class="badge text-bg-primary">admin</span>
                <?php else: ?>
                  <?php echo htmlspecialchars((string) $u['role'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </td>
              <td><?php echo (int) $u['is_active'] ? 'Yes' : 'No'; ?></td>
              <td><a class="btn btn-sm btn-outline-secondary" href="superadmin_user_edit.php?id=<?php echo (int) $u['id']; ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
