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

$shell_title = 'Student';
$shell_nav_items = student_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-2">Welcome, <?php echo htmlspecialchars((string) (current_username() ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>

    <?php if ($yearRow === null): ?>
      <div class="alert alert-warning">You are not enrolled in any class yet. An administrator must add you to a class for an academic year.</div>
    <?php elseif ($enroll === null): ?>
      <div class="alert alert-warning">No class enrollment found for the selected year. Contact an administrator.</div>
    <?php else: ?>
      <?php
        $yLabel = htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8');
        $classLine = htmlspecialchars(
            ($enroll['display_name'] ?? '') !== ''
                ? (string) $enroll['display_name']
                : (string) $enroll['class_code'] . ' · started ' . (string) $enroll['start_date'],
            ENT_QUOTES,
            'UTF-8'
        );
      ?>
      <p class="text-muted mb-1"><strong>Academic year:</strong> <?php echo $yLabel; ?></p>
      <p class="text-muted mb-3"><strong>Your class:</strong> <?php echo $classLine; ?></p>

      <?php $yearsOpts = student_enrolled_years_detail($mysqli, $uid); ?>
      <?php if (count($yearsOpts) > 1): ?>
        <form class="row g-2 align-items-end mb-4" method="get" action="student_dashboard.php">
          <div class="col-auto">
            <label class="form-label small mb-0" for="year_id">View year</label>
            <select class="form-select form-select-sm" id="year_id" name="year_id" onchange="this.form.submit()">
              <?php foreach ($yearsOpts as $opt): ?>
                <option value="<?php echo (int) $opt['id']; ?>"<?php echo $opt['id'] === $yearId ? ' selected' : ''; ?>>
                  <?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?>
                  <?php echo $opt['is_current'] ? ' (current)' : ''; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      <?php endif; ?>

      <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-primary btn-sm" href="student_subjects.php<?php echo student_year_q($yearId); ?>">View subjects</a>
        <a class="btn btn-outline-primary btn-sm" href="student_results.php<?php echo student_year_q($yearId); ?>">View results</a>
        <a class="btn btn-outline-secondary btn-sm" href="events.php<?php echo $yearId > 0 ? '?year_id=' . $yearId : ''; ?>">School events</a>
        <a class="btn btn-outline-secondary btn-sm" href="student_timetable.php<?php echo student_year_q($yearId); ?>">Class timetable</a>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
