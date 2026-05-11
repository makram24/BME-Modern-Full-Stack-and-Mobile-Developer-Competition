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

$yq = student_year_q($yearId);
$eventsQ = $yearId > 0 ? ('?year_id=' . $yearId) : '';

ob_start();
?>
<div class="portal-page-head">
  <h1>Hello, <?php echo htmlspecialchars((string) (current_username() ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>
  <p class="text-muted">Pick a task below or use the menu. Everything is scoped to your class and academic year.</p>
</div>

<?php if ($yearRow === null): ?>
  <div class="alert alert-warning border-0 shadow-sm">You are not enrolled in any class yet. An administrator must add you to a class for an academic year.</div>
<?php elseif ($enroll === null): ?>
  <div class="alert alert-warning border-0 shadow-sm">No class enrollment found for the selected year. Contact an administrator.</div>
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
  <div class="portal-card p-3 p-md-4 mb-4">
    <p class="mb-1 small text-muted text-uppercase" style="letter-spacing:0.05em">Academic year</p>
    <p class="mb-3 fw-semibold text-dark"><?php echo $yLabel; ?></p>
    <p class="mb-1 small text-muted text-uppercase" style="letter-spacing:0.05em">Your class</p>
    <p class="mb-0 fw-medium"><?php echo $classLine; ?></p>
  </div>

  <?php $yearsOpts = student_enrolled_years_detail($mysqli, $uid); ?>
  <?php if (count($yearsOpts) > 1): ?>
    <form class="portal-card p-3 mb-4" method="get" action="student_dashboard.php">
      <label class="form-label small mb-1" for="year_id">Switch academic year</label>
      <select class="form-select form-select-sm" id="year_id" name="year_id" onchange="this.form.submit()" style="max-width:22rem">
        <?php foreach ($yearsOpts as $opt): ?>
          <option value="<?php echo (int) $opt['id']; ?>"<?php echo $opt['id'] === $yearId ? ' selected' : ''; ?>>
            <?php echo htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8'); ?>
            <?php echo $opt['is_current'] ? ' (current)' : ''; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>

  <h2 class="h6 mb-3">Quick access</h2>
  <div class="portal-tiles mb-2">
    <a class="portal-tile portal-tile--primary" href="student_subjects.php<?php echo htmlspecialchars($yq, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-journal-text portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">Subjects</span>
      <span class="portal-tile__hint">Syllabus, teacher, and materials for your class.</span>
    </a>
    <a class="portal-tile" href="student_results.php<?php echo htmlspecialchars($yq, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-clipboard-data portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">Results</span>
      <span class="portal-tile__hint">Grades and averages for this year.</span>
    </a>
    <a class="portal-tile" href="student_timetable.php<?php echo htmlspecialchars($yq, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-calendar3 portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">Class timetable</span>
      <span class="portal-tile__hint">Weekly schedule for your subjects.</span>
    </a>
    <a class="portal-tile" href="events.php<?php echo htmlspecialchars($eventsQ, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-calendar-event portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">School events</span>
      <span class="portal-tile__hint">Campus calendar (read-only).</span>
    </a>
    <a class="portal-tile" href="student_record.php<?php echo htmlspecialchars($yq, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-person-badge portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">My profile</span>
      <span class="portal-tile__hint">Your account details.</span>
    </a>
  </div>
<?php endif; ?>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
