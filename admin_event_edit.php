<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';
require_once __DIR__ . '/includes/events_data.php';

$id = (int) ($_GET['id'] ?? $_POST['event_id'] ?? 0);
if ($id <= 0) {
    admin_redirect('admin_events.php', 'error=' . rawurlencode('Invalid event.'));
}

$ev = admin_event_by_id($mysqli, $id);
if ($ev === null) {
    admin_redirect('admin_events.php', 'error=' . rawurlencode('Event not found.'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_event_edit.php', 'id=' . $id . '&error=' . rawurlencode('Invalid session token.'));
    }
    $action = (string) ($_POST['form_action'] ?? '');

    if ($action === 'delete') {
        $del = $mysqli->prepare('DELETE FROM campus_events WHERE id = ? LIMIT 1');
        if ($del) {
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();
        }
        csrf_rotate();
        admin_redirect('admin_events.php', 'saved=1');
    }

    if ($action !== 'save') {
        admin_redirect('admin_event_edit.php', 'id=' . $id . '&error=' . rawurlencode('Unknown action.'));
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $startsRaw = trim((string) ($_POST['starts_at'] ?? ''));
    $endsRaw = trim((string) ($_POST['ends_at'] ?? ''));
    $yearId = (int) ($_POST['academic_year_id'] ?? 0);

    if ($title === '' || $startsRaw === '') {
        admin_redirect('admin_event_edit.php', 'id=' . $id . '&error=' . rawurlencode('Title and start date/time are required.'));
    }
    $startTs = strtotime($startsRaw);
    if ($startTs === false) {
        admin_redirect('admin_event_edit.php', 'id=' . $id . '&error=' . rawurlencode('Invalid start date/time.'));
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

    if ($yearId > 0) {
        $stmt = $mysqli->prepare(
            'UPDATE campus_events SET title = ?, description = ?, starts_at = ?, ends_at = ?, location = ?, academic_year_id = ? WHERE id = ?'
        );
        if ($stmt) {
            $stmt->bind_param('sssssii', $title, $descVal, $startsAt, $endsAt, $locVal, $yearId, $id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $mysqli->prepare(
            'UPDATE campus_events SET title = ?, description = ?, starts_at = ?, ends_at = ?, location = ?, academic_year_id = NULL WHERE id = ?'
        );
        if ($stmt) {
            $stmt->bind_param('sssssi', $title, $descVal, $startsAt, $endsAt, $locVal, $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    csrf_rotate();
    admin_redirect('admin_event_edit.php', 'id=' . $id . '&saved=1');
}

$years = events_academic_years_for_select($mysqli);

/** Format MySQL datetime for datetime-local input. */
function event_format_datetime_local(?string $mysqlDt): string
{
    if ($mysqlDt === null || $mysqlDt === '') {
        return '';
    }
    $ts = strtotime($mysqlDt);

    return $ts !== false ? date('Y-m-d\TH:i', $ts) : '';
}

$shell_title = 'Edit event';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-8">
    <h1 class="h3 mb-3">Edit event</h1>
    <p class="small"><a href="admin_events.php">← All events</a></p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm mb-3">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="event_id" value="<?php echo (int) $ev['id']; ?>">
      <input type="hidden" name="form_action" value="save">

      <div class="mb-3">
        <label class="form-label">Title</label>
        <input class="form-control" name="title" required maxlength="255" value="<?php echo htmlspecialchars((string) $ev['title'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="row g-2 mb-3">
        <div class="col-md-6">
          <label class="form-label">Starts</label>
          <input class="form-control" type="datetime-local" name="starts_at" required value="<?php echo htmlspecialchars(event_format_datetime_local((string) $ev['starts_at']), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Ends (optional)</label>
          <input class="form-control" type="datetime-local" name="ends_at" value="<?php echo htmlspecialchars(event_format_datetime_local(isset($ev['ends_at']) ? (string) $ev['ends_at'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Academic year filter (optional)</label>
        <select class="form-select" name="academic_year_id">
          <option value="0"<?php echo empty($ev['academic_year_id']) ? ' selected' : ''; ?>>All years</option>
          <?php foreach ($years as $y): ?>
            <option value="<?php echo (int) $y['id']; ?>"<?php echo (int) $y['id'] === (int) ($ev['academic_year_id'] ?? 0) ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars((string) $y['label'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Location (optional)</label>
        <input class="form-control" name="location" maxlength="255" value="<?php echo htmlspecialchars((string) ($ev['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Description (optional)</label>
        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars((string) ($ev['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <form method="post" onsubmit="return confirm('Delete this event permanently?');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="event_id" value="<?php echo (int) $ev['id']; ?>">
      <input type="hidden" name="form_action" value="delete">
      <button type="submit" class="btn btn-outline-danger btn-sm">Delete event</button>
    </form>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
