<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/includes/security.php';

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
    ob_end_clean();
    $dest = sanitize_next($_GET['next'] ?? '');
    header('Location: ' . $dest);
    exit();
}

$nextField = sanitize_next($_GET['next'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Sign in — Portal</title>
  
  <!-- Favicons -->
  <link href="assets/img/favicon.webp" rel="icon">
  <link href="assets/img/apple-touch-icon.webp" rel="apple-touch-icon">
  
  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="assets/img/logo light.webp" alt="Logo">
        <h1><i class="bi bi-shield-lock"></i> Sign in</h1>
        <p>Enter your credentials to access the portal</p>
      </div>
      
      <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
      
      <?php if(isset($_GET['logout'])): ?>
        <div class="alert alert-success">
          <i class="bi bi-check-circle"></i> You have been successfully logged out.
        </div>
      <?php endif; ?>

      <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success">
          <i class="bi bi-check-circle"></i> Your account was created. You can sign in below.
        </div>
      <?php endif; ?>
      
      <form action="auth.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="next" value="<?php echo htmlspecialchars($nextField, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
          <label class="form-label">
            <i class="bi bi-person"></i> Username
          </label>
          <input type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
        </div>
        
        <div class="form-group">
          <label class="form-label">
            <i class="bi bi-lock"></i> Password
          </label>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        
        <button type="submit" class="btn-login">
          <i class="bi bi-box-arrow-in-right"></i> Login
        </button>
      </form>
      
      <div class="back-link">
        <a href="register.php">Create an account</a>
      </div>
    </div>
  </div>
</body>
</html>

