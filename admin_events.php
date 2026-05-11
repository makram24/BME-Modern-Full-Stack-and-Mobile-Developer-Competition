<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';
require_once __DIR__ . '/includes/events_data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_events.php', 'error=' . rawurlencode('Invalid session token.'));
    }
    $action = (string) ($_POST['action'] ?? '');
    if ($action !== 'create') {
        admin_redirect('admin_events.php', 'error=' . rawurlencode('Unknown action.'));
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $startsRaw = trim((string) ($_POST['starts_at'] ?? ''));
    $endsRaw = trim((string) ($_POST['ends_at'] ?? ''));
    $yearId = (int) ($_POST['academic_year_id'] ?? 0);

    if ($title === '' || $startsRaw === '') {
        admin_redirect('admin_events.php', 'error=' . rawurlencode('Title and start date/time are required.'));
    }
    $startTs = strtotime($startsRaw);
    if ($startTs === false) {
        admin_redirect('admin_events.php', 'error=' . rawurlencode('Invalid start date/time.'));
    }
    $startsAt = date('Y-m-d H:i:s', $startTs);
    $endsAt = null;
    if ($endsRaw !== '') {
        $e = strtotime($endsRaw);
        if ($e !== false) {
            $endsAt = date('Y-m-d H:i:s', $e);
        }
    }
    $descVal = $description === '' ? null : $description;
    $locVal = $location === '' ? null : $location;
    $uid = (int) current_user_id();

    if ($yearId > 0) {
        $stmt = $mysqli->prepare(
            'INSERT INTO campus_events (title, description, starts_at, ends_at, location, academic_year_id, created_by_user_id)
             VALUES (?,?,?,?,?,?,?)'
        );
        if ($stmt === false) {
            portal_log('admin_events create prepare failed', ['error' => $mysqli->error]);
            admin_redirect('admin_events.php', 'error=' . rawurlencode('Database error (did you run sql/007_campus_events.sql?).'));
        }
        $stmt->bind_param('sssssii', $title, $descVal, $startsAt, $endsAt, $locVal, $yearId, $uid);
    } else {
        $stmt = $mysqli->prepare(
            'INSERT INTO campus_events (title, description, starts_at, ends_at, location, academic_year_id, created_by_user_id)
             VALUES (?,?,?,?,?,NULL,?)'
        );
        if ($stmt === false) {
            portal_log('admin_events create prepare failed', ['error' => $mysqli->error]);
            admin_redirect('admin_events.php', 'error=' . rawurlencode('Database error (did you run sql/007_campus_events.sql?).'));
        }
        $stmt->bind_param('sssssi', $title, $descVal, $startsAt, $endsAt, $locVal, $uid);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        admin_redirect('admin_events.php', 'error=' . rawurlencode('Could not create event.'));
    }
    $stmt->close();
    csrf_rotate();
    admin_redirect('admin_events.php', 'saved=1');
}

$res = $mysqli->query(
    'SELECT e.id, e.title, e.starts_at, e.ends_at, e.location, e.academic_year_id, ay.label AS year_label
     FROM campus_events e
     LEFT JOIN academic_years ay ON ay.id = e.academic_year_id
     ORDER BY e.starts_at DESC, e.id DESC'
);
if ($res === false) {
    portal_log('admin_events list failed', ['errno' => $mysqli->errno, 'error' => $mysqli->error]);
    $events = [];
} else {
    $events = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();
}

$years = events_academic_years_for_select($mysqli);

$shell_title = 'Events';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <h1 class="h3 mb-3">Campus events</h1>
    <p class="text-muted small">Create and edit school-wide events. Students and teachers see them on <strong>Events</strong> (read-only).</p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header">Add event</div>
      <div class="card-body">
        <form method="post" class="row g-2">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="create">
          <div class="col-md-4">
            <label class="form-label small">Title</label>
            <input class="form-control form-control-sm" name="title" required maxlength="255" placeholder="Parents evening">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Starts</label>
            <input class="form-control form-control-sm" type="datetime-local" name="starts_at" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Ends (optional)</label>
            <input class="form-control form-control-sm" type="datetime-local" name="ends_at">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Academic year (optional)</label>
            <select class="form-select form-select-sm" name="academic_year_id">
              <option value="0">All years</option>
              <?php foreach ($years as $y): ?>
                <option value="<?php echo (int) $y['id']; ?>"><?php echo htmlspecialchars((string) $y['label'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small">Location (optional)</label>
            <input class="form-control form-control-sm" name="location" maxlength="255" placeholder="Main hall">
          </div>
          <div class="col-md-6">
            <label class="form-label small">Description (optional)</label>
            <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Details for families…"></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-sm btn-primary">Create</button>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th>When</th>
            <th>Title</th>
            <th>Year filter</th>
            <th>Location</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <tr>
              <td class="small text-nowrap"><?php echo htmlspecialchars((string) $ev['starts_at'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($ev['ends_at'])): ?>
                  <br><span class="text-muted">→ <?php echo htmlspecialchars((string) $ev['ends_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars((string) $ev['title'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="small">
                <?php if (!empty($ev['academic_year_id'])): ?>
                  <?php echo htmlspecialchars((string) ($ev['year_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                <?php else: ?>
                  <span class="text-muted">All years</span>
                <?php endif; ?>
              </td>
              <td class="small"><?php echo htmlspecialchars((string) ($ev['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><a class="btn btn-sm btn-outline-secondary" href="admin_event_edit.php?id=<?php echo (int) $ev['id']; ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($events === []): ?>
      <p class="text-muted small">No events yet.</p>
    <?php endif; ?>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
