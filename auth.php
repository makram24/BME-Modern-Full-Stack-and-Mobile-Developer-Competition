<?php
declare(strict_types=1);

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$stmt = $mysqli->prepare(
    'SELECT id, username, password, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1'
);
if ($stmt === false) {
    portal_log('auth prepare failed', ['mysqli_error' => $mysqli->error]);
    auth_redirect_error('Login temporarily unavailable. Please try again later.');
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user === null || !password_verify($password, $user['password'])) {
    auth_redirect_error('Invalid username or password');
}

if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $rehash = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
    if ($rehash) {
        $uid = (int) $user['id'];
        $rehash->bind_param('si', $newHash, $uid);
        $rehash->execute();
        $rehash->close();
    }
}

session_regenerate_id(true);

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
