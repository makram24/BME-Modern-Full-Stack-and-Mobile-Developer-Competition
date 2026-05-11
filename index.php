<?php
// Prevent output buffering issues
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// If already logged in, redirect to dashboard
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: dashboard.php");
    exit();
}
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
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Jost:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  
  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Poppins', sans-serif;
      color: #d6d6d6;
    }
    
    .login-container {
      width: 100%;
      max-width: 450px;
      padding: 20px;
    }
    
    .login-card {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 20px;
      padding: 40px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-header img {
      width: 150px;
      margin-bottom: 20px;
    }
    
    .login-header h1 {
      color: #fff;
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 10px;
    }
    
    .login-header p {
      color: #999;
      font-size: 0.95rem;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-label {
      display: block;
      color: #fff;
      font-size: 0.9rem;
      font-weight: 500;
      margin-bottom: 8px;
    }
    
    .form-label i {
      margin-right: 8px;
      color: #4dabf7;
    }
    
    .form-control {
      width: 100%;
      padding: 12px 15px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      color: #fff;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #4dabf7;
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(77, 171, 247, 0.2);
    }
    
    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }
    
    .btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #4dabf7 0%, #339af0 100%);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(77, 171, 247, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(77, 171, 247, 0.4);
    }
    
    .alert {
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
    }
    
    .alert-danger {
      background: rgba(255, 107, 107, 0.2);
      border: 1px solid rgba(255, 107, 107, 0.5);
      color: #ff6b6b;
    }
    
    .alert-success {
      background: rgba(81, 207, 102, 0.2);
      border: 1px solid rgba(81, 207, 102, 0.5);
      color: #51cf66;
    }
    
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    
    .back-link a {
      color: #999;
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.3s ease;
    }
    
    .back-link a:hover {
      color: #4dabf7;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="assets/img/logo light.webp" alt="Logo">
        <h1><i class="bi bi-shield-lock"></i> Sign in</h1>
        <p>Enter your credentials to access the portal</p>
      </div>
      
      <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
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
        <span style="color:#666;margin:0 8px">·</span>
        <a href="index.php"><i class="bi bi-arrow-left"></i> Back to Home</a>
      </div>
    </div>
  </div>
</body>
</html>

