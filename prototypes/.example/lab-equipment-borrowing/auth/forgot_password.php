<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — Lab Equipment Borrowing System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

  <header class="site-header">
    <div class="brand">
      <span class="logo-dot"></span>
      <h2>Lab Equipment Borrowing System</h2>
    </div>
    <nav>
      <a href="login.php">Borrower Login</a>
      <a href="admin_login.php">Admin Login</a>
    </nav>
  </header>

  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-icon">&#9993;</div>
      <h1>Forgot Password</h1>
      <p class="auth-subtitle">Enter your registered email and we'll send you a reset link</p>

      <div class="alert alert-success">
        <span>&#9989;</span>
        <div>A password reset link has been sent to your email address.</div>
      </div>

      <form class="styled-form" action="forgot_password.php" method="POST">

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" value="ankita.roy@example.com">

        <button type="submit" class="btn btn-block">Send Reset Link</button>
      </form>

      <p class="auth-footer-link">
        Remembered your password? <a href="login.php">Back to Login</a>
      </p>
    </div>
  </div>

  <footer class="site-footer">
    <p>&copy; Lab Equipment Borrowing System</p>
  </footer>

  <script src="../assets/js/script.js"></script>
</body>
</html>