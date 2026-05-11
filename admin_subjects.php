<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/includes/authz.php';
require_roles('administrator');

require_once __DIR__ . '/dbConnect.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/admin_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!csrf_verify_post()) {
        admin_redirect('admin_subjects.php', 'error=' . rawurlencode('Invalid session token.'));
    }
    $title = trim((string) ($_POST['title'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $books = trim((string) ($_POST['required_books'] ?? ''));
    $lessons = trim((string) ($_POST['lessons_outline'] ?? ''));
    if ($title === '') {
        admin_redirect('admin_subjects.php', 'error=' . rawurlencode('Title is required.'));
    }
    $stmt = $mysqli->prepare(
        'INSERT INTO subjects (title, description, required_books, lessons_outline) VALUES (?,?,?,?)'
    );
    if ($stmt) {
        $stmt->bind_param('ssss', $title, $desc, $books, $lessons);
        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                $stmt->close();
                admin_redirect('admin_subjects.php', 'error=' . rawurlencode('A subject with that title already exists.'));
            }
        }
        $stmt->close();
    }
    csrf_rotate();
    admin_redirect('admin_subjects.php', 'saved=1');
}

$res = $mysqli->query('SELECT id, title, description, required_books, lessons_outline FROM subjects ORDER BY title ASC');
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$shell_title = 'Subjects';
$shell_nav_items = admin_portal_nav_items();

ob_start();
?>
<div class="row">
  <div class="col-lg-11">
    <h1 class="h3 mb-3">Subject catalog</h1>

    <?php if (!empty($_GET['saved'])): ?>
      <div class="alert alert-success py-2">Saved.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header">Add subject</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="create">
          <div class="mb-2">
            <label class="form-label small">Title (unique)</label>
            <input class="form-control form-control-sm" name="title" required maxlength="160">
          </div>
          <div class="mb-2">
            <label class="form-label small">Description</label>
            <textarea class="form-control form-control-sm" name="description" rows="2"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label small">Required books</label>
            <textarea class="form-control form-control-sm" name="required_books" rows="2"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label small">Lessons outline</label>
            <textarea class="form-control form-control-sm" name="lessons_outline" rows="2"></textarea>
          </div>
          <button type="submit" class="btn btn-sm btn-primary">Add subject</button>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>ID</th><th>Title</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo (int) $r['id']; ?></td>
              <td><?php echo htmlspecialchars((string) $r['title'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><a class="btn btn-sm btn-outline-secondary" href="admin_subject_edit.php?id=<?php echo (int) $r['id']; ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$shell_body_html = ob_get_clean();
require __DIR__ . '/includes/dashboard_shell.php';
