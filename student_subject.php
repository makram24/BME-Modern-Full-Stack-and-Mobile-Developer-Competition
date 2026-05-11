<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('student');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/student_data.php';
require_once __DIR__ . '/includes/timetable_slots.php';
require_once __DIR__ . '/includes/subject_assignments_upload.php';

$uid = (int) current_user_id();
$assignmentId = isset($_GET['assignment_id']) ? (int) $_GET['assignment_id'] : 0;
if ($assignmentId <= 0) {
    exit_forbidden('Missing or invalid subject reference.');
}

$row = student_assignment_for_user($mysqli, $uid, $assignmentId);
if ($row === null) {
    exit_forbidden('You do not have access to this subject offering.');
}

$ttSlots = timetable_slots_list($mysqli, $assignmentId);
$ttLine = timetable_slots_summary_string($ttSlots);

$yearId = (int) $row['academic_year_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_submission') {
    if (!csrf_verify_post()) {
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Invalid session token.'));
        exit;
    }

    $subjectAssignmentId = (int) ($_POST['subject_assignment_id'] ?? 0);
    if ($subjectAssignmentId <= 0) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Invalid assignment.'));
        exit;
    }

    // Ensure this teacher-created assignment belongs to the current class-subject offering.
    $chk = $mysqli->prepare(
        'SELECT 1 FROM subject_assignments WHERE id = ? AND class_subject_assignment_id = ? LIMIT 1'
    );
    if ($chk === false) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Database error.'));
        exit;
    }
    $chk->bind_param('ii', $subjectAssignmentId, $assignmentId);
    $chk->execute();
    $ok = $chk->get_result()->num_rows > 0;
    $chk->close();
    if (!$ok) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Invalid assignment.'));
        exit;
    }

    if (empty($_FILES['submission_file']) || !isset($_FILES['submission_file']['tmp_name'])) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Please choose a file.'));
        exit;
    }

    $f = $_FILES['submission_file'];
    if ((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Upload failed. Please try again.'));
        exit;
    }

    $size = (int) ($f['size'] ?? 0);
    if ($size <= 0) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Upload file is empty.'));
        exit;
    }
    if ($size > SUBJECT_UPLOAD_MAX_BYTES) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('File is too large (max 10 MiB).'));
        exit;
    }

    $origName = (string) ($f['name'] ?? '');
    $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = subject_upload_allowed_extensions();
    if ($origName === '' || !in_array($ext, $allowed, true)) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Unsupported file type.'));
        exit;
    }

    // Delete old file (single submission per student per assignment).
    $existing = subject_submission_get($mysqli, $subjectAssignmentId, $uid);

    $storedDirRel = 'subject_assignments/' . $subjectAssignmentId . '/' . $uid;
    $storedDirAbs = __DIR__ . '/uploads/' . $storedDirRel;
    if (!is_dir($storedDirAbs)) {
        mkdir($storedDirAbs, 0775, true);
    }

    $storedFilename = bin2hex(random_bytes(16)) . '.' . $ext;
    $storedPathRel = $storedDirRel . '/' . $storedFilename;
    $storedAbsPath = __DIR__ . '/uploads/' . $storedPathRel;

    if (!move_uploaded_file((string) $f['tmp_name'], $storedAbsPath)) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Could not save upload. Please try again.'));
        exit;
    }

    if ($existing !== null) {
        $oldRel = (string) ($existing['file_stored_path'] ?? '');
        $oldAbs = __DIR__ . '/uploads/' . $oldRel;
        if ($oldRel !== '' && is_file($oldAbs)) {
            @unlink($oldAbs);
        }
    }

    $okSave = subject_submission_upsert($mysqli, $subjectAssignmentId, $uid, $origName, $storedPathRel);
    if (!$okSave) {
        csrf_rotate();
        header('Location: student_subject.php?assignment_id=' . $assignmentId . '&error=' . rawurlencode('Could not save submission to database.'));
        exit;
    }

    csrf_rotate();
    header('Location: student_subject.php?assignment_id=' . $assignmentId . '&saved=1');
    exit;
}

$subjectAssignments = subject_assignments_list($mysqli, $assignmentId);
$shell_title = 'Subject';
$shell_nav_items = student_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-lg-9">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="student_subjects.php<?php echo student_year_q($yearId); ?>">Subjects</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8'); ?></li>
      </ol>
    </nav>
    <h1 class="h3 mb-2"><?php echo htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="text-muted small mb-4">
      Year <?php echo htmlspecialchars((string) $row['year_label'], ENT_QUOTES, 'UTF-8'); ?>
      · Teacher <strong><?php echo htmlspecialchars((string) $row['teacher_username'], ENT_QUOTES, 'UTF-8'); ?></strong>
      <?php if ($ttLine !== '—'): ?>
        · Timetable <strong><?php echo htmlspecialchars($ttLine, ENT_QUOTES, 'UTF-8'); ?></strong>
      <?php endif; ?>
    </p>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6">Description</h2>
        <p class="small mb-0"><?php echo nl2br(htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
      </div>
    </div>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6">Required books</h2>
        <p class="small mb-0"><?php echo nl2br(htmlspecialchars((string) ($row['required_books'] ?? '—'), ENT_QUOTES, 'UTF-8')); ?></p>
      </div>
    </div>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6">Lessons / topics</h2>
        <p class="small mb-0"><?php echo nl2br(htmlspecialchars((string) ($row['lessons_outline'] ?? '—'), ENT_QUOTES, 'UTF-8')); ?></p>
      </div>
    </div>
    <div class="card border-secondary mb-3">
      <div class="card-body py-2">
        <p class="small text-muted mb-0"><strong>Timetable:</strong> <?php echo $ttLine === '—' ? 'No periods set yet.' : htmlspecialchars($ttLine, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
    </div>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2 border-0 shadow-sm mb-3">Upload saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2 border-0 shadow-sm mb-3"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 mb-3">Assignments upload</h2>
        <?php if ($subjectAssignments === []): ?>
          <p class="small text-muted mb-0">Your teacher has not uploaded any assignments for this subject yet.</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($subjectAssignments as $sa): ?>
              <?php
                $saId = (int) $sa['id'];
                $sub = subject_submission_get($mysqli, $saId, $uid);
              ?>
              <div class="portal-card p-3">
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div class="min-w-0">
                      <h3 class="h6 mb-1"><?php echo htmlspecialchars((string) $sa['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                      <?php if (!empty($sa['due_at'])): ?>
                        <p class="small text-muted mb-1">
                          <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                          Due <?php echo htmlspecialchars((string) $sa['due_at'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if (!empty($sa['instructions'])): ?>
                    <p class="small mb-0 text-body-secondary"><?php echo nl2br(htmlspecialchars((string) $sa['instructions'], ENT_QUOTES, 'UTF-8')); ?></p>
                  <?php endif; ?>

                  <?php if ($sub !== null): ?>
                    <div class="small text-muted">
                      Uploaded:
                      <strong><?php echo htmlspecialchars((string) ($sub['file_original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                      · <?php echo htmlspecialchars((string) ($sub['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                  <?php else: ?>
                    <div class="small text-muted">No file submitted yet.</div>
                  <?php endif; ?>

                  <form method="post" enctype="multipart/form-data" class="border rounded p-2 bg-white">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="upload_submission">
                    <input type="hidden" name="subject_assignment_id" value="<?php echo $saId; ?>">
                    <div class="row g-2 align-items-end">
                      <div class="col-md-8">
                        <label class="form-label small mb-1">Choose file</label>
                        <input class="form-control form-control-sm" type="file" name="submission_file"
                          accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required>
                      </div>
                      <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">
                          <?php echo $sub !== null ? 'Replace file' : 'Upload file'; ?>
                        </button>
                      </div>
                    </div>
                    <div class="small text-muted mt-2">Max size: 10 MiB. Allowed: PDF/DOC/DOCX/PNG/JPG.</div>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
