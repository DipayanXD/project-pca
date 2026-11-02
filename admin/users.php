<?php
/**
 * Campus Resolve - Admin User Management
 * 
 * Displays all registered student accounts in a simple HTML table
 * with searching capabilities.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/helpers.php';

// Navigation state
$active_nav = 'users';
$crumb_title = 'Users';

// Handle optional search query by student name or email
$search = trim((string)($_GET['q'] ?? ''));

$sql = "
    SELECT 
        `id`,
        `user_code`,
        `name`,
        `email`,
        `role`,
        `department`,
        `created_at`
    FROM `users`
    WHERE `role` = 'student'
";
$params = [];

if ($search !== '') {
    $sql .= " AND (`name` LIKE ? OR `email` LIKE ? OR `user_code` LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY `created_at` DESC, `id` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Manage Users — Campus Resolve Admin</title>
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
            <div>
              <p class="eyebrow">Account directory</p>
              <h1>Manage Users</h1>
              <p>Directory of registered student accounts on Campus Resolve.</p>
            </div>
          </section>

          <section class="panel table-panel">
            <!-- Search Form -->
            <form class="table-controls" method="GET" action="users.php">
              <label class="search-input">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-search" /></svg>
                <input
                  type="search"
                  name="q"
                  value="<?= e($search) ?>"
                  placeholder="Search by student name or email"
                  aria-label="Search students"
                />
              </label>
              <div class="filter-row">
                <button class="button secondary small" type="submit">Search</button>
                <?php if ($search !== ''): ?>
                  <a class="button secondary small" href="users.php" style="text-decoration: none;">Reset</a>
                <?php endif; ?>
              </div>
            </form>

            <div class="responsive-table">
              <?php if (!empty($students)): ?>
                <table>
                  <thead>
                    <tr>
                      <th>Student ID</th>
                      <th>Student Name</th>
                      <th>Email</th>
                      <th>Role</th>
                      <th>Registration Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $student): ?>
                      <tr>
                        <td data-label="Student ID">
                          <span class="id-link"><?= e($student['user_code']) ?></span>
                        </td>
                        <td data-label="Student Name">
                          <strong><?= e($student['name']) ?></strong>
                          <?php if (!empty($student['department'])): ?>
                            <small><?= e($student['department']) ?></small>
                          <?php endif; ?>
                        </td>
                        <td data-label="Email">
                          <?= e($student['email']) ?>
                        </td>
                        <td data-label="Role">
                          <?= e(ucfirst($student['role'])) ?>
                        </td>
                        <td data-label="Registration Date">
                          <?= e(format_date($student['created_at'], true)) ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <div class="table-empty">
                  <h3>No students found</h3>
                  <p><?= $search !== '' ? 'No student accounts matched your search.' : 'There are currently no registered student accounts.' ?></p>
                  <?php if ($search !== ''): ?>
                    <a class="button secondary small" href="users.php" style="text-decoration: none;">View all students</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </section>
        </main>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
