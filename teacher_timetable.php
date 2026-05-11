<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('teacher');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/teacher_data.php';
require_once __DIR__ . '/includes/timetable_format.php';

$tid = (int) current_user_id();
$reqYear = isset($_GET['year_id']) ? (int) $_GET['year_id'] : 0;
$yearRow = teacher_resolve_year($mysqli, $tid, $reqYear);
$yearId = $yearRow ? (int) $yearRow['id'] : 0;
$raw = $yearRow ? teacher_timetable_assignments($mysqli, $tid, $yearId) : [];
$gridRows = [];
foreach ($raw as $r) {
    $cl = trim((string) ($r['display_name'] ?? '')) !== '' ? trim((string) $r['display_name']) : (string) $r['class_code'];
    $gridRows[] = [
        'timetable_day' => $r['timetable_day'],
        'timetable_slot' => $r['timetable_slot'],
        'subject_title' => (string) $r['subject_title'] . "\n(" . $cl . ')',
    ];
}
$grid = timetable_build_week_grid($gridRows);

$shell_title = 'Timetable';
$shell_nav_items = teacher_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-12">
    <h1 class="h3 mb-3">Your teaching timetable</h1>
    <?php if ($yearRow === null): ?>
      <div class="alert alert-warning">No assignments for this year.</div>
    <?php else: ?>
      <p class="text-muted small mb-2">
        Year <strong><?php echo htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
        · Each filled cell is a subject you teach (class on the second line). Administrators set weekday + period on each assignment.
      </p>
      <p class="text-muted small mb-3">
        <strong>Blank cells</strong> are free periods—normal when only some slots are assigned (e.g. two subjects on Monday 1–2 and nothing else in the database).
      </p>
      <?php if ($raw === []): ?>
        <div class="alert alert-info py-2">No scheduled slots yet. Ask an administrator to set weekday + period on your class–subject assignments.</div>
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
