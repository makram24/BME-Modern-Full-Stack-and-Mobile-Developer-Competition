<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/includes/session_bootstrap.php';
portal_ensure_session_started();

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/dbConnect.php';

function auth_redirect_error(string $message): void
{
    ob_end_clean();
    $next = sanitize_next($_POST['next'] ?? '');
    header('Location: index.php?error=' . urlencode($message) . '&next=' . rawurlencode($next));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: index.php');
    exit();
}

if (!csrf_verify_post()) {
    auth_redirect_error('Invalid request. Please try signing in again.');
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($username === '' || $password === '') {
    auth_redirect_error('Please enter both username and password');
}

try {
    $stmt = $mysqli->prepare(
        'SELECT id, username, password, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1'
    );
    if ($stmt === false) {
        portal_log('auth prepare failed', ['mysqli_error' => $mysqli->error]);
        auth_redirect_error('Login temporarily unavailable. Please try again later.');
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();

    // Avoid mysqli_stmt::get_result() — not available on PHP builds without mysqlnd (common on shared hosting → HTTP 500).
    $uid = 0;
    $uname = '';
    $hash = '';
    $role = '';
    $stmt->bind_result($uid, $uname, $hash, $role);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found || $uid <= 0 || !password_verify($password, $hash)) {
        auth_redirect_error('Invalid username or password');
    }

    $user = ['id' => $uid, 'username' => $uname, 'password' => $hash, 'role' => $role];

    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $rehash = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
        if ($rehash) {
            $uidInt = (int) $user['id'];
            $rehash->bind_param('si', $newHash, $uidInt);
            $rehash->execute();
            $rehash->close();
        }
    }
} catch (Throwable $e) {
    portal_log('auth database error', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    auth_redirect_error('Login temporarily unavailable. Please try again later.');
}

if (!session_regenerate_id(true)) {
    portal_log('auth session_regenerate_id failed', []);
}

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'] ?? 'student';
$_SESSION['login_time'] = time();

csrf_rotate();

$next = sanitize_next($_POST['next'] ?? '');

ob_end_clean();
header('Location: ' . $next);
exit();
