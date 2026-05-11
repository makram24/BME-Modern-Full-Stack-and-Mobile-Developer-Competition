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
        admin_redirect('admin_enrollments.php', 'error=' . rawurlencode('Invalid session token.'));
    }
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $yearId = (int) ($_POST['academic_year_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        if ($uid <= 0 || $yearId <= 0 || $classId <= 0) {
            admin_redirect('admin_enrollments.php', 'error=' . rawurlencode('Pick student, year, and class.'));
        }
        $roleChk = $mysqli->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $roleChk->bind_param('i', $uid);
        $roleChk->execute();
        $ur = $roleChk->get_result()->fetch_assoc();
        $roleChk->close();
        if (($ur['role'] ?? '') !== 'student') {
            admin_redirect('admin_enrollments.php', 'error=' . rawurlencode('Only student accounts can be enrolled as students.'));
        }
        $stmt = $mysqli->prepare(
            'INSERT INTO class_enrollments (user_id, class_id, academic_year_id) VALUES (?,?,?)'
        );
        if ($stmt) {
            $stmt->bind_param('iii', $uid, $classId, $yearId);
            if (!$stmt->execute()) {
                if ($stmt->errno === 1062) {
                    $stmt->close();
                    admin_redirect('admin_enrollments.php', 'error=' . rawurlencode('That student is already enrolled for this year.'));
                }
            }
            $stmt->close();
        }
        csrf_rotate();
        admin_redirect('admin_enrollments.php', 'saved=1');
    }

    if ($action === 'delete') {
        $eid = (int) ($_POST['enrollment_id'] ?? 0);
        if ($eid > 0) {
            $stmt = $mysqli->prepare('DELETE FROM class_enrollments WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $eid);
                $stmt->execute();
                $stmt->close();
            }
        }
        csrf_rotate();
        admin_redirect('admin_enrollments.php', 'saved=1');
    }
}

$students = $mysqli->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username");
$studentOpts = $students ? $students->fetch_all(MYSQLI_ASSOC) : [];

$years = $mysqli->query('SELECT id, label FROM academic_years ORDER BY date_end DESC');
$yearOpts = $years ? $years->fetch_all(MYSQLI_ASSOC) : [];

$classes = $mysqli->query('SELECT id, class_code, start_date, display_name FROM classes ORDER BY start_date DESC');
$classOpts = $classes ? $classes->fetch_all(MYSQLI_ASSOC) : [];

$list = $mysqli->query(
    'SELECT ce.id AS enrollment_id, u.username, ay.label AS year_label, c.class_code, c.start_date
     FROM class_enrollments ce
     INNER JOIN users u ON u.id = ce.user_id
     INNER JOIN academic_years ay ON ay.id = ce.academic_year_id
     INNER JOIN classes c ON c.id = ce.class_id
     ORDER BY ay.date_end DESC, u.username ASC'
);
$enrollments = $list ? $list->fetch_all(MYSQLI_ASSOC) : [];

$shell_title = 'Enrolments';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <h1 class="h3 mb-3">Class enrolments</h1>
    <p class="text-muted small">Put a <strong>student</strong> into a <strong>class</strong> for an <strong>academic year</strong> (one class per student per year).</p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header">Enrol student</div>
      <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="create">
          <div class="col-md-3">
            <label class="form-label small">Student</label>
            <select class="form-select form-select-sm" name="user_id" required>
              <option value="">—</option>
              <?php foreach ($studentOpts as $s): ?>
                <option value="<?php echo (int) $s['id']; ?>"><?php echo htmlspecialchars((string) $s['username'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Academic year</label>
            <select class="form-select form-select-sm" name="academic_year_id" required>
              <?php foreach ($yearOpts as $y): ?>
                <option value="<?php echo (int) $y['id']; ?>"><?php echo htmlspecialchars((string) $y['label'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Class</label>
            <select class="form-select form-select-sm" name="class_id" required>
              <?php foreach ($classOpts as $c): ?>
                <option value="<?php echo (int) $c['id']; ?>">
                  <?php echo htmlspecialchars((string) ($c['display_name'] ?: $c['class_code']), ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary">Enrol</button>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>Student</th><th>Year</th><th>Class</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($enrollments as $e): ?>
            <tr>
              <td><?php echo htmlspecialchars((string) $e['username'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) $e['year_label'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) $e['class_code'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td>
                <form method="post" class="d-inline" onsubmit="return confirm('Remove this enrolment?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="enrollment_id" value="<?php echo (int) $e['enrollment_id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
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
