<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('teacher');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/teacher_data.php';
require_once __DIR__ . '/includes/timetable_slots.php';

$tid = (int) current_user_id();
$reqYear = isset($_GET['year_id']) ? (int) $_GET['year_id'] : 0;
$yearRow = teacher_resolve_year($mysqli, $tid, $reqYear);
$yearId = $yearRow ? (int) $yearRow['id'] : 0;
$assignments = $yearRow ? teacher_assignments_for_year($mysqli, $tid, $yearId) : [];
$taIds = array_map(static fn (array $a): int => (int) $a['assignment_id'], $assignments);
$slotsByAssignment = timetable_slots_batch($mysqli, $taIds);

$shell_title = 'My subjects';
$shell_nav_items = teacher_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-3">Subjects you teach</h1>
    <?php if ($yearRow === null): ?>
      <div class="alert alert-warning">No assignments found.</div>
    <?php else: ?>
      <p class="text-muted small mb-3">Year <strong><?php echo htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8'); ?></strong> · Grades scale <strong>1–5</strong>.</p>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr><th>Subject</th><th>Class</th><th>Timetable</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($assignments as $a): ?>
              <?php $aid = (int) $a['assignment_id']; ?>
              <tr>
                <td><?php echo htmlspecialchars((string) $a['subject_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($a['display_name'] ?: $a['class_code']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="small"><?php echo htmlspecialchars(timetable_slots_summary_string($slotsByAssignment[$aid] ?? []), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(teacher_assignment_url((int) $a['assignment_id'], $yearId), ENT_QUOTES, 'UTF-8'); ?>">Roster &amp; grades</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($assignments === []): ?>
        <p class="text-muted small">No rows for this year.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
