<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('super_administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/superadmin_common.php';

$id = (int) ($_GET['id'] ?? $_POST['user_id'] ?? 0);
if ($id <= 0) {
    superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('Invalid user.'));
}

$stmt = $mysqli->prepare('SELECT id, username, role, is_active FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($user === null) {
    superadmin_redirect('superadmin_users.php', 'error=' . rawurlencode('User not found.'));
}

$allowedRoles = ['student', 'teacher', 'administrator', 'super_administrator'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        superadmin_redirect('superadmin_user_edit.php', 'id=' . $id . '&error=' . rawurlencode('Invalid session token.'));
    }

    $password = (string) ($_POST['password'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $role = trim((string) ($_POST['role'] ?? ''));
    if (!in_array($role, $allowedRoles, true)) {
        superadmin_redirect('superadmin_user_edit.php', 'id=' . $id . '&error=' . rawurlencode('Invalid role.'));
    }

    if ($password !== '') {
        if (strlen($password) < 8) {
            superadmin_redirect('superadmin_user_edit.php', 'id=' . $id . '&error=' . rawurlencode('Password must be at least 8 characters.'));
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('UPDATE users SET password = ?, is_active = ?, role = ? WHERE id = ?');
        $stmt->bind_param('sisi', $hash, $isActive, $role, $id);
    } else {
        $stmt = $mysqli->prepare('UPDATE users SET is_active = ?, role = ? WHERE id = ?');
        $stmt->bind_param('isi', $isActive, $role, $id);
    }
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }
    csrf_rotate();
    superadmin_redirect('superadmin_user_edit.php', 'id=' . $id . '&saved=1');
}

$shell_title = 'Edit user';
$shell_nav_items = superadmin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-6">
    <h1 class="h3 mb-3">Edit user (super-admin)</h1>
    <p class="small"><a href="superadmin_users.php">← Privileged users</a></p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <p><strong>Username:</strong> <?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-muted small">(change only via database if needed)</span></p>

    <form method="post" class="card card-body shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">

      <div class="mb-3">
        <label class="form-label">Role</label>
        <select class="form-select" name="role" required>
          <?php foreach ($allowedRoles as $r): ?>
            <option value="<?php echo htmlspecialchars($r, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $user['role'] === $r ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars($r, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="form-text small text-muted">School administrators cannot assign <code>super_administrator</code> in their own UI (Phase 7.2).</p>
      </div>
      <div class="mb-3 form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"<?php echo (int) $user['is_active'] ? ' checked' : ''; ?>>
        <label class="form-check-label" for="is_active">Account active</label>
      </div>
      <div class="mb-3">
        <label class="form-label">New password <span class="text-muted small">(optional)</span></label>
        <input class="form-control" type="password" name="password" minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
