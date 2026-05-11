<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';

$id = (int) ($_GET['id'] ?? $_POST['class_id'] ?? 0);
if ($id <= 0) {
    admin_redirect('admin_classes.php', 'error=' . rawurlencode('Invalid class.'));
}

$stmt = $mysqli->prepare('SELECT id, start_date, class_code, display_name FROM classes WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($class === null) {
    admin_redirect('admin_classes.php', 'error=' . rawurlencode('Class not found.'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_class_edit.php', 'id=' . $id . '&error=' . rawurlencode('Invalid session token.'));
    }
    $start = trim((string) ($_POST['start_date'] ?? ''));
    $code = trim((string) ($_POST['class_code'] ?? ''));
    $display = trim((string) ($_POST['display_name'] ?? ''));
    if ($start === '' || $code === '') {
        admin_redirect('admin_class_edit.php', 'id=' . $id . '&error=' . rawurlencode('Start date and class code are required.'));
    }
    $displayVal = $display === '' ? null : $display;
    $stmt = $mysqli->prepare('UPDATE classes SET start_date = ?, class_code = ?, display_name = ? WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('sssi', $start, $code, $displayVal, $id);
        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                $stmt->close();
                admin_redirect('admin_class_edit.php', 'id=' . $id . '&error=' . rawurlencode('Another class already uses that start date + code.'));
            }
        }
        $stmt->close();
    }
    csrf_rotate();
    admin_redirect('admin_class_edit.php', 'id=' . $id . '&saved=1');
}

$shell_title = 'Edit class';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-6">
    <h1 class="h3 mb-3">Edit class</h1>
    <p class="small"><a href="admin_classes.php">← Classes</a></p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="class_id" value="<?php echo (int) $class['id']; ?>">

      <div class="mb-3">
        <label class="form-label">Start date</label>
        <input class="form-control" type="date" name="start_date" required value="<?php echo htmlspecialchars((string) $class['start_date'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Class code</label>
        <input class="form-control" name="class_code" required maxlength="32" value="<?php echo htmlspecialchars((string) $class['class_code'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Display name</label>
        <input class="form-control" name="display_name" maxlength="128" value="<?php echo htmlspecialchars((string) ($class['display_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
