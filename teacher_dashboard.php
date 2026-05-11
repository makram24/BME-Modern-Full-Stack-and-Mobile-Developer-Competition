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

$yq = teacher_year_q($yearId);
$eventsQ = $yearId > 0 ? ('?year_id=' . $yearId) : '';

ob_start();
?>
<div class="portal-page-head">
  <h1>Hello, <?php echo htmlspecialchars((string) (current_username() ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>
  <p class="text-muted">Manage rosters and grades from <strong>My subjects</strong>. Grades use integers <strong>1–5</strong> everywhere.</p>
</div>

<?php if ($yearRow === null): ?>
  <div class="alert alert-warning border-0 shadow-sm">You have no class assignments yet. An administrator must assign you to a subject for a class and year.</div>
<?php else: ?>
  <div class="portal-card p-3 p-md-4 mb-4">
    <p class="mb-1 small text-muted text-uppercase" style="letter-spacing:0.05em">Working year</p>
    <p class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars((string) $yearRow['label'], ENT_QUOTES, 'UTF-8'); ?></p>
  </div>

  <?php $yearsOpts = teacher_assigned_years_detail($mysqli, $tid); ?>
  <?php if (count($yearsOpts) > 1): ?>
    <form class="portal-card p-3 mb-4" method="get" action="teacher_dashboard.php">
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
  <div class="portal-tiles mb-4">
    <a class="portal-tile portal-tile--primary" href="teacher_assignments.php<?php echo htmlspecialchars($yq, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-collection portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">My subjects</span>
      <span class="portal-tile__hint">Open a class to enter grades and view the roster.</span>
    </a>
    <a class="portal-tile" href="teacher_timetable.php<?php echo htmlspecialchars($yq, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-calendar3 portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">My timetable</span>
      <span class="portal-tile__hint">Your teaching periods for this year.</span>
    </a>
    <a class="portal-tile" href="events.php<?php echo htmlspecialchars($eventsQ, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi bi-calendar-event portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label">School events</span>
      <span class="portal-tile__hint">Campus calendar (read-only).</span>
    </a>
  </div>

  <?php if ($assignments !== []): ?>
    <h2 class="h6 mb-2">Recent subjects</h2>
    <ul class="list-group list-group-flush portal-card overflow-hidden mb-0">
      <?php foreach (array_slice($assignments, 0, 6) as $a): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
          <a class="fw-medium text-decoration-none" href="<?php echo htmlspecialchars(teacher_assignment_url((int) $a['assignment_id'], $yearId), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) $a['subject_title'], ENT_QUOTES, 'UTF-8'); ?>
          </a>
          <span class="small text-muted"><?php echo htmlspecialchars((string) ($a['display_name'] ?: $a['class_code']), ENT_QUOTES, 'UTF-8'); ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($assignments) > 6): ?>
      <p class="small text-muted mt-2 mb-0">
        <a href="teacher_assignments.php<?php echo htmlspecialchars($yq, ENT_QUOTES, 'UTF-8'); ?>">View all <?php echo (int) count($assignments); ?> subjects</a>
      </p>
    <?php endif; ?>
  <?php endif; ?>
<?php endif; ?>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
