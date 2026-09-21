<?php
/**
 * Campus Resolve - Account Registration
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (is_logged_in()) {
    header('Location: ' . (is_admin() ? "{$base}admin/dashboard.php" : "{$base}student/dashboard.php"));
    exit;
}

$error = '';
$name = '';
$email = '';
$department = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (empty($name) || empty($email) || empty($department) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!in_array($department, get_departments(), true)) {
        $error = 'Please select a valid department from the dropdown.';
    } elseif (mb_strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address already exists. Please sign in.';
        } else {
            $role = (stripos($email, 'admin') !== false) ? 'admin' : 'student';
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $userCode = ($role === 'admin' ? 'AD-' : 'ST-') . rand(2100, 9999);

            $stmt = $pdo->prepare("INSERT INTO `users` (`user_code`, `name`, `email`, `password`, `role`, `department`, `status`) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$userCode, $name, $email, $hashedPassword, $role, $department]);
            $newId = (int)$pdo->lastInsertId();

            $_SESSION['user'] = [
                'id'         => $newId,
                'user_code'  => $userCode,
                'name'       => $name,
                'email'      => $email,
                'role'       => $role,
                'department' => $department,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ];

            set_flash('Your account has been created successfully. Welcome to Campus Resolve!', 'success');
            header('Location: ' . ($role === 'admin' ? "{$base}admin/dashboard.php" : "{$base}student/dashboard.php"));
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Create account — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css?v=<?= time() ?>" />
    <style>
      .strength {
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #525c6a;
        font-size: 12px;
        margin: -4px 0 24px;
      }
      .strength-label {
        font-weight: 650;
        margin-left: 4px;
        transition: color 180ms ease;
      }
      .strength-label.weak { color: #dc2626 !important; }
      .strength-label.fair { color: #d97706 !important; }
      .strength-label.good { color: #2563eb !important; }
      .strength-label.strong { color: #16a34a !important; }
      .strength div {
        display: flex;
        gap: 4px;
      }
      .strength i {
        display: block;
        width: 25px;
        height: 4px;
        border-radius: 3px;
        background: #e2e8f0 !important;
        transition: background-color 200ms ease;
      }
      .strength[data-score="1"] i:nth-child(1) { background: #dc2626 !important; }
      .strength[data-score="2"] i:nth-child(-n + 2) { background: #d97706 !important; }
      .strength[data-score="3"] i:nth-child(-n + 3) { background: #2563eb !important; }
      .strength[data-score="4"] i:nth-child(-n + 4) { background: #16a34a !important; }
    </style>
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
          <p class="eyebrow">Create your account</p>
          <h1>A clearer way to be heard.</h1>
          <p>Your complaint journey starts with a simple account.</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="form-alert" role="alert" style="display:block; margin-bottom:1rem; color:var(--color-danger, #d9534f); background:rgba(217,83,79,0.1); padding:0.75rem 1rem; border-radius:6px;">
            <?= e($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" data-validate novalidate>
          <?= csrf_field() ?>
          <label class="field">
            <span>Full name</span>
            <input
              name="name"
              type="text"
              placeholder="Enter your full name"
              autocomplete="name"
              value="<?= e($name) ?>"
              required
            />
            <small class="error"></small>
          </label>
          <label class="field">
            <span>Email address</span>
            <input
              name="email"
              type="email"
              placeholder="name@campus.edu"
              autocomplete="email"
              value="<?= e($email) ?>"
              required
            />
            <small class="error"></small>
          </label>
          <label class="field">
            <span>Department</span>
            <select name="department" required>
              <option value="" disabled <?= empty($department) ? 'selected' : '' ?>>Select your department</option>
              <?php foreach (get_departments() as $dept): ?>
                <option value="<?= e($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="error"></small>
          </label>
          <label class="field">
            <span>Password</span>
            <span class="password-wrap">
              <input
                id="register-password"
                name="password"
                type="password"
                placeholder="Create a password (min. 8 characters)"
                autocomplete="new-password"
                required
              />
              <button
                type="button"
                class="password-toggle"
                data-password-toggle="register-password"
                aria-label="Show password"
              >
                <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-eye" /></svg>
              </button>
            </span>
            <small class="error"></small>
          </label>
          <label class="field">
            <span>Confirm password</span>
            <span class="password-wrap">
              <input
                id="confirm-password"
                name="confirmPassword"
                type="password"
                placeholder="Re-enter your password"
                autocomplete="new-password"
                required
              />
              <button
                type="button"
                class="password-toggle"
                data-password-toggle="confirm-password"
                aria-label="Show confirm password"
              >
                <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-eye" /></svg>
              </button>
            </span>
            <small class="error"></small>
          </label>
          <div class="strength" id="password-strength-container" aria-live="polite">
            <span>Password strength <strong class="strength-label" id="strength-label"></strong></span>
            <div><i></i><i></i><i></i><i></i></div>
          </div>
          <button class="button primary full" type="submit">
            Create account
            <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
          </button>
        </form>
        <p class="auth-switch">
          Already have an account? <a href="login.php">Sign in</a>
        </p>
      </section>
    </main>
    <script>
      (function () {
        function initStrength() {
          const pwd = document.getElementById("register-password");
          const meter = document.getElementById("password-strength-container");
          const label = document.getElementById("strength-label");
          if (!pwd || !meter) return;

          function calc() {
            const val = pwd.value || "";
            if (!val) {
              meter.removeAttribute("data-score");
              if (label) {
                label.textContent = "";
                label.className = "strength-label";
              }
              return;
            }

            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val) && /[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            if (score === 0 && val.length > 0) score = 1;

            meter.setAttribute("data-score", String(score));

            if (label) {
              const levels = [
                { text: "", class: "" },
                { text: "Weak", class: "weak" },
                { text: "Fair", class: "fair" },
                { text: "Good", class: "good" },
                { text: "Strong", class: "strong" },
              ];
              const cur = levels[score] || levels[0];
              label.textContent = cur.text ? "· " + cur.text : "";
              label.className = "strength-label " + cur.class;
            }
          }

          pwd.addEventListener("input", calc);
          pwd.addEventListener("change", calc);
          pwd.addEventListener("keyup", calc);
          calc();
        }

        if (document.readyState === "loading") {
          document.addEventListener("DOMContentLoaded", initStrength);
        } else {
          initStrength();
        }
      })();
    </script>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
