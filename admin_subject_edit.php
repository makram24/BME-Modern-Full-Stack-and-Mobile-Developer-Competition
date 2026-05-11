<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';

$id = (int) ($_GET['id'] ?? $_POST['subject_id'] ?? 0);
if ($id <= 0) {
    admin_redirect('admin_subjects.php', 'error=' . rawurlencode('Invalid subject.'));
}

$stmt = $mysqli->prepare('SELECT id, title, description, required_books, lessons_outline FROM subjects WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($sub === null) {
    admin_redirect('admin_subjects.php', 'error=' . rawurlencode('Subject not found.'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_subject_edit.php', 'id=' . $id . '&error=' . rawurlencode('Invalid session token.'));
    }
    $action = (string) ($_POST['form_action'] ?? '');

    if ($action === 'delete') {
        $chk = $mysqli->prepare('SELECT COUNT(*) AS c FROM class_subject_assignments WHERE subject_id = ?');
        $chk->bind_param('i', $id);
        $chk->execute();
        $cnt = (int) ($chk->get_result()->fetch_assoc()['c'] ?? 0);
        $chk->close();
        if ($cnt > 0) {
            admin_redirect('admin_subject_edit.php', 'id=' . $id . '&error=' . rawurlencode('Cannot delete: subject is assigned to classes.'));
        }
        $del = $mysqli->prepare('DELETE FROM subjects WHERE id = ? LIMIT 1');
        if ($del) {
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();
        }
        csrf_rotate();
        admin_redirect('admin_subjects.php', 'saved=1');
    }

    if ($action !== 'save') {
        admin_redirect('admin_subject_edit.php', 'id=' . $id . '&error=' . rawurlencode('Unknown action.'));
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $books = trim((string) ($_POST['required_books'] ?? ''));
    $lessons = trim((string) ($_POST['lessons_outline'] ?? ''));
    if ($title === '') {
        admin_redirect('admin_subject_edit.php', 'id=' . $id . '&error=' . rawurlencode('Title is required.'));
    }
    $stmt = $mysqli->prepare(
        'UPDATE subjects SET title = ?, description = ?, required_books = ?, lessons_outline = ? WHERE id = ?'
    );
    if ($stmt) {
        $stmt->bind_param('ssssi', $title, $desc, $books, $lessons, $id);
        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                $stmt->close();
                admin_redirect('admin_subject_edit.php', 'id=' . $id . '&error=' . rawurlencode('Another subject already uses that title.'));
            }
        }
        $stmt->close();
    }
    csrf_rotate();
    admin_redirect('admin_subject_edit.php', 'id=' . $id . '&saved=1');
}

$shell_title = 'Edit subject';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-8">
    <h1 class="h3 mb-3">Edit subject</h1>
    <p class="small"><a href="admin_subjects.php">← Catalog</a></p>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm mb-3">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="subject_id" value="<?php echo (int) $sub['id']; ?>">
      <input type="hidden" name="form_action" value="save">

      <div class="mb-3">
        <label class="form-label">Title</label>
        <input class="form-control" name="title" required maxlength="160" value="<?php echo htmlspecialchars((string) $sub['title'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars((string) ($sub['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Required books</label>
        <textarea class="form-control" name="required_books" rows="2"><?php echo htmlspecialchars((string) ($sub['required_books'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Lessons outline</label>
        <textarea class="form-control" name="lessons_outline" rows="3"><?php echo htmlspecialchars((string) ($sub['lessons_outline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <form method="post" onsubmit="return confirm('Delete this subject permanently?');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="subject_id" value="<?php echo (int) $sub['id']; ?>">
      <input type="hidden" name="form_action" value="delete">
      <button type="submit" class="btn btn-outline-danger btn-sm">Delete subject</button>
    </form>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
