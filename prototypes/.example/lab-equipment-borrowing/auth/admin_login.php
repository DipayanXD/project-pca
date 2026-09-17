<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Lab Equipment Borrowing System</title>
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
    </nav>
  </header>

  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-icon">&#128737;</div>
      <h1>Admin Login</h1>
      <p class="auth-subtitle">Restricted access — Lab In-Charge only</p>

      <!-- Static demo: error state (sample only) -->
      <div class="alert alert-error">
        <span>&#9888;</span>
        <div>Invalid admin username or password.</div>
      </div>

      <form class="styled-form" action="admin_login.php" method="POST">

        <label for="username">Admin Username</label>
        <input type="text" id="username" name="username" placeholder="Enter admin username" value="admin">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter admin password">

        <div class="checkbox-row">
          <label><input type="checkbox" name="remember"> Remember me</label>
          <a href="forgot_password.php">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-block">Login as Admin</button>
      </form>

      <p class="auth-footer-link">
        Not an admin? <a href="login.php">Go to Borrower Login</a>
      </p>
    </div>
  </div>

  <footer class="site-footer">
    <p>&copy; Lab Equipment Borrowing System</p>
  </footer>

  <script src="../assets/js/script.js"></script>
</body>
</html>
