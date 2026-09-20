<?php
/**
 * Campus Resolve - Administrator Sign In
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (is_logged_in() && is_admin()) {
    header("Location: {$base}admin/dashboard.php");
    exit;
}

$error = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!validate_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your administrator email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ? AND `role` = 'admin' LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your administrator account has been deactivated.';
                    } else {
                        $_SESSION['user'] = [
                            'id'         => (int)$user['id'],
                            'user_code'  => $user['user_code'],
                            'name'       => $user['name'],
                            'email'      => $user['email'],
                            'role'       => $user['role'],
                            'department' => $user['department'],
                            'status'     => $user['status'],
                            'created_at' => $user['created_at'],
                        ];
                        set_flash('Welcome to the admin workspace, ' . $user['name'] . '!', 'success');
                        header("Location: {$base}admin/dashboard.php");
                        exit;
                    }
                } else {
                    $error = 'Invalid administrator credentials or unauthorized account.';
                }
            } catch (PDOException $e) {
                $error = 'Database service unavailable. Please check back shortly.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Administrator Sign in — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
  </head>
  <body class="auth-page">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="auth-top">
      <a class="brand" href="<?= $base ?>index.php">
        <span class="brand-mark"><svg class="icon" width="15" height="15"><use href="<?= $base ?>icons.svg#i-check" /></svg></span>Campus Resolve
      </a>
      <a class="back-button" href="<?= $base ?>index.php">
        <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow-left" /></svg>Back to home
      </a>
    </header>
    <main id="main-content" class="auth-main">
      <section class="auth-card reveal">
        <div class="auth-copy">
          <p class="eyebrow" style="color:var(--indigo, #4f46e5); font-weight:650;">Operations &amp; Oversight</p>
          <h1>Administrator Portal.</h1>
          <p>Sign in with your administrative credentials to manage campus concerns.</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="form-alert" role="alert">
            <?= e($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php" data-validate novalidate>
          <?= csrf_field() ?>
          <label class="field">
            <span>Admin email</span>
            <input
              name="email"
              type="email"
              placeholder="admin@campus.edu"
              autocomplete="email"
              value="<?= e($email) ?>"
              required
            />
            <small class="error"></small>
          </label>
          <label class="field">
            <span style="display:flex; justify-content:space-between; align-items:center;">
              <span>Password</span>
              <a href="otp.php" style="font-size:12.5px; color:var(--blue, #0a6ef6); text-decoration:none; font-weight:500;">Forgot password?</a>
            </span>
            <span class="password-wrap">
              <input
                id="admin-password"
                name="password"
                type="password"
                placeholder="Enter admin password"
                autocomplete="current-password"
                required
              />
              <button
                type="button"
                class="password-toggle"
                data-password-toggle="admin-password"
                aria-label="Show password"
              >
                <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-eye" /></svg>
              </button>
            </span>
            <small class="error"></small>
          </label>
          <button class="button primary full" type="submit">
            Sign in as administrator
            <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
          </button>
        </form>
        <p class="auth-switch">
          Student account? <a href="login.php">Student Sign in</a>
        </p>
        <div class="demo-note" style="margin-top:20px; line-height:1.5; border-top:1px solid var(--line); padding-top:16px;">
          <strong>Admin credentials:</strong><br />
          • Email: <code>admin@campus.edu</code><br />
          • Password: <code>Admin@123</code>
        </div>
      </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
