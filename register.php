<?php
/**
 * Self-registration for student and teacher accounts only.
 * administrator / super_administrator must be assigned in the database.
 */
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
    ob_end_clean();
    header('Location: dashboard.php');
    exit();
}

$registerableRoles = [
    'student' => 'Student',
    'teacher' => 'Teacher',
];

function register_redirect(string $query): void
{
    ob_end_clean();
    header('Location: register.php?' . $query);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/dbConnect.php';

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $passwordConfirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';

    if ($username === '' || $password === '' || $passwordConfirm === '') {
        register_redirect('error=' . urlencode('Please fill in all fields.'));
    }

    if (!isset($registerableRoles[$role])) {
        register_redirect('error=' . urlencode('Invalid account type selected.'));
    }

    if (strlen($username) < 3 || strlen($username) > 64) {
        register_redirect('error=' . urlencode('Username must be between 3 and 64 characters.'));
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        register_redirect('error=' . urlencode('Username may only contain letters, numbers, and underscores.'));
    }

    if (strlen($password) < 8) {
        register_redirect('error=' . urlencode('Password must be at least 8 characters.'));
    }

    if ($password !== $passwordConfirm) {
        register_redirect('error=' . urlencode('Passwords do not match.'));
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('INSERT INTO users (username, password, role, is_active) VALUES (?, ?, ?, 1)');
    if ($stmt === false) {
        error_log('register prepare failed: ' . $mysqli->error);
        register_redirect('error=' . urlencode('Registration is temporarily unavailable. Run sql/001_add_user_role.sql and sql/002_phase1_domain.sql if the database is not migrated.'));
    }

    $stmt->bind_param('sss', $username, $hash, $role);
    if (!$stmt->execute()) {
        if ($stmt->errno === 1062) {
            $stmt->close();
            register_redirect('error=' . urlencode('That username is already taken.'));
        }
        error_log('register execute failed: ' . $stmt->error);
        $stmt->close();
        register_redirect('error=' . urlencode('Could not create account. Please try again.'));
    }
    $stmt->close();

    ob_end_clean();
    header('Location: index.php?registered=1');
    exit();
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Create account — Portal</title>
  <link href="assets/img/favicon.webp" rel="icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Jost:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="card-wrap">
    <div class="card-panel">
      <h1><i class="bi bi-person-plus"></i> Create account</h1>
      <p class="sub">Register as a student or teacher. Administrator accounts are created separately.</p>

      <?php if (!empty($_GET['error'])): ?>
        <div class="alert-danger"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="post" action="register.php" autocomplete="off">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" type="text" placeholder="letters, numbers, underscore" required minlength="3" maxlength="64" pattern="[a-zA-Z0-9_]+">

        <label class="form-label" for="role">I am a</label>
        <select class="form-select" id="role" name="role" required>
          <?php foreach ($registerableRoles as $value => $label): ?>
            <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>

        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password" placeholder="at least 8 characters" required minlength="8" autocomplete="new-password">

        <label class="form-label" for="password_confirm">Confirm password</label>
        <input class="form-control" id="password_confirm" name="password_confirm" type="password" required minlength="8" autocomplete="new-password">

        <button type="submit" class="btn-submit">Create account</button>
      </form>

      <div class="footer-link">
        <a href="index.php"><i class="bi bi-arrow-left"></i> Back to sign in</a>
      </div>
    </div>
  </div>
</body>
</html>
