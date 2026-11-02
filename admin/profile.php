<?php
/**
 * Campus Resolve - Admin Profile View & Edit Page
 * 
 * Displays administrator profile details and allows updating personal information
 * and credentials with CSRF protection, input validation, and flash notifications.
 * Matches prototypes/prototype-4/admin-profile.html.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = current_user();
$adminId = (int)($user['id'] ?? 0);

// Fetch fresh admin record from database
$stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
$stmt->execute([$adminId]);
$adminData = $stmt->fetch() ?: $user;

// Fetch system stats for profile summary card
$stmtComplaints = $pdo->query("SELECT COUNT(*) FROM `complaints`");
$totalComplaints = (int)$stmtComplaints->fetchColumn();

$stmtStudents = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'student'");
$totalStudents = (int)$stmtStudents->fetchColumn();

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

    // Validate name
    if ($name === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'Full name must be between 3 and 100 characters.';
    }

    // Validate email
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 120) {
        $errors[] = 'Email cannot exceed 120 characters.';
    } else {
        // Check uniqueness excluding current user
        $stmtCheck = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = ? AND `id` != ?");
        $stmtCheck->execute([$email, $adminId]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'This email address is already in use by another account.';
        }
    }

    // Optional password change validation
    $updatePassword = false;
    if ($newPassword !== '' || $currentPassword !== '' || $confirmPassword !== '') {
        if ($currentPassword === '') {
            $errors[] = 'Please enter your current password to set a new password.';
        } elseif (!password_verify($currentPassword, (string)($adminData['password'] ?? ''))) {
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
                $stmtUpdate = $pdo->prepare("
                    UPDATE `users` 
                    SET `name` = ?, `email` = ?, `department` = ?, `password` = ? 
                    WHERE `id` = ?
                ");
                $stmtUpdate->execute([$name, $email, $department ?: 'Administration', $hash, $adminId]);
            } else {
                $stmtUpdate = $pdo->prepare("
                    UPDATE `users` 
                    SET `name` = ?, `email` = ?, `department` = ? 
                    WHERE `id` = ?
                ");
                $stmtUpdate->execute([$name, $email, $department ?: 'Administration', $adminId]);
            }

            // Refresh session data
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['department'] = $department ?: 'Administration';

            set_flash('Profile updated successfully.', 'success');
            header('Location: profile.php');
            exit;
        } catch (Throwable $e) {
            error_log('Admin profile update error: ' . $e->getMessage());
            $errors[] = 'Database error occurred while updating profile. Please try again.';
        }
    }
}

$active_nav = 'admin-profile';
$crumb_title = 'Profile';
$flash = get_flash();
$initials = user_initials($adminData['name']);
$formName = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($name ?? $adminData['name']) : $adminData['name'];
$formEmail = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($email ?? $adminData['email']) : $adminData['email'];
$formDept = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($department ?? ($adminData['department'] ?: 'Administration')) : ($adminData['department'] ?: 'Administration');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Profile — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>

      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main id="main-content" class="app-main">
          <section class="page-head">
            <p class="eyebrow">Account</p>
            <h1>Your profile</h1>
            <p>Keep your Campus Resolve account information current.</p>
          </section>

          <?php if ($flash): ?>
            <div class="form-alert <?= $flash['type'] === 'success' ? 'success' : '' ?>" role="status">
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
              <h2><?= e($adminData['name']) ?></h2>
              <p><?= e($adminData['email']) ?></p>
              <div style="margin: 12px 0;">
                <span class="role-chip">Administrator</span>
                <span class="id-link" style="margin-left: 8px;"><?= e($adminData['user_code']) ?></span>
              </div>
              <hr />
              <dl>
                <div>
                  <dt>Department</dt>
                  <dd><?= e($adminData['department'] ?: 'Administration') ?></dd>
                </div>
                <div>
                  <dt>Account status</dt>
                  <dd><span class="account-active"><?= ucfirst(e($adminData['status'] ?? 'active')) ?></span></dd>
                </div>
                <div>
                  <dt>Member since</dt>
                  <dd><?= e(format_date($adminData['created_at'], true)) ?></dd>
                </div>
                <div>
                  <dt>System complaints</dt>
                  <dd><strong><?= $totalComplaints ?></strong></dd>
                </div>
                <div>
                  <dt>Registered students</dt>
                  <dd><strong><?= $totalStudents ?></strong></dd>
                </div>
              </dl>
            </div>

            <!-- Right Edit Form Matching Prototype-4 -->
            <form class="panel profile-form" method="post" data-validate novalidate>
              <?= csrf_field() ?>
              <div class="panel-head">
                <div>
                  <h2>Personal information</h2>
                  <p>Used only to identify your account and administrative actions.</p>
                </div>
              </div>

              <label class="field">
                <span>Full name</span>
                <input
                  name="name"
                  value="<?= e($formName) ?>"
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
                  value="<?= e($formEmail) ?>"
                  required
                  maxlength="120"
                />
                <small class="error"></small>
              </label>

              <label class="field">
                <span>Department / Office</span>
                <input
                  name="department"
                  value="<?= e($formDept) ?>"
                  maxlength="100"
                />
                <small class="error"></small>
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

              <div class="form-actions">
                <button class="button primary" type="submit">
                  Save changes
                </button>
              </div>
            </form>
          </section>
        </main>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>

