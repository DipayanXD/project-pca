<?php
/**
 * Campus Resolve - Student Profile View & Edit Page
 * 
 * Displays student profile information, academic details, and activity summary.
 * Allows updating profile details and credentials with CSRF protection and server-side validation.
 * Matches prototypes/prototype-4/profile.html.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_student.php';

$user = current_user();
$userId = (int)($user['id'] ?? 0);

// Fetch fresh user record from database
$stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch() ?: $user;

// Fetch student complaint stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM `complaints` WHERE `user_id` = ?");
$stmt->execute([$userId]);
$totalComplaints = (int)$stmt->fetchColumn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $errors[] = 'Your session token expired. Please refresh and try again.';
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // Validate full name
    if ($name === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'Full name must be between 3 and 100 characters.';
    }

    // Validate email address
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 120) {
        $errors[] = 'Email cannot exceed 120 characters.';
    } else {
        // Ensure email is unique across users (excluding self)
        $stmtCheck = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = ? AND `id` != ?");
        $stmtCheck->execute([$email, $userId]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'This email address is already in use by another account.';
        }
    }

    // Optional password change validation
    $updatePassword = false;
    if ($newPassword !== '' || $currentPassword !== '' || $confirmPassword !== '') {
        if ($currentPassword === '') {
            $errors[] = 'Please enter your current password to set a new password.';
        } elseif (!password_verify($currentPassword, (string)($userData['password'] ?? ''))) {
            $errors[] = 'Current password does not match.';
        } elseif (mb_strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password confirmation does not match.';
        } else {
            $updatePassword = true;
        }
    }

    if (empty($errors)) {
        try {
            if ($updatePassword) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE `users` 
                    SET `name` = ?, `email` = ?, `department` = ?, `password` = ? 
                    WHERE `id` = ?
                ");
                $stmt->execute([$name, $email, $department, $hash, $userId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE `users` 
                    SET `name` = ?, `email` = ?, `department` = ? 
                    WHERE `id` = ?
                ");
                $stmt->execute([$name, $email, $department, $userId]);
            }

            // Update session user cache
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['department'] = $department;
            $userData['name'] = $name;
            $userData['email'] = $email;
            $userData['department'] = $department;

            set_flash('Profile updated successfully.', 'success');
            header('Location: profile.php');
            exit;
        } catch (Throwable $e) {
            error_log('Student profile update failed: ' . $e->getMessage());
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

$active_nav = 'profile';
$crumb_title = 'Profile';
$flash = get_flash();
$initials = user_initials($userData['name']);
$departments = get_departments();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Profile — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/student-sidebar.php'; ?>

      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main id="main-content" class="app-main">
          <section class="page-head">
            <p class="eyebrow">Account</p>
            <h1>Your profile</h1>
            <p>Keep your Campus Resolve account and contact information current.</p>
          </section>

          <?php if ($flash): ?>
            <div class="form-alert <?= ($flash['type'] ?? '') === 'success' ? 'success' : '' ?>" role="<?= ($flash['type'] ?? '') === 'success' ? 'status' : 'alert' ?>">
              <?= e($flash['message']) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="form-alert" role="alert">
              <strong>Please correct the following:</strong>
              <ul>
                <?php foreach ($errors as $err): ?>
                  <li><?= e($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <section class="profile-layout">
            <!-- Left Info Card Matching Prototype-4 -->
            <div class="panel profile-card">
              <span class="avatar profile-avatar"><?= e($initials) ?></span>
              <h2><?= e($userData['name']) ?></h2>
              <p><?= e($userData['email']) ?></p>
              <div style="margin: 12px 0;">
                <span class="role-chip">Student</span>
                <span class="id-link" style="margin-left: 8px;"><?= e($userData['user_code']) ?></span>
              </div>
              <hr />
              <dl>
                <div>
                  <dt>Department</dt>
                  <dd><?= e($userData['department'] ?: 'Not assigned') ?></dd>
                </div>
                <div>
                  <dt>Account status</dt>
                  <dd><span class="account-active"><?= ucfirst(e($userData['status'] ?? 'active')) ?></span></dd>
                </div>
                <div>
                  <dt>Member since</dt>
                  <dd><?= e(format_date($userData['created_at'], true)) ?></dd>
                </div>
                <div>
                  <dt>Total complaints</dt>
                  <dd><strong><?= $totalComplaints ?></strong></dd>
                </div>
              </dl>
            </div>

            <!-- Right Edit Form Matching Prototype-4 -->
            <form class="panel profile-form" method="post" data-validate novalidate>
              <?= csrf_field() ?>
              <div class="panel-head">
                <div>
                  <h2>Personal information</h2>
                  <p>Used to identify your account and department across campus.</p>
                </div>
              </div>

              <div style="padding: 24px;">
                <label class="field">
                  <span>Full name</span>
                  <input
                    name="name"
                    value="<?= e($userData['name']) ?>"
                    required
                    minlength="3"
                    maxlength="100"
                  />
                  <small class="error"></small>
                </label>

                <label class="field">
                  <span>Email address</span>
                  <input
                    name="email"
                    type="email"
                    value="<?= e($userData['email']) ?>"
                    required
                    maxlength="120"
                  />
                  <small class="error"></small>
                </label>

                <label class="field">
                  <span>Academic department</span>
                  <select name="department">
                    <option value="">Select your department</option>
                    <?php foreach ($departments as $dept): ?>
                      <option value="<?= e($dept) ?>" <?= ($userData['department'] ?? '') === $dept ? 'selected' : '' ?>>
                        <?= e($dept) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </label>

                <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--line);">
                  <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 6px; color: var(--ink);">Change Password</h3>
                  <p style="font-size: 12px; color: var(--muted); margin-bottom: 16px;">Leave blank if you do not wish to update your password.</p>

                  <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                    <label class="field">
                      <span>Current password</span>
                      <input
                        name="current_password"
                        type="password"
                        placeholder="Enter current password to verify"
                        autocomplete="current-password"
                      />
                    </label>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                      <label class="field">
                        <span>New password</span>
                        <input
                          name="new_password"
                          type="password"
                          placeholder="Min 6 characters"
                          autocomplete="new-password"
                        />
                      </label>

                      <label class="field">
                        <span>Confirm new password</span>
                        <input
                          name="confirm_password"
                          type="password"
                          placeholder="Confirm new password"
                          autocomplete="new-password"
                        />
                      </label>
                    </div>
                  </div>
                </div>

                <div class="form-actions" style="margin-top: 24px; padding-top: 18px;">
                  <button class="button primary" type="submit">
                    Save changes
                  </button>
                </div>
              </div>
            </form>
          </section>
        </main>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
