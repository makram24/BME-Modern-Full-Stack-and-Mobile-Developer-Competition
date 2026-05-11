<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/includes/admin_common.php';

$shell_title = 'Administrator';
$shell_nav_items = admin_portal_nav_items();

$sections = [
    ['admin_users.php', 'Users', 'Students and teachers (secure passwords).', 'bi-people'],
    ['admin_classes.php', 'Classes', 'Class code, start date, display name.', 'bi-building'],
    ['admin_subjects.php', 'Subjects', 'Catalog: title, description, books, outline.', 'bi-journal-text'],
    ['admin_enrollments.php', 'Enrolments', 'Place students in a class for a year.', 'bi-person-badge'],
    ['admin_assignments.php', 'Assignments', 'Teachers, subjects, and timetable periods.', 'bi-link-45deg'],
    ['admin_events.php', 'Events', 'Campus calendar visible to everyone.', 'bi-calendar-event'],
];

ob_start();
?>
<div class="portal-page-head">
  <h1>School administrator</h1>
  <p class="text-muted">Set up years, classes, subjects, enrolments, and teaching assignments. You cannot edit <strong>super-administrator</strong> accounts here.</p>
</div>

<div class="portal-tiles">
  <?php foreach ($sections as $L): ?>
    <a class="portal-tile" href="<?php echo htmlspecialchars($L[0], ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi <?php echo htmlspecialchars($L[3], ENT_QUOTES, 'UTF-8'); ?> portal-tile__icon" aria-hidden="true"></i>
      <span class="portal-tile__label"><?php echo htmlspecialchars($L[1], ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="portal-tile__hint"><?php echo htmlspecialchars($L[2], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
