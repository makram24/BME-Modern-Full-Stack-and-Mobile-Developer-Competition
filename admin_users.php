<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_users.php', 'error=' . rawurlencode('Invalid session token.'));
    }
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = trim((string) ($_POST['role'] ?? ''));

    if ($username === '' || $password === '' || !in_array($role, ['student', 'teacher'], true)) {
        admin_redirect('admin_users.php', 'error=' . rawurlencode('Username, password, and role (student or teacher) are required.'));
    }
    if (strlen($username) < 3 || strlen($username) > 64 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        admin_redirect('admin_users.php', 'error=' . rawurlencode('Invalid username format.'));
    }
    if (strlen($password) < 8) {
        admin_redirect('admin_users.php', 'error=' . rawurlencode('Password must be at least 8 characters.'));
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('INSERT INTO users (username, password, role, is_active) VALUES (?,?,?,1)');
    if ($stmt === false) {
        admin_redirect('admin_users.php', 'error=' . rawurlencode('Database error.'));
    }
    $stmt->bind_param('sss', $username, $hash, $role);
    if (!$stmt->execute()) {
        if ($stmt->errno === 1062) {
            $stmt->close();
            admin_redirect('admin_users.php', 'error=' . rawurlencode('Username already taken.'));
        }
        $stmt->close();
        admin_redirect('admin_users.php', 'error=' . rawurlencode('Could not create user.'));
    }
    $stmt->close();
    csrf_rotate();
    admin_redirect('admin_users.php', 'saved=1');
}

$shell_title = 'Users';
$shell_nav_items = admin_portal_nav_items();

$stmt = $mysqli->query(
    'SELECT id, username, role, is_active, created_at FROM users ORDER BY role ASC, username ASC'
);
$users = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <h1 class="h3 mb-3">Users</h1>
    <p class="text-muted small">School administrators create <strong>students</strong> and <strong>teachers</strong> here. Super-administrator accounts are not editable in this area.</p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header">Create student or teacher</div>
      <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="create">
          <div class="col-md-3">
            <label class="form-label small">Username</label>
            <input class="form-control form-control-sm" name="username" required pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="64">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Password</label>
            <input class="form-control form-control-sm" type="password" name="password" required minlength="8">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Role</label>
            <select class="form-select form-select-sm" name="role" required>
              <option value="student">Student</option>
              <option value="teacher">Teacher</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary">Create</button>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Active</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <?php
              $rid = (int) $u['id'];
              $canEdit = in_array($u['role'], ['student', 'teacher'], true);
            ?>
            <tr>
              <td><?php echo $rid; ?></td>
              <td><?php echo htmlspecialchars((string) $u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) $u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo (int) $u['is_active'] ? 'Yes' : 'No'; ?></td>
              <td>
                <?php if ($canEdit): ?>
                  <a class="btn btn-sm btn-outline-secondary" href="admin_user_edit.php?id=<?php echo $rid; ?>">Edit</a>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
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
