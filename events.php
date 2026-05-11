<?php
declare(strict_types=1);

/**
 * Phase 10.3 — read-only campus calendar for all signed-in roles (CRUD is admin_events.php).
 */
require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/events_data.php';

$filterYear = (int) ($_GET['year_id'] ?? 0);
$events = events_list_for_portal($mysqli, $filterYear);

$shell_title = 'Events';
$shell_nav_items = [
    [role_home_url(), 'Dashboard'],
    ['events.php' . ($filterYear > 0 ? '?year_id=' . $filterYear : ''), 'Events'],
    ['logout.php', 'Log out'],
];

ob_start();
?>
<div class="row">
  <div class="col-lg-10">
    <h1 class="h3 mb-3">School events</h1>
    <p class="text-muted small mb-3">Read-only list. School administrators create and edit events under <strong>Administrator → Events</strong>.</p>

    <?php if ($filterYear > 0): ?>
      <p class="small mb-3"><a href="events.php">Show events for all years</a></p>
    <?php endif; ?>

    <?php if ($events === []): ?>
      <div class="alert alert-info border-0 shadow-sm">No events scheduled<?php echo $filterYear > 0 ? ' for this filter.' : '.'; ?></div>
    <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($events as $ev): ?>
          <article class="portal-card p-3 p-md-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
              <div>
                <h2 class="h6 mb-1"><?php echo htmlspecialchars((string) $ev['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <?php if (!empty($ev['location'])): ?>
                  <p class="small mb-1 text-muted">
                    <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>
                    <?php echo htmlspecialchars((string) $ev['location'], ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                <?php endif; ?>
              </div>
              <div class="text-end small text-muted">
                <div>
                  <?php echo htmlspecialchars((string) $ev['starts_at'], ENT_QUOTES, 'UTF-8'); ?>
                  <?php if (!empty($ev['ends_at'])): ?>
                    <span class="mx-1">→</span><?php echo htmlspecialchars((string) $ev['ends_at'], ENT_QUOTES, 'UTF-8'); ?>
                  <?php endif; ?>
                </div>
                <?php if (!empty($ev['year_label'])): ?>
                  <div>Year: <?php echo htmlspecialchars((string) $ev['year_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php elseif (!empty($ev['academic_year_id'])): ?>
                  <div>Year ID <?php echo (int) $ev['academic_year_id']; ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($ev['description'])): ?>
              <p class="small mb-0 text-body-secondary"><?php echo nl2br(htmlspecialchars((string) $ev['description'], ENT_QUOTES, 'UTF-8')); ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
