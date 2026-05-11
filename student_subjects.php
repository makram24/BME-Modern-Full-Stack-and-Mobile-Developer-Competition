<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('student');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/student_data.php';
require_once __DIR__ . '/includes/timetable_slots.php';

$uid = (int) current_user_id();
$reqYear = isset($_GET['year_id']) ? (int) $_GET['year_id'] : 0;
$yearRow = student_resolve_year($mysqli, $uid, $reqYear);
$yearId = $yearRow ? (int) $yearRow['id'] : 0;
$enroll = $yearRow ? student_enrollment_with_class($mysqli, $uid, $yearId) : null;
$assignments = ($enroll !== null) ? student_assignments_for_class_year($mysqli, $yearId, (int) $enroll['class_id']) : [];
$slotIds = array_map(static fn (array $a): int => (int) $a['assignment_id'], $assignments);
$slotsByAssignment = timetable_slots_batch($mysqli, $slotIds);

$shell_title = 'Subjects';
$shell_nav_items = student_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-3">Subjects for your class</h1>
    <?php if ($yearRow === null || $enroll === null): ?>
      <div class="alert alert-warning">No enrollment found. You cannot view subjects until you are assigned to a class for a year.</div>
    <?php else: ?>
      <p class="text-muted small mb-3">
        Year <strong><?php echo htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
        · Class <strong><?php echo htmlspecialchars((string) ($enroll['display_name'] ?: $enroll['class_code']), ENT_QUOTES, 'UTF-8'); ?></strong>
      </p>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead><tr><th>Subject</th><th>Teacher</th><th>Timetable</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($assignments as $a): ?>
              <?php
                $aid = (int) $a['assignment_id'];
                $q = student_year_q($yearId);
                $sep = $q !== '' ? '&' : '?';
                $detailHref = 'student_subject.php' . $q . $sep . 'assignment_id=' . $aid;
                $ttSlots = $slotsByAssignment[$aid] ?? [];
              ?>
              <tr>
                <td><?php echo htmlspecialchars((string) $a['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) $a['teacher_username'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="small"><?php echo htmlspecialchars(timetable_slots_summary_string($ttSlots), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?php echo $detailHref; ?>">Details</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($assignments === []): ?>
        <p class="text-muted small">No subjects are assigned to your class for this year yet.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
