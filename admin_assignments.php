<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';
require_once __DIR__ . '/includes/timetable_slots.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upsert') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_assignments.php', 'error=' . rawurlencode('Invalid session token.'));
    }
    $yearId = (int) ($_POST['academic_year_id'] ?? 0);
    $classId = (int) ($_POST['class_id'] ?? 0);
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $teacherId = (int) ($_POST['teacher_user_id'] ?? 0);
    if ($yearId <= 0 || $classId <= 0 || $subjectId <= 0 || $teacherId <= 0) {
        admin_redirect('admin_assignments.php', 'error=' . rawurlencode('All fields are required.'));
    }
    $chk = $mysqli->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $chk->bind_param('i', $teacherId);
    $chk->execute();
    $tr = $chk->get_result()->fetch_assoc();
    $chk->close();
    if (($tr['role'] ?? '') !== 'teacher') {
        admin_redirect('admin_assignments.php', 'error=' . rawurlencode('Assigned teacher must be a user with role teacher.'));
    }

    $stmt = $mysqli->prepare(
        'INSERT INTO class_subject_assignments (academic_year_id, class_id, subject_id, teacher_user_id, timetable_day, timetable_slot)
         VALUES (?,?,?,?,0,0)
         ON DUPLICATE KEY UPDATE teacher_user_id = VALUES(teacher_user_id)'
    );
    if ($stmt) {
        $stmt->bind_param('iiii', $yearId, $classId, $subjectId, $teacherId);
        $stmt->execute();
        $stmt->close();
    }
    csrf_rotate();
    admin_redirect('admin_assignments.php', 'saved=1');
}

$years = $mysqli->query('SELECT id, label FROM academic_years ORDER BY date_end DESC');
$yearOpts = $years ? $years->fetch_all(MYSQLI_ASSOC) : [];

$classes = $mysqli->query('SELECT id, class_code, start_date, display_name FROM classes ORDER BY start_date DESC');
$classOpts = $classes ? $classes->fetch_all(MYSQLI_ASSOC) : [];

$subjects = $mysqli->query('SELECT id, title FROM subjects ORDER BY title ASC');
$subjectOpts = $subjects ? $subjects->fetch_all(MYSQLI_ASSOC) : [];

$teachers = $mysqli->query("SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username");
$teacherOpts = $teachers ? $teachers->fetch_all(MYSQLI_ASSOC) : [];

$list = $mysqli->query(
    'SELECT csa.id AS assignment_id, ay.label AS year_label, c.class_code, s.title AS subject_title, u.username AS teacher_username
     FROM class_subject_assignments csa
     INNER JOIN academic_years ay ON ay.id = csa.academic_year_id
     INNER JOIN classes c ON c.id = csa.class_id
     INNER JOIN subjects s ON s.id = csa.subject_id
     INNER JOIN users u ON u.id = csa.teacher_user_id
     ORDER BY ay.date_end DESC, c.class_code, s.title'
);
$assignRows = $list ? $list->fetch_all(MYSQLI_ASSOC) : [];
$assignmentIds = array_map(static fn (array $a): int => (int) $a['assignment_id'], $assignRows);
$slotsByAssignment = timetable_slots_batch($mysqli, $assignmentIds);

$shell_title = 'Assignments';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <h1 class="h3 mb-3">Subject → class assignments</h1>
    <p class="text-muted small">For each <strong>year + class + subject</strong>, set the <strong>teacher</strong>. Saving again updates the row if it already exists (same year, class, subject). Use <strong>Edit</strong> to add one or more timetable periods (same day twice is allowed).</p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header">Assign or update teacher</div>
      <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="upsert">
          <div class="col-md-2">
            <label class="form-label small">Year</label>
            <select class="form-select form-select-sm" name="academic_year_id" required>
              <?php foreach ($yearOpts as $y): ?>
                <option value="<?php echo (int) $y['id']; ?>"><?php echo htmlspecialchars((string) $y['label'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Class</label>
            <select class="form-select form-select-sm" name="class_id" required>
              <?php foreach ($classOpts as $c): ?>
                <option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars((string) ($c['display_name'] ?: $c['class_code']), ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Subject</label>
            <select class="form-select form-select-sm" name="subject_id" required>
              <?php foreach ($subjectOpts as $s): ?>
                <option value="<?php echo (int) $s['id']; ?>"><?php echo htmlspecialchars((string) $s['title'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Teacher</label>
            <select class="form-select form-select-sm" name="teacher_user_id" required>
              <?php foreach ($teacherOpts as $t): ?>
                <option value="<?php echo (int) $t['id']; ?>"><?php echo htmlspecialchars((string) $t['username'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-sm btn-primary">Save assignment</button>
          </div>
        </form>
      </div>
    </div>

    <h2 class="h6 text-muted">Current rows</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>Year</th><th>Class</th><th>Subject</th><th>Teacher</th><th>Timetable</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($assignRows as $a): ?>
            <tr>
              <td><?php echo htmlspecialchars((string) $a['year_label'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) $a['class_code'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) $a['subject_title'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) $a['teacher_username'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="small"><?php echo htmlspecialchars(timetable_slots_summary_string($slotsByAssignment[(int) $a['assignment_id']] ?? []), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><a class="btn btn-sm btn-outline-secondary" href="admin_assignment_edit.php?id=<?php echo (int) $a['assignment_id']; ?>">Edit</a></td>
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
