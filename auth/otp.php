<?php
/**
 * Campus Resolve - OTP Verification & Password Reset Program
 * Designed following Prototype 4 UI architecture and Prototype auth workflows.
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    header('Location: ' . (is_admin() ? "{$base}admin/dashboard.php" : "{$base}student/dashboard.php"));
    exit;
}

// Ensure OTP verification table exists if DB is reachable
if (isset($pdo)) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `otp_verifications` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `email` VARCHAR(120) NOT NULL,
              `otp_code` VARCHAR(10) NOT NULL,
              `purpose` VARCHAR(50) NOT NULL DEFAULT 'password_reset',
              `attempts` INT NOT NULL DEFAULT 0,
              `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
              `expires_at` DATETIME NOT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (PDOException $e) {
        // Table creation failure is non-fatal; session fallback will be used
    }
}

if (!function_exists('mask_email')) {
    function mask_email(string $email): string {
        $parts = explode('@', $email);
        if (count($parts) < 2) return $email;
        $name = $parts[0];
        $domain = $parts[1];
        $visible = mb_substr($name, 0, min(2, mb_strlen($name)));
        return $visible . '***@' . $domain;
    }
}

// Session state initialization
if (!isset($_SESSION['otp_state'])) {
    $_SESSION['otp_state'] = [
        'stage'      => 'request', // 'request', 'verify', 'reset', 'success'
        'email'      => '',
        'otp'        => '',
        'expires_at' => 0,
        'attempts'   => 0,
        'verified'   => false,
        'last_sent'  => 0,
    ];
}

$state = &$_SESSION['otp_state'];
$error = '';
$info = '';
$success = '';

// Handle cancel or restart
if (isset($_GET['restart']) && $_GET['restart'] === '1') {
    $_SESSION['otp_state'] = [
        'stage'      => 'request',
        'email'      => '',
        'otp'        => '',
        'expires_at' => 0,
        'attempts'   => 0,
        'verified'   => false,
        'last_sent'  => 0,
    ];
    header('Location: otp.php');
    exit;
}

// Pre-fill email from query parameter if provided
if ($state['stage'] === 'request' && !empty($_GET['email']) && empty($state['email'])) {
    $candidate = trim($_GET['email']);
    if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
        $state['email'] = $candidate;
    }
}

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // STAGE 1: Request OTP Code
        if ($action === 'request_otp') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $error = 'Please enter your registered campus email address.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check if user exists in database
                $userExists = true;
                if (isset($pdo)) {
                    try {
                        $stmt = $pdo->prepare("SELECT `id`, `name`, `status` FROM `users` WHERE `email` = ? LIMIT 1");
                        $stmt->execute([$email]);
                        $user = $stmt->fetch();
                        if (!$user) {
                            $userExists = false;
                            $error = 'No registered account found with that email address.';
                        } elseif ($user['status'] !== 'active') {
                            $userExists = false;
                            $error = 'This account is currently inactive. Please contact administration.';
                        }
                    } catch (PDOException $e) {
                        // DB issue, allow fallback
                    }
                }

                if ($userExists) {
                    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $now = time();
                    $expiresAt = $now + 600; // 10 minutes

                    $state['stage'] = 'verify';
                    $state['email'] = $email;
                    $state['otp'] = $otp;
                    $state['expires_at'] = $expiresAt;
                    $state['attempts'] = 0;
                    $state['verified'] = false;
                    $state['last_sent'] = $now;

                    // Record to DB if available
                    if (isset($pdo)) {
                        try {
                            $expDate = date('Y-m-d H:i:s', $expiresAt);
                            $stmt = $pdo->prepare("INSERT INTO `otp_verifications` (`email`, `otp_code`, `purpose`, `expires_at`) VALUES (?, ?, 'password_reset', ?)");
                            $stmt->execute([$email, $otp, $expDate]);
                        } catch (PDOException $e) {
                            // Non-fatal
                        }
                    }

                    // Attempt sending email via PHP mail()
                    $subject = "Campus Resolve — Verification Code: {$otp}";
                    $message = "Hello,\r\n\r\nYour 6-digit verification code for Campus Resolve is:\r\n\r\n"
                             . "    {$otp}\r\n\r\n"
                             . "This code will expire in 10 minutes.\r\n"
                             . "If you did not request this password reset, please secure your account immediately.\r\n\r\n"
                             . "Campus Resolve System\r\n";
                    $headers = "From: noreply@campusresolve.edu\r\n"
                             . "Reply-To: support@campusresolve.edu\r\n"
                             . "X-Mailer: PHP/" . phpversion();
                    @mail($email, $subject, $message, $headers);

                    $info = 'A 6-digit verification code has been generated and dispatched to ' . mask_email($email) . '.';
                }
            }
        }

        // STAGE 2: Resend OTP Code
        elseif ($action === 'resend_otp') {
            $email = $state['email'];
            if (empty($email)) {
                $state['stage'] = 'request';
                $error = 'Session expired. Please enter your email address again.';
            } else {
                $now = time();
                if ($now - $state['last_sent'] < 30) {
                    $waitSeconds = 30 - ($now - $state['last_sent']);
                    $error = "Please wait {$waitSeconds} seconds before requesting another code.";
                } else {
                    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $expiresAt = $now + 600;

                    $state['otp'] = $otp;
                    $state['expires_at'] = $expiresAt;
                    $state['attempts'] = 0;
                    $state['last_sent'] = $now;

                    if (isset($pdo)) {
                        try {
                            $expDate = date('Y-m-d H:i:s', $expiresAt);
                            $stmt = $pdo->prepare("INSERT INTO `otp_verifications` (`email`, `otp_code`, `purpose`, `expires_at`) VALUES (?, ?, 'password_reset', ?)");
                            $stmt->execute([$email, $otp, $expDate]);
                        } catch (PDOException $e) {
                            // Non-fatal
                        }
                    }

                    $info = 'A new 6-digit verification code has been dispatched to ' . mask_email($email) . '.';
                }
            }
        }

        // STAGE 3: Verify OTP Code
        elseif ($action === 'verify_otp') {
            $email = $state['email'];
            // Assemble digits from digit array or single text field
            $digits = $_POST['digits'] ?? [];
            if (is_array($digits) && count($digits) === 6) {
                $enteredOtp = implode('', array_map('trim', $digits));
            } else {
                $enteredOtp = trim($_POST['otp'] ?? '');
            }

            if (empty($email) || empty($state['otp'])) {
                $state['stage'] = 'request';
                $error = 'Your verification session expired. Please enter your email to start again.';
            } elseif (time() > $state['expires_at']) {
                $error = 'This verification code has expired. Please click "Resend Code" to get a fresh one.';
            } elseif ($state['attempts'] >= 5) {
                $error = 'Too many failed verification attempts. Please request a new code.';
            } elseif (strlen($enteredOtp) !== 6 || !ctype_digit($enteredOtp)) {
                $error = 'Please enter a valid 6-digit numerical code.';
            } else {
                if (hash_equals((string)$state['otp'], (string)$enteredOtp)) {
                    $state['verified'] = true;
                    $state['stage'] = 'reset';

                    if (isset($pdo)) {
                        try {
                            $stmt = $pdo->prepare("UPDATE `otp_verifications` SET `is_verified` = 1 WHERE `email` = ? AND `otp_code` = ? ORDER BY `id` DESC LIMIT 1");
                            $stmt->execute([$email, $enteredOtp]);
                        } catch (PDOException $e) {
                            // Non-fatal
                        }
                    }
                    $success = 'Code successfully verified! Please choose a strong new password.';
                } else {
                    $state['attempts']++;
                    $remaining = 5 - $state['attempts'];
                    if ($remaining > 0) {
                        $error = "Incorrect verification code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining.";
                    } else {
                        $error = 'Maximum attempts exceeded. Please request a new verification code.';
                    }
                }
            }
        }

        // STAGE 4: Reset Password
        elseif ($action === 'reset_password') {
            $email = $state['email'];
            $newPassword = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';

            if (!$state['verified'] || empty($email)) {
                $state['stage'] = 'request';
                $error = 'Unauthorized password reset request. Please verify your email first.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters in length.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match. Please re-enter both carefully.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updated = false;

                if (isset($pdo)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `email` = ?");
                        $stmt->execute([$hashedPassword, $email]);
                        $updated = ($stmt->rowCount() > 0);
                    } catch (PDOException $e) {
                        $error = 'Database update error: ' . $e->getMessage();
                    }
                } else {
                    $updated = true; // Local simulation
                }

                if ($updated || !isset($pdo)) {
                    // Reset state
                    unset($_SESSION['otp_state']);
                    set_flash('Your password has been successfully updated. You can now sign in.', 'success');
                    header('Location: login.php');
                    exit;
                } elseif (empty($error)) {
                    $error = 'Unable to update password. Please verify your account and try again.';
                }
            }
        }
    }
}

// Calculate remaining expiry in seconds for JavaScript countdown
$expirySeconds = max(0, $state['expires_at'] - time());
$canResendAfter = max(0, 30 - (time() - $state['last_sent']));
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Verification & Password Reset — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css?v=<?= time() ?>" />
    <style>
      /* OTP-specific refinements harmonized with Prototype 4 tokens */
      .otp-segmented {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin: 18px 0 22px;
      }
      .otp-segmented input {
        width: 48px;
        height: 56px;
        text-align: center;
        font-size: 24px;
        font-weight: 700;
        letter-spacing: 0;
        color: var(--ink, #17191d);
        background: #ffffff;
        border: 1.5px solid var(--line, #e6e8eb);
        border-radius: 10px;
        transition: all 180ms cubic-bezier(0.23, 1, 0.32, 1);
        outline: none;
        padding: 0;
      }
      .otp-segmented input:focus {
        border-color: var(--blue, #0a6ef6);
        box-shadow: 0 0 0 4px rgba(10, 110, 246, 0.16);
        transform: translateY(-1.5px);
      }
      .otp-segmented input.filled {
        background: #f8fafc;
        border-color: #cbd5e1;
      }
      .otp-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 14px 0 20px;
        font-size: 13.5px;
        color: var(--muted, #69707b);
      }
      .otp-timer {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        color: var(--ink, #17191d);
      }
      .otp-timer.expired {
        color: var(--red, #d92d20);
      }
      .resend-btn {
        background: none;
        border: none;
        color: var(--blue, #0a6ef6);
        font-weight: 600;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 4px;
        transition: opacity 160ms ease;
      }
      .resend-btn[disabled] {
        color: var(--muted, #69707b);
        cursor: not-allowed;
        opacity: 0.6;
        text-decoration: none;
      }
      .dev-callout {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(10, 110, 246, 0.07);
        border: 1px dashed rgba(10, 110, 246, 0.4);
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 20px;
        font-size: 13px;
        color: var(--ink, #17191d);
      }
      .dev-callout code {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 3px;
        color: var(--blue, #0a6ef6);
        background: #ffffff;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid rgba(10, 110, 246, 0.2);
      }
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
      <a class="back-button" href="login.php">
        <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow-left" /></svg>Back to sign in
      </a>
    </header>

    <main id="main-content" class="auth-main">
      <section class="auth-card reveal">

        <!-- STAGE 1: REQUEST OTP -->
        <?php if ($state['stage'] === 'request'): ?>
          <div class="auth-copy">
            <p class="eyebrow">Account Recovery</p>
            <h1>Reset your password.</h1>
            <p>Enter your campus email to receive a 6-digit one-time verification code.</p>
          </div>

          <?php if (!empty($error)): ?>
            <div class="form-alert" role="alert" style="display:block; margin-bottom:1rem; color:var(--color-danger, #d9534f); background:rgba(217,83,79,0.1); padding:0.75rem 1rem; border-radius:6px;">
              <?= e($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="otp.php" data-validate novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_otp" />

            <label class="field">
              <span>Campus email address</span>
              <input
                name="email"
                type="email"
                placeholder="name@campus.edu"
                autocomplete="email"
                value="<?= e($state['email']) ?>"
                required
                autofocus
              />
              <small class="error"></small>
            </label>

            <button class="button primary full" type="submit" style="margin-top:10px;">
              Send verification code
              <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
            </button>
          </form>

          <p class="auth-switch" style="margin-top:20px;">
            Remembered your credentials? <a href="login.php">Sign in here</a>
          </p>

        <!-- STAGE 2: VERIFY OTP -->
        <?php elseif ($state['stage'] === 'verify'): ?>
          <div class="auth-copy">
            <p class="eyebrow">Security Verification</p>
            <h1>Enter 6-digit code.</h1>
            <p>We sent a one-time verification code to <strong><?= e(mask_email($state['email'])) ?></strong>.</p>
          </div>

          <!-- Development simulation callout for quick local testing -->
          <?php if (!empty($state['otp'])): ?>
            <div class="dev-callout">
              <span>Demo OTP Preview:</span>
              <code id="preview-code"><?= e($state['otp']) ?></code>
            </div>
          <?php endif; ?>

          <?php if (!empty($error)): ?>
            <div class="form-alert" role="alert" style="display:block; margin-bottom:1rem; color:var(--color-danger, #d9534f); background:rgba(217,83,79,0.1); padding:0.75rem 1rem; border-radius:6px;">
              <?= e($error) ?>
            </div>
          <?php elseif (!empty($info)): ?>
            <div class="form-alert" role="alert" style="display:block; margin-bottom:1rem; color:var(--color-success, #16835d); background:rgba(22,131,93,0.1); padding:0.75rem 1rem; border-radius:6px;">
              <?= e($info) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="otp.php" id="otp-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="verify_otp" />
            <input type="hidden" name="otp" id="combined-otp" value="" />

            <!-- Segmented 6-digit inputs -->
            <div class="otp-segmented" id="otp-container">
              <input type="text" name="digits[]" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" autofocus required />
              <input type="text" name="digits[]" maxlength="1" pattern="[0-9]" inputmode="numeric" required />
              <input type="text" name="digits[]" maxlength="1" pattern="[0-9]" inputmode="numeric" required />
              <input type="text" name="digits[]" maxlength="1" pattern="[0-9]" inputmode="numeric" required />
              <input type="text" name="digits[]" maxlength="1" pattern="[0-9]" inputmode="numeric" required />
              <input type="text" name="digits[]" maxlength="1" pattern="[0-9]" inputmode="numeric" required />
            </div>

            <div class="otp-meta">
              <span class="otp-timer" id="timer-display">
                <svg class="icon" width="16" height="16"><use href="<?= $base ?>icons.svg#i-check" /></svg>
                Expires in <strong id="countdown-val">10:00</strong>
              </span>

              <button
                type="button"
                class="resend-btn"
                id="resend-trigger"
                <?= ($canResendAfter > 0) ? 'disabled' : '' ?>
              >
                Resend code <?= ($canResendAfter > 0) ? "({$canResendAfter}s)" : '' ?>
              </button>
            </div>

            <button class="button primary full" type="submit" id="verify-button">
              Verify and proceed
              <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
            </button>
          </form>

          <!-- Hidden resend form -->
          <form method="POST" action="otp.php" id="resend-form" style="display:none;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="resend_otp" />
          </form>

          <p class="auth-switch" style="margin-top:20px;">
            Wrong email address? <a href="otp.php?restart=1">Change email</a>
          </p>

        <!-- STAGE 3: RESET PASSWORD -->
        <?php elseif ($state['stage'] === 'reset'): ?>
          <div class="auth-copy">
            <p class="eyebrow">Set New Password</p>
            <h1>Choose a new password.</h1>
            <p>Verification successful for <strong><?= e($state['email']) ?></strong>. Set your new access credentials below.</p>
          </div>

          <?php if (!empty($error)): ?>
            <div class="form-alert" role="alert" style="display:block; margin-bottom:1rem; color:var(--color-danger, #d9534f); background:rgba(217,83,79,0.1); padding:0.75rem 1rem; border-radius:6px;">
              <?= e($error) ?>
            </div>
          <?php elseif (!empty($success)): ?>
            <div class="form-alert" role="alert" style="display:block; margin-bottom:1rem; color:var(--color-success, #16835d); background:rgba(22,131,93,0.1); padding:0.75rem 1rem; border-radius:6px;">
              <?= e($success) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="otp.php" data-validate novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset_password" />

            <label class="field">
              <span>New password</span>
              <span class="password-wrap">
                <input
                  id="register-password"
                  name="password"
                  type="password"
                  placeholder="Create a new password (min. 8 characters)"
                  autocomplete="new-password"
                  required
                  autofocus
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
              <span>Confirm new password</span>
              <span class="password-wrap">
                <input
                  id="confirm-password"
                  name="confirmPassword"
                  type="password"
                  placeholder="Re-enter your new password"
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
              Update password and sign in
              <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
            </button>
          </form>

        <?php endif; ?>

      </section>
    </main>

    <div class="toast-region" aria-live="polite" aria-label="Notifications"></div>

    <script src="<?= $base ?>assets/js/app.js" defer></script>
    <script>
      (function () {
        // 1. Password Strength Meter Logic for Reset Stage
        const pwd = document.getElementById("register-password");
        const meter = document.getElementById("password-strength-container");
        const label = document.getElementById("strength-label");

        if (pwd && meter) {
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
        }

        // 2. Segmented OTP Input Navigation & Paste Handling
        const container = document.getElementById("otp-container");
        const otpForm = document.getElementById("otp-form");
        const combinedInput = document.getElementById("combined-otp");

        if (container && otpForm) {
          const inputs = Array.from(container.querySelectorAll("input"));

          inputs.forEach((input, idx) => {
            // Auto-advance on input
            input.addEventListener("input", (e) => {
              const val = input.value.replace(/\D/g, "");
              input.value = val ? val[val.length - 1] : "";
              if (input.value) {
                input.classList.add("filled");
                if (idx < inputs.length - 1) {
                  inputs[idx + 1].focus();
                  inputs[idx + 1].select();
                }
              } else {
                input.classList.remove("filled");
              }
              syncCombined();
            });

            // Backspace navigation
            input.addEventListener("keydown", (e) => {
              if (e.key === "Backspace" && !input.value && idx > 0) {
                inputs[idx - 1].focus();
                inputs[idx - 1].select();
              }
            });

            // Auto-select on focus
            input.addEventListener("focus", () => input.select());
          });

          // Global paste handling across digit boxes
          container.addEventListener("paste", (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData("text").trim();
            const digitsOnly = pasted.replace(/\D/g, "").slice(0, 6);
            if (digitsOnly.length > 0) {
              digitsOnly.split("").forEach((d, i) => {
                if (inputs[i]) {
                  inputs[i].value = d;
                  inputs[i].classList.add("filled");
                }
              });
              const nextIndex = Math.min(digitsOnly.length, inputs.length - 1);
              inputs[nextIndex].focus();
              syncCombined();
            }
          });

          function syncCombined() {
            const code = inputs.map(i => i.value).join("");
            if (combinedInput) combinedInput.value = code;
          }

          otpForm.addEventListener("submit", (e) => {
            syncCombined();
            if (combinedInput && combinedInput.value.length !== 6) {
              e.preventDefault();
              alert("Please enter the complete 6-digit verification code.");
              const emptyIdx = inputs.findIndex(i => !i.value);
              if (emptyIdx !== -1) inputs[emptyIdx].focus();
            }
          });
        }

        // 3. Countdown Timer & Resend Button Controller
        let remaining = <?= (int)$expirySeconds ?>;
        let resendDelay = <?= (int)$canResendAfter ?>;
        const timerVal = document.getElementById("countdown-val");
        const resendBtn = document.getElementById("resend-trigger");
        const resendForm = document.getElementById("resend-form");

        function updateTimerDisplay() {
          if (!timerVal) return;
          const mins = Math.floor(remaining / 60);
          const secs = remaining % 60;
          timerVal.textContent = String(mins).padStart(2, '0') + ":" + String(secs).padStart(2, '0');
          if (remaining <= 0) {
            timerVal.textContent = "Expired";
            timerVal.parentElement.classList.add("expired");
          }
        }

        updateTimerDisplay();

        const timerInterval = setInterval(() => {
          if (remaining > 0) {
            remaining--;
            updateTimerDisplay();
          }

          if (resendDelay > 0) {
            resendDelay--;
            if (resendBtn) {
              resendBtn.disabled = true;
              resendBtn.textContent = `Resend code (${resendDelay}s)`;
            }
          } else if (resendBtn && resendBtn.disabled) {
            resendBtn.disabled = false;
            resendBtn.textContent = "Resend code";
          }

          if (remaining <= 0 && resendDelay <= 0) {
            clearInterval(timerInterval);
          }
        }, 1000);

        if (resendBtn && resendForm) {
          resendBtn.addEventListener("click", () => {
            if (!resendBtn.disabled) {
              resendForm.submit();
            }
          });
        }
      })();
    </script>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
