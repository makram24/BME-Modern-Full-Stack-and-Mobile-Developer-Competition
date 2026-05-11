<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('teacher');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/teacher_data.php';
require_once __DIR__ . '/includes/grade_weights.php';
require_once __DIR__ . '/includes/timetable_slots.php';

$tid = (int) current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        http_response_code(403);
        exit('Invalid session token.');
    }

    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
    $yearForUrl = (int) ($_POST['year_id'] ?? 0);
    $rosterPageRedirect = max(1, (int) ($_POST['roster_page'] ?? 1));
    $assign = teacher_assignment_for_teacher($mysqli, $tid, $assignmentId);
    if ($assign === null) {
        exit_forbidden('You are not assigned to this class subject.');
    }

    $classId = (int) $assign['class_id'];
    $academicYearId = (int) $assign['academic_year_id'];
    $redirectYear = $yearForUrl > 0 ? $yearForUrl : $academicYearId;
    $action = (string) ($_POST['action'] ?? '');
    $saveFailed = 'Could not save grade. Please try again.';

    if ($action === 'periodic_weight') {
        $gradeId = (int) ($_POST['grade_id'] ?? 0);
        $weightIn = parse_grade_weight($_POST['weight'] ?? '');
        if ($weightIn === null) {
            teacher_redirect_assignment(
                $assignmentId,
                $redirectYear,
                'error=' . rawurlencode('Weight must be a number between ' . GRADE_WEIGHT_MIN . ' and ' . GRADE_WEIGHT_MAX . '.'),
                $rosterPageRedirect
            );
        }
        $mysqli->begin_transaction();
        $chk = $mysqli->prepare(
            'SELECT 1 FROM grades g
             INNER JOIN class_subject_assignments csa ON csa.id = g.class_subject_assignment_id
             WHERE g.id = ? AND g.class_subject_assignment_id = ? AND csa.teacher_user_id = ? AND g.grade_type = \'periodic\'
             LIMIT 1'
        );
        if ($chk === false || !$chk->bind_param('iii', $gradeId, $assignmentId, $tid) || !$chk->execute()) {
            $mysqli->rollback();
            portal_log('teacher periodic_weight check failed', ['errno' => $mysqli->errno]);
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('Could not update weight.'), $rosterPageRedirect);
        }
        $okRow = $chk->get_result()->num_rows > 0;
        $chk->close();
        if (!$okRow) {
            $mysqli->rollback();
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('That periodic grade was not found for this subject.'), $rosterPageRedirect);
        }
        $uw = $mysqli->prepare(
            'UPDATE grades g
             INNER JOIN class_subject_assignments csa ON csa.id = g.class_subject_assignment_id
             SET g.weight = ?
             WHERE g.id = ? AND g.class_subject_assignment_id = ? AND csa.teacher_user_id = ? AND g.grade_type = \'periodic\''
        );
        if ($uw === false || !$uw->bind_param('diii', $weightIn, $gradeId, $assignmentId, $tid) || !$uw->execute()) {
            $mysqli->rollback();
            portal_log('teacher periodic_weight update failed', ['errno' => $mysqli->errno, 'error' => $mysqli->error]);
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('Could not update weight.'), $rosterPageRedirect);
        }
        $uw->close();
        $mysqli->commit();
        teacher_redirect_assignment($assignmentId, $redirectYear, 'saved=1', $rosterPageRedirect);
    }

    $studentId = (int) ($_POST['student_user_id'] ?? 0);

    if (!teacher_student_in_class($mysqli, $classId, $academicYearId, $studentId)) {
        exit_forbidden('That student is not in this class for this year.');
    }

    if ($action !== 'periodic' && $action !== 'year_end' && $action !== 'semester') {
        teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('Unknown action.'), $rosterPageRedirect);
    }

    if ($action === 'periodic') {
        $label = trim((string) ($_POST['label'] ?? ''));
        if ($label === '') {
            $label = 'Grade entry';
        }
        $val = trim((string) ($_POST['grade_value'] ?? ''));
        if (!teacher_grade_value_valid($val)) {
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('Use a whole number from 1 to 5 only.'), $rosterPageRedirect);
        }
        $weightIn = parse_grade_weight($_POST['weight'] ?? '');
        if ($weightIn === null) {
            teacher_redirect_assignment(
                $assignmentId,
                $redirectYear,
                'error=' . rawurlencode('Weight must be a number between ' . GRADE_WEIGHT_MIN . ' and ' . GRADE_WEIGHT_MAX . '.'),
                $rosterPageRedirect
            );
        }
        $gtype = 'periodic';
        $mysqli->begin_transaction();
        $stmt = $mysqli->prepare(
            'INSERT INTO grades (class_subject_assignment_id, student_user_id, grade_value, grade_type, label, entered_by_user_id, weight) VALUES (?,?,?,?,?,?,?)'
        );
        if ($stmt === false || !$stmt->bind_param('iisssid', $assignmentId, $studentId, $val, $gtype, $label, $tid, $weightIn) || !$stmt->execute()) {
            $mysqli->rollback();
            portal_log('teacher periodic grade failed', ['errno' => $mysqli->errno, 'error' => $mysqli->error]);
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode($saveFailed), $rosterPageRedirect);
        }
        $stmt->close();
        $mysqli->commit();
    } else {
        $val = trim((string) ($_POST['grade_value'] ?? ''));
        if (!teacher_grade_value_valid($val)) {
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode('Use a whole number from 1 to 5 only.'), $rosterPageRedirect);
        }
        $gtype = $action === 'semester' ? 'semester' : 'year_end';
        $mysqli->begin_transaction();
        $sel = $mysqli->prepare(
            'SELECT id FROM grades WHERE class_subject_assignment_id = ? AND student_user_id = ? AND grade_type = ? LIMIT 1 FOR UPDATE'
        );
        if ($sel === false || !$sel->bind_param('iis', $assignmentId, $studentId, $gtype) || !$sel->execute()) {
            $mysqli->rollback();
            portal_log('teacher term grade select failed', ['type' => $gtype, 'errno' => $mysqli->errno, 'error' => $mysqli->error]);
            teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode($saveFailed), $rosterPageRedirect);
        }
        $row = $sel->get_result()->fetch_assoc();
        $sel->close();
        $existingId = $row ? (int) $row['id'] : 0;

        if ($existingId > 0) {
            $up = $mysqli->prepare('UPDATE grades SET grade_value = ?, entered_by_user_id = ? WHERE id = ?');
            if ($up === false || !$up->bind_param('sii', $val, $tid, $existingId) || !$up->execute()) {
                $mysqli->rollback();
                portal_log('teacher term grade update failed', ['type' => $gtype, 'errno' => $mysqli->errno, 'error' => $mysqli->error]);
                teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode($saveFailed), $rosterPageRedirect);
            }
            $up->close();
        } else {
            $ins = $mysqli->prepare(
                'INSERT INTO grades (class_subject_assignment_id, student_user_id, grade_value, grade_type, entered_by_user_id) VALUES (?,?,?,?,?)'
            );
            if ($ins === false || !$ins->bind_param('iissi', $assignmentId, $studentId, $val, $gtype, $tid) || !$ins->execute()) {
                $mysqli->rollback();
                portal_log('teacher term grade insert failed', ['type' => $gtype, 'errno' => $mysqli->errno, 'error' => $mysqli->error]);
                teacher_redirect_assignment($assignmentId, $redirectYear, 'error=' . rawurlencode($saveFailed), $rosterPageRedirect);
            }
            $ins->close();
        }
        $mysqli->commit();
    }

    teacher_redirect_assignment($assignmentId, $redirectYear, 'saved=1', $rosterPageRedirect);
}

$assignmentId = (int) ($_GET['assignment_id'] ?? 0);
if ($assignmentId <= 0) {
    exit_forbidden('Missing assignment.');
}

$assign = teacher_assignment_for_teacher($mysqli, $tid, $assignmentId);
if ($assign === null) {
    exit_forbidden('You are not assigned to this class subject.');
}

$ttSlots = timetable_slots_list($mysqli, $assignmentId);
$ttHdr = timetable_slots_summary_string($ttSlots);

$yearId = (int) $assign['academic_year_id'];
$classId = (int) $assign['class_id'];
$rosterTotal = teacher_roster_count($mysqli, $classId, $yearId);
$rosterPage = max(1, (int) ($_GET['page'] ?? 1));
$totalPages = $rosterTotal > 0 ? (int) ceil($rosterTotal / TEACHER_ROSTER_PAGE_SIZE) : 1;
if ($rosterPage > $totalPages) {
    teacher_redirect_assignment($assignmentId, $yearId, '', $totalPages);
}
$roster = teacher_roster_page($mysqli, $classId, $yearId, $rosterPage);
$grades = teacher_grades_for_assignment($mysqli, $assignmentId);

$gradesByStudent = [];
foreach ($grades as $g) {
    $sid = (int) $g['student_user_id'];
    if (!isset($gradesByStudent[$sid])) {
        $gradesByStudent[$sid] = ['periodic' => [], 'year_end' => null, 'semester' => null];
    }
    $gt = (string) $g['grade_type'];
    if ($gt === 'year_end') {
        $gradesByStudent[$sid]['year_end'] = $g;
    } elseif ($gt === 'semester') {
        $gradesByStudent[$sid]['semester'] = $g;
    } elseif ($gt === 'periodic') {
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
      <?php if ($ttHdr !== '—'): ?>
        · Timetable <strong><?php echo htmlspecialchars($ttHdr, ENT_QUOTES, 'UTF-8'); ?></strong>
      <?php endif; ?>
      · <span class="text-dark">Grade scale: integers 1–5 only</span>
    </p>

    <div class="card border-info mb-4">
      <div class="card-body py-2">
        <p class="small mb-2"><strong>Semester grade:</strong> one official semester mark per student per subject (same 1–5 scale). Saving again overwrites the previous semester value.</p>
        <p class="small mb-2"><strong>Year-end grades:</strong> use the year-end row when closing the year. Updating overwrites the previous year-end value for that student.</p>
        <p class="small mb-0"><strong>Weights (periodic):</strong> each periodic entry has a weight (default 1). The <em>weighted average</em> uses only periodic grades: sum(grade × weight) ÷ sum(weight). Semester and year-end are not part of that average. Allowed weights: <?php echo GRADE_WEIGHT_MIN; ?>–<?php echo GRADE_WEIGHT_MAX; ?>.</p>
      </div>
    </div>

    <?php if ($rosterTotal > TEACHER_ROSTER_PAGE_SIZE): ?>
      <nav class="mb-3 d-flex flex-wrap align-items-center gap-2" aria-label="Roster pages">
        <span class="small text-muted"><?php echo (int) $rosterTotal; ?> students · page <?php echo (int) $rosterPage; ?> / <?php echo (int) $totalPages; ?></span>
        <ul class="pagination pagination-sm mb-0">
          <?php if ($rosterPage > 1): ?>
            <li class="page-item"><a class="page-link" href="<?php echo htmlspecialchars(teacher_assignment_url($assignmentId, $yearId, '', $rosterPage - 1), ENT_QUOTES, 'UTF-8'); ?>">Previous</a></li>
          <?php endif; ?>
          <?php if ($rosterPage < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="<?php echo htmlspecialchars(teacher_assignment_url($assignmentId, $yearId, '', $rosterPage + 1), ENT_QUOTES, 'UTF-8'); ?>">Next</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <?php foreach ($roster as $student): ?>
      <?php
        $sid = (int) $student['student_id'];
        $sun = htmlspecialchars((string) $student['username'], ENT_QUOTES, 'UTF-8');
        $periodicList = $gradesByStudent[$sid]['periodic'] ?? [];
        $ye = $gradesByStudent[$sid]['year_end'] ?? null;
        $sem = $gradesByStudent[$sid]['semester'] ?? null;
        $weightedAvg = weighted_periodic_grade_average($periodicList);
        $csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
      ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <strong><?php echo $sun; ?></strong>
          <span class="small text-muted">ID <?php echo $sid; ?></span>
        </div>
        <div class="card-body">
          <h3 class="h6">Recorded grades</h3>
          <?php if ($periodicList === [] && $ye === null && $sem === null): ?>
            <p class="small text-muted">No grades yet.</p>
          <?php else: ?>
            <p class="small mb-2"><strong>Weighted average (periodic):</strong> <?php echo htmlspecialchars(format_grade_average($weightedAvg), ENT_QUOTES, 'UTF-8'); ?></p>
            <ul class="small mb-3 list-unstyled">
              <?php foreach ($periodicList as $pg): ?>
                <?php
                  $gid = (int) $pg['id'];
                  $pw = isset($pg['weight']) ? (float) $pg['weight'] : grade_weight_default();
                  $pwDisp = htmlspecialchars(number_format($pw, 2, '.', ''), ENT_QUOTES, 'UTF-8');
                ?>
                <li class="mb-3 pb-2 border-bottom border-light">
                  <?php echo htmlspecialchars((string) $pg['label'], ENT_QUOTES, 'UTF-8'); ?>:
                  <strong><?php echo htmlspecialchars((string) $pg['grade_value'], ENT_QUOTES, 'UTF-8'); ?></strong>
                  <span class="text-muted">(periodic · weight <?php echo $pwDisp; ?>)</span>
                  <form method="post" class="row row-cols-sm-auto g-1 align-items-center mt-1 ms-1">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="action" value="periodic_weight">
                    <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                    <input type="hidden" name="year_id" value="<?php echo $yearId; ?>">
                    <input type="hidden" name="roster_page" value="<?php echo (int) $rosterPage; ?>">
                    <input type="hidden" name="grade_id" value="<?php echo $gid; ?>">
                    <div class="col-auto"><label class="col-form-label col-form-label-sm mb-0">Adjust weight</label></div>
                    <div class="col-auto">
                      <input class="form-control form-control-sm" type="number" name="weight" step="0.25" min="<?php echo (float) GRADE_WEIGHT_MIN; ?>" max="<?php echo (float) GRADE_WEIGHT_MAX; ?>" value="<?php echo htmlspecialchars(number_format($pw, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" required style="width:5.5rem" title="Weight for this entry">
                    </div>
                    <div class="col-auto">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                    </div>
                  </form>
                </li>
              <?php endforeach; ?>
              <?php if ($sem !== null): ?>
                <li class="mb-2"><strong>Semester:</strong> <?php echo htmlspecialchars((string) $sem['grade_value'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-muted">(not in weighted average)</span></li>
              <?php endif; ?>
              <?php if ($ye !== null): ?>
                <li class="mb-0"><strong>Year-end:</strong> <?php echo htmlspecialchars((string) $ye['grade_value'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-muted">(not in weighted average)</span></li>
              <?php endif; ?>
            </ul>
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-lg-4">
              <h4 class="h6">Add periodic grade</h4>
              <form method="post" class="border rounded p-3 bg-white">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <input type="hidden" name="year_id" value="<?php echo $yearId; ?>">
                <input type="hidden" name="roster_page" value="<?php echo (int) $rosterPage; ?>">
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
                <div class="mb-2">
                  <label class="form-label small">Weight (<?php echo GRADE_WEIGHT_MIN; ?>–<?php echo GRADE_WEIGHT_MAX; ?>, default 1)</label>
                  <input class="form-control form-control-sm" type="number" name="weight" step="0.25" min="<?php echo (float) GRADE_WEIGHT_MIN; ?>" max="<?php echo (float) GRADE_WEIGHT_MAX; ?>" value="1" title="Higher weight counts more in the periodic average">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Save periodic</button>
              </form>
            </div>
            <div class="col-lg-4">
              <h4 class="h6">Semester grade</h4>
              <form method="post" class="border rounded p-3 bg-white">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <input type="hidden" name="year_id" value="<?php echo $yearId; ?>">
                <input type="hidden" name="roster_page" value="<?php echo (int) $rosterPage; ?>">
                <input type="hidden" name="student_user_id" value="<?php echo $sid; ?>">
                <input type="hidden" name="action" value="semester">
                <div class="mb-2">
                  <label class="form-label small">Semester (1–5)</label>
                  <input class="form-control form-control-sm" name="grade_value" required pattern="[1-5]" title="1 to 5" value="<?php echo $sem !== null ? htmlspecialchars((string) $sem['grade_value'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-warning">Save semester</button>
              </form>
            </div>
            <div class="col-lg-4">
              <h4 class="h6">Year-end grade</h4>
              <form method="post" class="border rounded p-3 bg-light">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <input type="hidden" name="year_id" value="<?php echo $yearId; ?>">
                <input type="hidden" name="roster_page" value="<?php echo (int) $rosterPage; ?>">
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

    <?php if ($rosterTotal === 0): ?>
      <div class="alert alert-warning">No students are enrolled in this class for this year.</div>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
