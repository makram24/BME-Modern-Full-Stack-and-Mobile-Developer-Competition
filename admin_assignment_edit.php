<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';
require_once __DIR__ . '/includes/timetable_format.php';
require_once __DIR__ . '/includes/timetable_slots.php';

$id = (int) ($_GET['id'] ?? $_POST['assignment_id'] ?? 0);
if ($id <= 0) {
    admin_redirect('admin_assignments.php', 'error=' . rawurlencode('Invalid assignment.'));
}

$stmt = $mysqli->prepare(
    'SELECT csa.id, csa.academic_year_id, csa.class_id, csa.subject_id, csa.teacher_user_id,
            ay.label AS year_label, c.class_code, c.display_name, s.title AS subject_title, u.username AS teacher_username
     FROM class_subject_assignments csa
     INNER JOIN academic_years ay ON ay.id = csa.academic_year_id
     INNER JOIN classes c ON c.id = csa.class_id
     INNER JOIN subjects s ON s.id = csa.subject_id
     INNER JOIN users u ON u.id = csa.teacher_user_id
     WHERE csa.id = ? LIMIT 1'
);
if ($stmt === false) {
    admin_redirect('admin_assignments.php', 'error=' . rawurlencode('Database error.'));
}
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row === null) {
    admin_redirect('admin_assignments.php', 'error=' . rawurlencode('Assignment not found.'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&error=' . rawurlencode('Invalid session token.'));
    }
    $form = (string) ($_POST['form'] ?? 'teacher');

    if ($form === 'delete_slot') {
        $slotRowId = (int) ($_POST['slot_id'] ?? 0);
        if ($slotRowId > 0) {
            timetable_slots_delete($mysqli, $slotRowId, $id);
        }
        csrf_rotate();
        admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&saved=1');
    }

    if ($form === 'add_slot') {
        $tday = timetable_day_sanitize($_POST['timetable_day'] ?? 0);
        $tslot = timetable_slot_sanitize($_POST['timetable_slot'] ?? 0);
        if ($tday === null || $tslot === null) {
            admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&error=' . rawurlencode('Choose a valid weekday and lesson period.'));
        }
        $err = timetable_slots_add($mysqli, $id, $tday, $tslot);
        if ($err !== null) {
            admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&error=' . rawurlencode($err));
        }
        csrf_rotate();
        admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&saved=1');
    }

    // teacher
    $teacherId = (int) ($_POST['teacher_user_id'] ?? 0);
    if ($teacherId <= 0) {
        admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&error=' . rawurlencode('Teacher is required.'));
    }
    $chk = $mysqli->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $chk->bind_param('i', $teacherId);
    $chk->execute();
    $tr = $chk->get_result()->fetch_assoc();
    $chk->close();
    if (($tr['role'] ?? '') !== 'teacher') {
        admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&error=' . rawurlencode('Selected user must have role teacher.'));
    }

    $up = $mysqli->prepare(
        'UPDATE class_subject_assignments SET teacher_user_id = ? WHERE id = ?'
    );
    if ($up) {
        $up->bind_param('ii', $teacherId, $id);
        $up->execute();
        $up->close();
    }
    csrf_rotate();
    admin_redirect('admin_assignment_edit.php', 'id=' . $id . '&saved=1');
}

$teachers = $mysqli->query("SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username");
$teacherOpts = $teachers ? $teachers->fetch_all(MYSQLI_ASSOC) : [];
$slots = timetable_slots_list($mysqli, $id);

$shell_title = 'Edit assignment';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-8">
    <h1 class="h3 mb-3">Edit assignment</h1>
    <p class="small"><a href="admin_assignments.php">← All assignments</a></p>
    <p class="text-muted small">
      <?php echo htmlspecialchars((string) $row['year_label'], ENT_QUOTES, 'UTF-8'); ?>
      · Class <strong><?php echo htmlspecialchars((string) ($row['display_name'] ?: $row['class_code']), ENT_QUOTES, 'UTF-8'); ?></strong>
      · Subject <strong><?php echo htmlspecialchars((string) $row['subject_title'], ENT_QUOTES, 'UTF-8'); ?></strong>
    </p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm mb-3">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="assignment_id" value="<?php echo (int) $row['id']; ?>">
      <input type="hidden" name="form" value="teacher">

      <div class="mb-3">
        <label class="form-label">Teacher</label>
        <select class="form-select" name="teacher_user_id" required>
          <?php foreach ($teacherOpts as $t): ?>
            <option value="<?php echo (int) $t['id']; ?>"<?php echo (int) $t['id'] === (int) $row['teacher_user_id'] ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars((string) $t['username'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Save teacher</button>
    </form>

    <div class="card shadow-sm mb-3">
      <div class="card-header">Timetable periods</div>
      <div class="card-body">
        <p class="small text-muted mb-3">Add as many <strong>weekday + period</strong> rows as needed (e.g. same subject Monday period 1 and Monday period 5). Each row must be unique for this assignment.</p>
        <?php if ($slots === []): ?>
          <p class="small text-muted mb-3">No periods yet — students and teachers will see “—” until you add at least one.</p>
        <?php else: ?>
          <ul class="list-group list-group-flush mb-3 border rounded">
            <?php foreach ($slots as $sl): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span class="small"><?php echo htmlspecialchars(timetable_summary($sl['timetable_day'], $sl['timetable_slot']), ENT_QUOTES, 'UTF-8'); ?></span>
                <form method="post" class="mb-0" onsubmit="return confirm('Remove this period from the timetable?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="assignment_id" value="<?php echo (int) $row['id']; ?>">
                  <input type="hidden" name="form" value="delete_slot">
                  <input type="hidden" name="slot_id" value="<?php echo (int) $sl['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="assignment_id" value="<?php echo (int) $row['id']; ?>">
          <input type="hidden" name="form" value="add_slot">
          <div class="col-md-5">
            <label class="form-label small">Weekday</label>
            <select class="form-select form-select-sm" name="timetable_day" required>
              <?php foreach (timetable_weekday_options() as $dv => $label): ?>
                <option value="<?php echo (int) $dv; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small">Lesson period</label>
            <select class="form-select form-select-sm" name="timetable_slot" required>
              <?php for ($s = TIMETABLE_SLOT_MIN; $s <= TIMETABLE_SLOT_MAX; $s++): ?>
                <option value="<?php echo $s; ?>"><?php echo htmlspecialchars(timetable_slot_label($s), ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-sm btn-primary">Add period</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
