<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('student');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/student_data.php';

$uid = (int) current_user_id();
$reqYear = isset($_GET['year_id']) ? (int) $_GET['year_id'] : 0;
$yearRow = student_resolve_year($mysqli, $uid, $reqYear);
$yearId = $yearRow ? (int) $yearRow['id'] : 0;
$enroll = $yearRow ? student_enrollment_with_class($mysqli, $uid, $yearId) : null;
$grades = ($enroll !== null) ? student_grades_for_class_year($mysqli, $uid, $yearId, (int) $enroll['class_id']) : [];

$shell_title = 'Results';
$shell_nav_items = student_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-3">Your results</h1>
    <?php if ($yearRow === null || $enroll === null): ?>
      <div class="alert alert-warning">No enrollment found. Results are only available when you are assigned to a class for a year.</div>
    <?php else: ?>
      <p class="text-muted small mb-3">
        Year <strong><?php echo htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
        · Grades are only shown for subjects in your class (server-side filter).
      </p>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Type</th>
              <th>Item</th>
              <th>Grade</th>
              <th>Recorded</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($grades as $g): ?>
              <tr>
                <td><?php echo htmlspecialchars((string) $g['subject_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) $g['grade_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($g['label'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><strong><?php echo htmlspecialchars((string) $g['grade_value'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td class="small text-muted"><?php echo htmlspecialchars((string) $g['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($grades === []): ?>
        <p class="text-muted small">No grades recorded for you in this year yet.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
