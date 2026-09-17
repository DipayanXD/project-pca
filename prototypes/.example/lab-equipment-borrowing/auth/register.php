<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Lab Equipment Borrowing System</title>
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
      <div class="auth-icon">&#128100;</div>
      <h1>Create an Account</h1>
      <p class="auth-subtitle">Register to browse and borrow lab equipment</p>

      <!-- Static demo: validation error state (sample only) -->
      <div class="alert alert-error">
        <span>&#9888;</span>
        <div>
          <strong>Please fix the following:</strong>
          <ul>
            <li>Email is already registered</li>
            <li>Passwords do not match</li>
          </ul>
        </div>
      </div>

      <form class="styled-form" action="register.php" method="POST">

        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" placeholder="e.g. Ankita Roy" value="Ankita Roy">

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" value="ankita.roy@example.com">

        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" placeholder="e.g. 9876543210" value="9876543210">

        <label for="role">Register As</label>
        <select id="role" name="role">
          <option value="student" selected>Student</option>
          <option value="faculty">Faculty</option>
        </select>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Create a password">
        <p class="field-hint">Minimum 8 characters, with at least one number.</p>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password">

        <div class="checkbox-row">
          <label><input type="checkbox" name="agree" checked> I agree to the lab usage policy</label>
        </div>

        <button type="submit" class="btn btn-block">Create Account</button>
      </form>

      <div class="auth-divider"><span>OR</span></div>

      <p class="auth-footer-link">
        Already have an account? <a href="login.php">Login here</a>
      </p>
    </div>
  </div>

  <footer class="site-footer">
    <p>&copy; Lab Equipment Borrowing System</p>
  </footer>

  <script src="../assets/js/script.js"></script>
</body>
</html>
