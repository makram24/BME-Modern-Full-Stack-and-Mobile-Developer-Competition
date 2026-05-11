<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/includes/admin_common.php';

$shell_title = 'Administrator';
$shell_nav_items = admin_portal_nav_items();

$links = [
    ['admin_users.php', 'Users', 'Create and edit students and teachers (passwords hashed).'],
    ['admin_classes.php', 'Classes', 'Start date + class code (identifier), display name.'],
    ['admin_subjects.php', 'Subjects', 'Catalog: title, description, books, lesson outline.'],
    ['admin_enrollments.php', 'Enrolments', 'Place students into a class for an academic year.'],
    ['admin_assignments.php', 'Assignments', 'Assign subject + teacher to a class for a year; set weekday + lesson period (timetable).'],
    ['admin_events.php', 'Events', 'Campus calendar (visible read-only to students and teachers).'],
];

ob_start();
?>
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-3">School administrator</h1>
    <p class="text-muted">Set up academic years, classes, subjects, enrolments, and teacher assignments. You cannot edit <strong>super-administrator</strong> accounts here.</p>

    <div class="row g-3 mt-2">
      <?php foreach ($links as $L): ?>
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h2 class="h5 card-title"><a href="<?php echo htmlspecialchars($L[0], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($L[1], ENT_QUOTES, 'UTF-8'); ?></a></h2>
              <p class="card-text small text-muted mb-0"><?php echo htmlspecialchars($L[2], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
