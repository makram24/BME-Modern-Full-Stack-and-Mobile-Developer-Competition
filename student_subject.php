<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('student');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/student_data.php';

$uid = (int) current_user_id();
$assignmentId = isset($_GET['assignment_id']) ? (int) $_GET['assignment_id'] : 0;
if ($assignmentId <= 0) {
    exit_forbidden('Missing or invalid subject reference.');
}

$row = student_assignment_for_user($mysqli, $uid, $assignmentId);
if ($row === null) {
    exit_forbidden('You do not have access to this subject offering.');
}

$yearId = (int) $row['academic_year_id'];
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
        <p class="small text-muted mb-0"><strong>Timetable:</strong> not configured yet (optional competition feature).</p>
      </div>
    </div>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
