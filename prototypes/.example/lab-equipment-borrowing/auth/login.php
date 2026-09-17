<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Lab Equipment Borrowing System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

  <header class="site-header">
    <div class="brand">
      <span class="logo-dot"></span>
      <h2>Lab Equipment Borrowing System</h2>
    </div>
    <nav>
      <a href="register.php">Register</a>
      <a href="admin_login.php">Admin Login</a>
    </nav>
  </header>

  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-icon">&#128274;</div>
      <h1>Welcome Back</h1>
      <p class="auth-subtitle">Login to your Student / Faculty account</p>

      <!-- Static demo: success state (sample only) -->
      <div class="alert alert-success">
        <span>&#9989;</span>
        <div>Registration successful! Please log in to continue.</div>
      </div>

      <form class="styled-form" action="login.php" method="POST">

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" value="ankita.roy@example.com">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password">

        <div class="checkbox-row">
          <label><input type="checkbox" name="remember" checked> Remember me</label>
          <a href="forgot_password.php">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-block">Login</button>
      </form>

      <div class="auth-divider"><span>OR</span></div>

      <p class="auth-footer-link">
        Don't have an account? <a href="register.php">Register here</a>
      </p>
    </div>
  </div>

  <footer class="site-footer">
    <p>&copy; Lab Equipment Borrowing System</p>
  </footer>

  <script src="../assets/js/script.js"></script>
</body>
</html>
