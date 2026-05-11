<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_classes.php', 'error=' . rawurlencode('Invalid session token.'));
    }
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $start = trim((string) ($_POST['start_date'] ?? ''));
        $code = trim((string) ($_POST['class_code'] ?? ''));
        $display = trim((string) ($_POST['display_name'] ?? ''));
        if ($start === '' || $code === '') {
            admin_redirect('admin_classes.php', 'error=' . rawurlencode('Start date and class code are required.'));
        }
        $displayVal = $display === '' ? null : $display;
        $stmt = $mysqli->prepare('INSERT INTO classes (start_date, class_code, display_name) VALUES (?,?,?)');
        if ($stmt) {
            $stmt->bind_param('sss', $start, $code, $displayVal);
            if (!$stmt->execute()) {
                if ($stmt->errno === 1062) {
                    $stmt->close();
                    admin_redirect('admin_classes.php', 'error=' . rawurlencode('That start date + class code already exists.'));
                }
            }
            $stmt->close();
        }
        csrf_rotate();
        admin_redirect('admin_classes.php', 'saved=1');
    }

    if ($action === 'delete') {
        $cid = (int) ($_POST['class_id'] ?? 0);
        if ($cid > 0) {
            $stmt = $mysqli->prepare('DELETE FROM classes WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $cid);
                if (!$stmt->execute()) {
                    $stmt->close();
                    admin_redirect('admin_classes.php', 'error=' . rawurlencode('Cannot delete: class is still used by enrolments or assignments.'));
                }
                $stmt->close();
            }
        }
        csrf_rotate();
        admin_redirect('admin_classes.php', 'saved=1');
    }
}

$res = $mysqli->query('SELECT id, start_date, class_code, display_name, created_at FROM classes ORDER BY start_date DESC, class_code ASC');
$classes = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$shell_title = 'Classes';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <h1 class="h3 mb-3">Classes</h1>
    <p class="text-muted small">Identifier = <strong>start date</strong> + <strong>class code</strong> (e.g. 2009/C). Unique together.</p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header">Add class</div>
      <div class="card-body">
        <form method="post" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="create">
          <div class="col-md-3">
            <label class="form-label small">Start date</label>
            <input class="form-control form-control-sm" type="date" name="start_date" required>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Class code</label>
            <input class="form-control form-control-sm" name="class_code" required maxlength="32" placeholder="2009/C">
          </div>
          <div class="col-md-4">
            <label class="form-label small">Display name (optional)</label>
            <input class="form-control form-control-sm" name="display_name" maxlength="128">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-sm btn-primary">Add</button>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>ID</th><th>Start</th><th>Code</th><th>Display</th><th></th><th></th></tr></thead>
        <tbody>
          <?php foreach ($classes as $c): ?>
            <tr>
              <td><?php echo (int) $c['id']; ?></td>
              <td><?php echo htmlspecialchars((string) $c['start_date'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) $c['class_code'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) ($c['display_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="admin_class_edit.php?id=<?php echo (int) $c['id']; ?>">Edit</a></td>
              <td>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this class?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="class_id" value="<?php echo (int) $c['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
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
