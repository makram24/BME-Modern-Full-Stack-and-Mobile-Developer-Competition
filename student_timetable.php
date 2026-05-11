<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('student');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/student_data.php';
require_once __DIR__ . '/includes/timetable_format.php';

$uid = (int) current_user_id();
$reqYear = isset($_GET['year_id']) ? (int) $_GET['year_id'] : 0;
$yearRow = student_resolve_year($mysqli, $uid, $reqYear);
$yearId = $yearRow ? (int) $yearRow['id'] : 0;
$enroll = $yearRow ? student_enrollment_with_class($mysqli, $uid, $yearId) : null;
$rows = ($enroll !== null) ? student_timetable_assignments($mysqli, $yearId, (int) $enroll['class_id']) : [];
$grid = timetable_build_week_grid($rows);

$shell_title = 'Timetable';
$shell_nav_items = student_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-12">
    <h1 class="h3 mb-3">Class timetable</h1>
    <?php if ($yearRow === null || $enroll === null): ?>
      <div class="alert alert-warning">No enrollment found for this year.</div>
    <?php else: ?>
      <p class="text-muted small mb-2">
        Year <strong><?php echo htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
        · Only subjects that have a <strong>weekday + period</strong> set on the assignment appear in cells. Subjects still appear in your <a href="student_subjects.php<?php echo htmlspecialchars(student_year_q($yearId), ENT_QUOTES, 'UTF-8'); ?>">Subjects</a> list even if no slot is set yet.
      </p>
      <p class="text-muted small mb-3">
        <strong>Blank cells</strong> mean no class is scheduled for that day and period (for example a small database may only book Monday periods 1–2 and leave the rest of the week empty by design).
      </p>
      <?php if ($rows === []): ?>
        <div class="alert alert-info py-2">Nothing scheduled yet. When your school sets weekday + period on each subject assignment, it will appear here.</div>
      <?php else: ?>
        <p class="small text-muted mb-2">Showing periods <strong>1</strong> through <strong><?php echo (int) $grid['maxSlot']; ?></strong> (up to the highest period number in use).</p>
        <div class="table-responsive">
          <table class="table table-bordered table-sm text-center align-middle" style="min-width: 44rem">
            <thead class="table-light">
              <tr>
                <th scope="col" class="text-start">Period</th>
                <?php for ($d = 1; $d <= 7; $d++): ?>
                  <th scope="col"><?php echo htmlspecialchars(timetable_day_label($d), ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endfor; ?>
              </tr>
            </thead>
            <tbody>
              <?php for ($slot = 1; $slot <= $grid['maxSlot']; $slot++): ?>
                <tr>
                  <th scope="row" class="text-start small text-muted"><?php echo htmlspecialchars(timetable_slot_label($slot), ENT_QUOTES, 'UTF-8'); ?></th>
                  <?php for ($day = 1; $day <= 7; $day++): ?>
                    <?php
                      $cellLines = $grid['cells'][$day][$slot] ?? [];
                      $inner = $cellLines === []
                        ? timetable_empty_cell_html()
                        : implode('<hr class="my-1 opacity-25">', array_map(static function (string $line): string {
                            return nl2br(htmlspecialchars($line, ENT_QUOTES, 'UTF-8'));
                        }, $cellLines));
                    ?>
                    <td class="small p-2"><?php echo $inner; ?></td>
                  <?php endfor; ?>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
