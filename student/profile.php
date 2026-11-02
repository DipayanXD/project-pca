<?php
/**
 * Campus Resolve - Student Profile Page
 * 
 * Displays student profile information, academic details, and activity summary.
 * Allows updating profile details with CSRF protection and server-side validation.
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
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $errors[] = 'Your session token expired. Please refresh and try again.';
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));

    if ($name === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'Full name must be between 3 and 100 characters.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE `users` SET `name` = ?, `department` = ? WHERE `id` = ?");
            $stmt->execute([$name, $department, $userId]);

            // Update session user cache
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['department'] = $department;
            $userData['name'] = $name;
            $userData['department'] = $department;

            set_flash('Profile updated successfully.', 'success');
            header('Location: profile.php');
            exit;
        } catch (Throwable $e) {
            error_log('Profile update failed: ' . $e->getMessage());
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
            <div class="form-alert <?= $flash['type'] === 'success' ? 'success' : '' ?>" role="status">
              <?= e($flash['message']) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="form-alert" role="alert">
              <ul>
                <?php foreach ($errors as $err): ?>
                  <li><?= e($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <section class="profile-layout">
            <!-- Left Info Card -->
            <div class="panel profile-card">
              <span class="avatar profile-avatar" style="width: 56px; height: 56px; font-size: 18px; margin-bottom: 16px;">
                <?= e($initials) ?>
              </span>
              <h2><?= e($userData['name']) ?></h2>
              <p><?= e($userData['email']) ?></p>
              <div style="margin: 12px 0;">
                <span class="role-chip">Student</span>
                <span class="id-link" style="margin-left: 8px;"><?= e($userData['user_code']) ?></span>
              </div>
              <hr style="border: 0; border-top: 1px solid var(--line); margin: 20px 0;" />
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

            <!-- Right Edit Form -->
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
                  <input name="name" value="<?= e($userData['name']) ?>" required />
                  <small class="error"></small>
                </label>

                <label class="field">
                  <span>Email address (read-only)</span>
                  <input type="email" value="<?= e($userData['email']) ?>" readonly style="background: var(--soft); cursor: not-allowed;" />
                  <small style="color: var(--muted); font-size: 11px;">Email is linked to your campus credentials and cannot be changed here.</small>
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
