<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('teacher');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/teacher_data.php';

$tid = (int) current_user_id();
$reqYear = isset($_GET['year_id']) ? (int) $_GET['year_id'] : 0;
$yearRow = teacher_resolve_year($mysqli, $tid, $reqYear);
$yearId = $yearRow ? (int) $yearRow['id'] : 0;
$assignments = $yearRow ? teacher_assignments_for_year($mysqli, $tid, $yearId) : [];

$shell_title = 'Teacher';
$shell_nav_items = teacher_portal_nav_links($yearId);

ob_start();
?>
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-2">Welcome, <?php echo htmlspecialchars((string) (current_username() ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>

    <?php if ($yearRow === null): ?>
      <div class="alert alert-warning">You have no class assignments yet. An administrator must assign you to a subject for a class and year.</div>
    <?php else: ?>
      <p class="text-muted mb-1"><strong>Working year:</strong> <?php echo htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="text-muted small mb-3">Grades use a <strong>1–5</strong> integer scale site-wide.</p>

      <?php $yearsOpts = teacher_assigned_years_detail($mysqli, $tid); ?>
      <?php if (count($yearsOpts) > 1): ?>
        <form class="row g-2 align-items-end mb-4" method="get" action="teacher_dashboard.php">
          <div class="col-auto">
            <label class="form-label small mb-0" for="year_id">Academic year</label>
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

      <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-primary btn-sm" href="teacher_assignments.php<?php echo teacher_year_q($yearId); ?>">My subjects this year</a>
        <a class="btn btn-outline-secondary btn-sm" href="events.php<?php echo $yearId > 0 ? '?year_id=' . $yearId : ''; ?>">School events</a>
        <a class="btn btn-outline-secondary btn-sm" href="teacher_timetable.php<?php echo teacher_year_q($yearId); ?>">My timetable</a>
      </div>

      <h2 class="h6 text-muted">Quick list</h2>
      <ul class="list-unstyled small">
        <?php foreach (array_slice($assignments, 0, 5) as $a): ?>
          <li class="mb-1">
            <a href="<?php echo htmlspecialchars(teacher_assignment_url((int) $a['assignment_id'], $yearId), ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars((string) $a['subject_title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <span class="text-muted"> — <?php echo htmlspecialchars((string) ($a['display_name'] ?: $a['class_code']), ENT_QUOTES, 'UTF-8'); ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if (count($assignments) > 5): ?>
        <p class="small"><a href="teacher_assignments.php<?php echo teacher_year_q($yearId); ?>">View all <?php echo (int) count($assignments); ?> assignments</a></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
