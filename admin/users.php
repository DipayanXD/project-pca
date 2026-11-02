<?php
/**
 * Campus Resolve - Admin User Management
 * 
 * Displays registered student and administrator accounts in a responsive table
 * with searching, role filtering, and user directory management.
 * Faithfully matches prototypes/prototype-4/users.html.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/helpers.php';

// Navigation state
$active_nav = 'users';
$crumb_title = 'User Management';

// Handle optional search query and role filter
$search = trim((string)($_GET['q'] ?? ''));
$rawRole = trim((string)($_GET['role'] ?? ''));
$roleFilter = strtolower($rawRole);
if ($roleFilter === 'administrator') {
    $roleFilter = 'admin';
}

$sql = "
    SELECT 
        `id`,
        `user_code`,
        `name`,
        `email`,
        `role`,
        `department`,
        `status`,
        `created_at`
    FROM `users`
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (`name` LIKE ? OR `email` LIKE ? OR `user_code` LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($roleFilter === 'student' || $roleFilter === 'admin') {
    $sql .= " AND `role` = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY `role` ASC, `created_at` DESC, `id` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$flash = get_flash();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>User Management — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>

      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main id="main-content" class="app-main">
          <section class="page-head list-head">
            <div>
              <p class="eyebrow">Account directory</p>
              <h1>User management</h1>
              <p>View registered students and campus administrators.</p>
            </div>
            <a
              class="button primary"
              href="mailto:?subject=Campus%20Resolve%20Invitation"
              data-toast="User directory is synced with campus active accounts."
            >
              <svg class="icon"><use href="<?= $base ?>icons.svg#i-plus" /></svg>
              Invite user
            </a>
          </section>

          <?php if ($flash): ?>
            <div class="form-alert <?= $flash['type'] === 'success' ? 'success' : '' ?>" role="status">
              <?= e($flash['message']) ?>
            </div>
          <?php endif; ?>

          <section class="panel table-panel">
            <!-- Search & Filter Controls Matching Prototype-4 -->
            <form class="table-controls" method="GET" action="users.php">
              <label class="search-input">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-search" /></svg>
                <input
                  id="user-search"
                  name="q"
                  value="<?= e($search) ?>"
                  placeholder="Search name or email"
                  aria-label="Search users"
                />
              </label>
              <div class="filter-row">
                <select id="user-role" name="role" aria-label="Filter users" onchange="this.form.submit()">
                  <option value="">All roles</option>
                  <option value="Student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Student</option>
                  <option value="Administrator" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
                <?php if ($search !== '' || $roleFilter !== ''): ?>
                  <a class="button secondary small" href="users.php" style="text-decoration: none;">Reset</a>
                <?php endif; ?>
              </div>
            </form>

            <div class="responsive-table">
              <?php if (!empty($usersList)): ?>
                <table id="user-table">
                  <thead>
                    <tr>
                      <th>User ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Role</th>
                      <th>Registered</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($usersList as $u): ?>
                      <?php 
                        $roleTitle = $u['role'] === 'admin' ? 'Administrator' : 'Student';
                        $statusActive = ($u['status'] ?? 'active') === 'active';
                      ?>
                      <tr data-role="<?= e($roleTitle) ?>">
                        <td data-label="User ID">
                          <span class="id-link"><?= e($u['user_code']) ?></span>
                        </td>
                        <td data-label="Name">
                          <strong><?= e($u['name']) ?></strong>
                          <?php if (!empty($u['department'])): ?>
                            <small><?= e($u['department']) ?></small>
                          <?php endif; ?>
                        </td>
                        <td data-label="Email">
                          <?= e($u['email']) ?>
                        </td>
                        <td data-label="Role">
                          <span class="role-chip" style="<?= $u['role'] === 'admin' ? 'background: #eef2ff; color: #3730a3;' : '' ?>">
                            <?= e($roleTitle) ?>
                          </span>
                        </td>
                        <td data-label="Registered">
                          <?= e(format_date($u['created_at'], true)) ?>
                        </td>
                        <td data-label="Status">
                          <span class="<?= $statusActive ? 'account-active' : 'account-inactive' ?>" style="<?= !$statusActive ? 'color: #991b1b; background: #fee2e2; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 500;' : '' ?>">
                            <?= ucfirst(e($u['status'] ?? 'active')) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <div class="table-empty" style="padding: 48px 24px; text-align: center;">
                  <h3>No users found</h3>
                  <p><?= ($search !== '' || $roleFilter !== '') ? 'No accounts matched your search criteria.' : 'There are currently no registered accounts.' ?></p>
                  <?php if ($search !== '' || $roleFilter !== ''): ?>
                    <a class="button secondary small" href="users.php" style="text-decoration: none;">View all users</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </section>
        </main>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
