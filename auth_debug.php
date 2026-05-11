<?php
/**
 * Local troubleshooting: logs steps to PHP error_log.
 * Remove or block public access on production hosting.
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/dbConnect.php';

function debug_auth_redirect(string $url): void
{
    ob_end_clean();
    header('Location: ' . $url);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_auth_redirect('index.php');
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

error_log('auth_debug: POST received for user=' . $username);

if ($username === '' || $password === '') {
    debug_auth_redirect('index.php?error=' . urlencode('Please enter both username and password'));
}

$stmt = $mysqli->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
if ($stmt === false) {
    error_log('auth_debug: prepare failed ' . $mysqli->error);
    debug_auth_redirect('index.php?error=' . urlencode('Login temporarily unavailable. Please try again later.'));
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user === null) {
    error_log('auth_debug: no user row for username');
    debug_auth_redirect('index.php?error=' . urlencode('Invalid username or password'));
}

if (!password_verify($password, $user['password'])) {
    error_log('auth_debug: password_verify failed');
    debug_auth_redirect('index.php?error=' . urlencode('Invalid username or password'));
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

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'] ?? 'student';
$_SESSION['login_time'] = time();

error_log('auth_debug: login OK user_id=' . $_SESSION['user_id']);

ob_end_clean();
header('Location: dashboard.php');
exit();
