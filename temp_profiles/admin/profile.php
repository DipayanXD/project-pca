<?php
/**
 * Campus Resolve - Administrator Profile Management
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';

$currentUser = current_user();
$userId = (int)($currentUser['id'] ?? 0);

// Fetch fresh admin data from database
$stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? AND `role` = 'admin' LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('Unable to load administrator profile.', 'error');
    header('Location: ' . $base . 'auth/logout.php');
    exit;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!validate_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            $error = 'Full name is required.';
        } elseif (mb_strlen($name) < 2) {
            $error = 'Full name must be at least 2 characters long.';
        } else {
            try {
                $updateStmt = $pdo->prepare("UPDATE `users` SET `name` = ? WHERE `id` = ? AND `role` = 'admin'");
                $updateStmt->execute([$name, $userId]);

                $_SESSION['user']['name'] = $name;

                set_flash('Administrator profile updated successfully.', 'success');
                header('Location: ' . $base . 'admin/profile.php');
                exit;
            } catch (PDOException $e) {
                error_log("Admin profile update exception: " . $e->getMessage());
                $error = 'An error occurred while saving your changes. Please try again.';
            }
        }
    }
}

$active_nav = 'admin-profile';
$crumb_title = 'Profile';
$initials = user_initials($user['name']);
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
            <p>Keep your Campus Resolve administrative account information current.</p>
          </section>

          <section class="profile-layout">
            <div class="panel profile-card">
              <span class="avatar profile-avatar"><?= e($initials) ?></span>
              <h2><?= e($user['name']) ?></h2>
              <p><?= e($user['email']) ?></p>
              <span class="role-chip">Administrator</span>
              <hr />
              <dl>
                <div>
                  <dt>Staff ID</dt>
                  <dd><code><?= e($user['user_code']) ?></code></dd>
                </div>
                <div>
                  <dt>Department</dt>
                  <dd><?= e($user['department'] ?: 'Administration') ?></dd>
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
                  <p>Used to identify administrative actions and notes across the grievance registry.</p>
                </div>
              </div>

              <?php if (!empty($error)): ?>
                <div class="form-alert" role="alert" style="display:block; margin-bottom:1.25rem; color:var(--color-danger, #d92d20); background:rgba(217,45,32,0.08); padding:0.75rem 1rem; border-radius:6px; font-size:13px;">
                  <?= e($error) ?>
                </div>
              <?php endif; ?>

              <label class="field">
                <span>Staff ID</span>
                <input
                  type="text"
                  value="<?= e($user['user_code']) ?>"
                  readonly
                  disabled
                  style="background: var(--soft, #f5f6f7); cursor: not-allowed; opacity: 0.85;"
                />
                <small class="hint" style="color: var(--muted, #69707b); font-size: 12px; margin-top: 4px;">Institutional administrative staff identification code.</small>
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
                <small class="hint" style="color: var(--muted, #69707b); font-size: 12px; margin-top: 4px;">Administrative contact email.</small>
              </label>

              <label class="field">
                <span>Department</span>
                <input
                  type="text"
                  value="<?= e($user['department'] ?: 'Administration') ?>"
                  readonly
                  disabled
                  style="background: var(--soft, #f5f6f7); cursor: not-allowed; opacity: 0.85;"
                />
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
