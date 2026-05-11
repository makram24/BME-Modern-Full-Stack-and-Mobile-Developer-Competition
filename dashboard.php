<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';

$displayName = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'User';
$roleLabel = isset($_SESSION['role']) ? htmlspecialchars(str_replace('_', ' ', (string) $_SESSION['role']), ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1 class="h3">Welcome, <?php echo $displayName; ?></h1>
    <?php if ($roleLabel !== ''): ?>
      <p class="text-secondary mb-2"><span class="badge bg-secondary text-uppercase"><?php echo $roleLabel; ?></span></p>
    <?php endif; ?>
    <p class="text-muted">You are signed in. Replace this page with your portal home.</p>
    <a class="btn btn-outline-secondary" href="logout.php">Log out</a>
  </div>
</body>
</html>
