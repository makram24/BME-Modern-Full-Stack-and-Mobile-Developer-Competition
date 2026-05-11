<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('teacher');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/teacher_data.php';

$tid = (int) current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        http_response_code(403);
        exit('Invalid session token.');
    }

    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
    $yearForUrl = (int) ($_POST['year_id'] ?? 0);
    $assign = teacher_assignment_for_teacher($mysqli, $tid, $assignmentId);
    if ($assign === null) {
        exit_forbidden('You are not assigned to this class subject.');
    }

    $classId = (int) $assign['class_id'];
    $academicYearId = (int) $assign['academic_year_id'];
    $studentId = (int) ($_POST['student_user_id'] ?? 0);

    if (!teacher_student_in_class($mysqli, $classId, $academicYearId, $studentId)) {
        exit_forbidden('That student is not in this class for this year.');
    }

    $redirectYear = $yearForUrl > 0 ? $yearForUrl : $academicYearId;

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'periodic') {
        $label = trim((string) ($_POST['label'] ?? ''));
        if ($label === '') {
            $label = 'Grade entry';
        }
        $val = trim((string) ($_POST['grade_value'] ?? ''));
        if (!teacher_grade_value_valid($val)) {
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('Use a whole number from 1 to 5 only.'));
        }
        $gtype = 'periodic';
        $stmt = $mysqli->prepare(
            'INSERT INTO grades (class_subject_assignment_id, student_user_id, grade_value, grade_type, label, entered_by_user_id) VALUES (?,?,?,?,?,?)'
        );
        if ($stmt) {
            $stmt->bind_param('iisssi', $assignmentId, $studentId, $val, $gtype, $label, $tid);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'year_end') {
        $val = trim((string) ($_POST['grade_value'] ?? ''));
        if (!teacher_grade_value_valid($val)) {
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('Use a whole number from 1 to 5 only.'));
        }
        $gtype = 'year_end';
        $sel = $mysqli->prepare(
            'SELECT id FROM grades WHERE class_subject_assignment_id = ? AND student_user_id = ? AND grade_type = ? LIMIT 1'
        );
        $existingId = 0;
        if ($sel) {
            $sel->bind_param('iis', $assignmentId, $studentId, $gtype);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            $sel->close();
            if ($row) {
                $existingId = (int) $row['id'];
            }
        }
        if ($existingId > 0) {
            $up = $mysqli->prepare('UPDATE grades SET grade_value = ?, entered_by_user_id = ? WHERE id = ?');
            if ($up) {
                $up->bind_param('sii', $val, $tid, $existingId);
                $up->execute();
                $up->close();
            }
        } else {
            $ins = $mysqli->prepare(
                'INSERT INTO grades (class_subject_assignment_id, student_user_id, grade_value, grade_type, entered_by_user_id) VALUES (?,?,?,?,?)'
            );
            if ($ins) {
                $ins->bind_param('iissi', $assignmentId, $studentId, $val, $gtype, $tid);
                $ins->execute();
                $ins->close();
            }
        }
    }

    teacher_redirect_assignment($assignmentId, $redirectYear, 'saved=1');
}

$assignmentId = (int) ($_GET['assignment_id'] ?? 0);
if ($assignmentId <= 0) {
    exit_forbidden('Missing assignment.');
}

$assign = teacher_assignment_for_teacher($mysqli, $tid, $assignmentId);
if ($assign === null) {
    exit_forbidden('You are not assigned to this class subject.');
}

$yearId = (int) $assign['academic_year_id'];
$classId = (int) $assign['class_id'];
$roster = teacher_roster($mysqli, $classId, $yearId);
$grades = teacher_grades_for_assignment($mysqli, $assignmentId);

$gradesByStudent = [];
foreach ($grades as $g) {
    $sid = (int) $g['student_user_id'];
    if (!isset($gradesByStudent[$sid])) {
        $gradesByStudent[$sid] = ['periodic' => [], 'year_end' => null];
    }
    if ($g['grade_type'] === 'year_end') {
        $gradesByStudent[$sid]['year_end'] = $g;
    } else {
        $gradesByStudent[$sid]['periodic'][] = $g;
    }
}

$shell_title = 'Roster & grades';
$shell_nav_items = teacher_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="teacher_assignments.php<?php echo teacher_year_q($yearId); ?>">My subjects</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars((string) $assign['subject_title'], ENT_QUOTES, 'UTF-8'); ?></li>
      </ol>
    </nav>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <h1 class="h3 mb-2"><?php echo htmlspecialchars((string) $assign['subject_title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="text-muted small mb-4">
      <?php echo htmlspecialchars((string) $assign['year_label'], ENT_QUOTES, 'UTF-8'); ?>
      · Class <?php echo htmlspecialchars((string) ($assign['display_name'] ?: $assign['class_code']), ENT_QUOTES, 'UTF-8'); ?>
      · <span class="text-dark">Grade scale: integers 1–5 only</span>
    </p>

    <div class="card border-info mb-4">
      <div class="card-body py-2">
        <p class="small mb-0"><strong>Year-end grades:</strong> use the year-end row per student when closing the year. Updating overwrites the previous year-end value for that student.</p>
      </div>
    </div>

    <?php foreach ($roster as $student): ?>
      <?php
        $sid = (int) $student['student_id'];
        $sun = htmlspecialchars((string) $student['username'], ENT_QUOTES, 'UTF-8');
        $periodicList = $gradesByStudent[$sid]['periodic'] ?? [];
        $ye = $gradesByStudent[$sid]['year_end'] ?? null;
        $csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
      ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <strong><?php echo $sun; ?></strong>
          <span class="small text-muted">ID <?php echo $sid; ?></span>
        </div>
        <div class="card-body">
          <h3 class="h6">Recorded grades</h3>
          <?php if ($periodicList === [] && $ye === null): ?>
            <p class="small text-muted">No grades yet.</p>
          <?php else: ?>
            <ul class="small mb-3">
              <?php foreach ($periodicList as $pg): ?>
                <li><?php echo htmlspecialchars((string) $pg['label'], ENT_QUOTES, 'UTF-8'); ?>:
                  <strong><?php echo htmlspecialchars((string) $pg['grade_value'], ENT_QUOTES, 'UTF-8'); ?></strong>
                  <span class="text-muted">(<?php echo htmlspecialchars((string) $pg['grade_type'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                </li>
              <?php endforeach; ?>
              <?php if ($ye !== null): ?>
                <li><strong>Year-end:</strong> <?php echo htmlspecialchars((string) $ye['grade_value'], ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endif; ?>
            </ul>
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-md-6">
              <h4 class="h6">Add periodic grade</h4>
              <form method="post" class="border rounded p-3 bg-white">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <input type="hidden" name="year_id" value="<?php echo $yearId; ?>">
                <input type="hidden" name="student_user_id" value="<?php echo $sid; ?>">
                <input type="hidden" name="action" value="periodic">
                <div class="mb-2">
                  <label class="form-label small">Label (e.g. quiz, oral)</label>
                  <input class="form-control form-control-sm" name="label" maxlength="64" placeholder="Topic test 2">
                </div>
                <div class="mb-2">
                  <label class="form-label small">Grade 1–5</label>
                  <input class="form-control form-control-sm" name="grade_value" required pattern="[1-5]" title="1 to 5">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Save periodic</button>
              </form>
            </div>
            <div class="col-md-6">
              <h4 class="h6">Year-end grade</h4>
              <form method="post" class="border rounded p-3 bg-light">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <input type="hidden" name="year_id" value="<?php echo $yearId; ?>">
                <input type="hidden" name="student_user_id" value="<?php echo $sid; ?>">
                <input type="hidden" name="action" value="year_end">
                <div class="mb-2">
                  <label class="form-label small">Year-end (1–5)</label>
                  <input class="form-control form-control-sm" name="grade_value" required pattern="[1-5]" title="1 to 5" value="<?php echo $ye !== null ? htmlspecialchars((string) $ye['grade_value'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-success">Save year-end</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if ($roster === []): ?>
      <div class="alert alert-warning">No students are enrolled in this class for this year.</div>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
