<?php
/**
 * Campus Resolve - Student Profile Management
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_student.php';

$currentUser = current_user();
$userId = (int)($currentUser['id'] ?? 0);

// Fetch fresh user data from database
$stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? AND `role` = 'student' LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('Unable to load user profile.', 'error');
    header('Location: ' . $base . 'auth/logout.php');
    exit;
}

$error = '';
$availableDepartments = get_departments();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!validate_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (empty($name) || empty($department)) {
            $error = 'Full name and department are required.';
        } elseif (mb_strlen($name) < 2) {
            $error = 'Full name must be at least 2 characters long.';
        } elseif (!in_array($department, $availableDepartments, true)) {
            $error = 'Please select a valid department from the dropdown.';
        } else {
            try {
                $updateStmt = $pdo->prepare("UPDATE `users` SET `name` = ?, `department` = ? WHERE `id` = ? AND `role` = 'student'");
                $updateStmt->execute([$name, $department, $userId]);

                // Update active session details
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['department'] = $department;

                set_flash('Your profile and department have been updated successfully.', 'success');
                header('Location: ' . $base . 'student/profile.php');
                exit;
            } catch (PDOException $e) {
                error_log("Profile update exception: " . $e->getMessage());
                $error = 'An error occurred while saving your changes. Please try again.';
            }
        }
    }
}

$active_nav = 'profile';
$crumb_title = 'Profile';
$initials = user_initials($user['name']);
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
            <p>Keep your Campus Resolve account and department information current.</p>
          </section>

          <section class="profile-layout">
            <div class="panel profile-card">
              <span class="avatar profile-avatar"><?= e($initials) ?></span>
              <h2><?= e($user['name']) ?></h2>
              <p><?= e($user['email']) ?></p>
              <span class="role-chip">Student</span>
              <hr />
              <dl>
                <div>
                  <dt>Student ID</dt>
                  <dd><code><?= e($user['user_code']) ?></code></dd>
                </div>
                <div>
                  <dt>Department</dt>
                  <dd><?= e($user['department'] ?: 'Not assigned') ?></dd>
                </div>
                <div>
                  <dt>Member since</dt>
                  <dd><?= format_date($user['created_at'], true) ?></dd>
                </div>
                <div>
                  <dt>Account status</dt>
                  <dd><span class="account-active"><?= ucfirst(e($user['status'])) ?></span></dd>
                </div>
              </dl>
            </div>

            <form class="panel profile-form" method="POST" action="profile.php" data-validate novalidate>
              <?= csrf_field() ?>
              <div class="panel-head">
                <div>
                  <h2>Personal information</h2>
                  <p>Used to identify your account across campus grievances and operations.</p>
                </div>
              </div>

              <?php if (!empty($error)): ?>
                <div class="form-alert" role="alert" style="display:block; margin-bottom:1.25rem; color:var(--color-danger, #d92d20); background:rgba(217,45,32,0.08); padding:0.75rem 1rem; border-radius:6px; font-size:13px;">
                  <?= e($error) ?>
                </div>
              <?php endif; ?>

              <label class="field">
                <span>Student ID</span>
                <input
                  type="text"
                  value="<?= e($user['user_code']) ?>"
                  readonly
                  disabled
                  style="background: var(--soft, #f5f6f7); cursor: not-allowed; opacity: 0.85;"
                />
                <small class="hint" style="color: var(--muted, #69707b); font-size: 12px; margin-top: 4px;">Institutional student identification number.</small>
              </label>

              <label class="field">
                <span>Full name</span>
                <input
                  name="name"
                  type="text"
                  value="<?= e($user['name']) ?>"
                  required
                  placeholder="Your full name"
                />
                <small class="error"></small>
              </label>

              <label class="field">
                <span>Email address</span>
                <input
                  type="email"
                  value="<?= e($user['email']) ?>"
                  readonly
                  disabled
                  style="background: var(--soft, #f5f6f7); cursor: not-allowed; opacity: 0.85;"
                />
                <small class="hint" style="color: var(--muted, #69707b); font-size: 12px; margin-top: 4px;">Institutional email tied to your student record.</small>
              </label>

              <label class="field">
                <span>Department</span>
                <select name="department" required>
                  <option value="" disabled <?= empty($user['department']) ? 'selected' : '' ?>>Select your department</option>
                  <?php foreach ($availableDepartments as $dept): ?>
                    <option value="<?= e($dept) ?>" <?= ($user['department'] === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="error"></small>
              </label>

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
