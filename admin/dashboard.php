<?php
/**
 * Campus Resolve - Admin Dashboard
 * Faithfully matches prototype-4 styling, layout, animations, and MySQL data integration.
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';

$currentUser = current_user();
$fullName = $currentUser['name'] ?? 'Administrator';
$firstName = explode(' ', trim($fullName))[0];

// Determine time-aware greeting
$hour = (int)date('G');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

// Initial stats
$stats = [
    'total'          => 0,
    'pending'        => 0,
    'in_progress'    => 0,
    'resolved'       => 0,
    'total_students' => 0,
    'rate'           => 0,
];
$needsAttention = [];
$weeklyActivity = [
    'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0
];

if (isset($pdo)) {
    try {
        // Total complaints
        $stmt = $pdo->query("SELECT COUNT(*) FROM `complaints`");
        $stats['total'] = (int)$stmt->fetchColumn();

        // Pending complaints
        $stmt = $pdo->query("SELECT COUNT(*) FROM `complaints` WHERE `status` = 'Pending'");
        $stats['pending'] = (int)$stmt->fetchColumn();

        // In-progress complaints
        $stmt = $pdo->query("SELECT COUNT(*) FROM `complaints` WHERE `status` IN ('In Progress', 'Under Review')");
        $stats['in_progress'] = (int)$stmt->fetchColumn();

        // Resolved complaints
        $stmt = $pdo->query("SELECT COUNT(*) FROM `complaints` WHERE `status` = 'Resolved'");
        $stats['resolved'] = (int)$stmt->fetchColumn();

        // Total registered students
        $stmt = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'student'");
        $stats['total_students'] = (int)$stmt->fetchColumn();

        if ($stats['total'] > 0) {
            $stats['rate'] = (int)round(($stats['resolved'] / $stats['total']) * 100);
        }

        // Recent complaints requiring attention (Pending & In Progress)
        $stmt = $pdo->query("SELECT c.`id`, c.`complaint_code`, c.`title`, c.`category`, c.`location`, c.`status`, c.`created_at`,
                                    u.`name` AS `student_name`, u.`email` AS `student_email`
                             FROM `complaints` c
                             JOIN `users` u ON c.`user_id` = u.`id`
                             WHERE c.`status` IN ('Pending', 'In Progress', 'Under Review')
                             ORDER BY c.`created_at` DESC
                             LIMIT 6");
        $needsAttention = $stmt->fetchAll();

        // Activity over the past 7 days
        $stmt = $pdo->query("SELECT DATE_FORMAT(`created_at`, '%a') as `day_name`, COUNT(*) as `cnt`
                             FROM `complaints`
                             WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                             GROUP BY DATE_FORMAT(`created_at`, '%a')");
        while ($row = $stmt->fetch()) {
            if (isset($weeklyActivity[$row['day_name']])) {
                $weeklyActivity[$row['day_name']] = (int)$row['cnt'];
            }
        }
    } catch (PDOException $e) {
        error_log("Admin dashboard query exception: " . $e->getMessage());
    }
}

// Compute activity chart bar heights
$maxDay = max(array_values($weeklyActivity));
if ($maxDay < 1) $maxDay = 1;

$active_nav = 'admin-dashboard';
$crumb_title = 'Dashboard';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Dashboard — Campus Resolve</title>
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
            <p class="eyebrow">Operations overview</p>
            <h1><?= e($greeting) ?>, <?= e($firstName) ?>.</h1>
            <p>Here’s the current picture across campus.</p>
          </section>

          <section class="stats-grid admin-stats">
            <article class="stat-card">
              <div>
                <p>Total complaints</p>
                <strong><?= $stats['total'] ?></strong>
                <span>All recorded</span>
              </div>
              <span class="stat-icon total">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-doc" /></svg>
              </span>
            </article>
            <article class="stat-card">
              <div>
                <p>Pending</p>
                <strong><?= $stats['pending'] ?></strong>
                <span>Needs review</span>
              </div>
              <span class="stat-icon pending">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-settings" /></svg>
              </span>
            </article>
            <article class="stat-card">
              <div>
                <p>In progress</p>
                <strong><?= $stats['in_progress'] ?></strong>
                <span>Being handled</span>
              </div>
              <span class="stat-icon progress">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-settings" /></svg>
              </span>
            </article>
            <article class="stat-card">
              <div>
                <p>Resolved</p>
                <strong><?= $stats['resolved'] ?></strong>
                <span><?= $stats['rate'] ?>% resolution rate</span>
              </div>
              <span class="stat-icon resolved">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-check" /></svg>
              </span>
            </article>
            <article class="stat-card">
              <div>
                <p>Total students</p>
                <strong><?= $stats['total_students'] ?></strong>
                <span>Registered users</span>
              </div>
              <span class="stat-icon total">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-users" /></svg>
              </span>
            </article>
          </section>

          <section class="admin-dashboard">
            <div class="panel activity-panel">
              <div class="panel-head">
                <div>
                  <h2>Complaint activity</h2>
                  <p>Incoming requests over the past 7 days.</p>
                </div>
                <span class="legend"><i></i>Received</span>
              </div>
              <div class="chart" aria-label="Bar chart showing complaint activity">
                <div class="chart-lines"></div>
                <?php foreach ($weeklyActivity as $day => $count): 
                  $height = $count > 0 ? max(20, (int)round(($count / $maxDay) * 90)) : 15;
                ?>
                  <div class="bar">
                    <i style="height: <?= $height ?>%"></i>
                    <span><?= e($day) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="panel recent-panel">
              <div class="panel-head">
                <div>
                  <h2>Needs attention</h2>
                  <p>Recent campus concerns requiring action.</p>
                </div>
                <a class="text-button" href="<?= $base ?>admin/complaints/list.php">
                  View all
                  <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
                </a>
              </div>
              <div class="responsive-table">
                <?php if (!empty($needsAttention)): ?>
                  <table>
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Complaint</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th><span class="sr-only">Action</span></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($needsAttention as $c): ?>
                        <tr>
                          <td data-label="ID">
                            <a class="id-link" href="<?= $base ?>admin/complaints/detail.php?id=<?= (int)$c['id'] ?>">
                              <?= e($c['complaint_code']) ?>
                            </a>
                          </td>
                          <td data-label="Student">
                            <strong><?= e($c['student_name']) ?></strong>
                            <small><?= e($c['student_email']) ?></small>
                          </td>
                          <td data-label="Complaint">
                            <strong><?= e($c['title']) ?></strong>
                            <small><?= e($c['location']) ?></small>
                          </td>
                          <td data-label="Category"><?= e($c['category']) ?></td>
                          <td data-label="Date"><?= format_date($c['created_at']) ?></td>
                          <td data-label="Status">
                            <?= status_badge($c['status']) ?>
                          </td>
                          <td>
                            <a class="view-link" href="<?= $base ?>admin/complaints/detail.php?id=<?= (int)$c['id'] ?>">
                              View
                              <svg class="icon" width="15" height="15"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php else: ?>
                  <div class="empty-state" style="padding: 48px 24px; text-align: center;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--soft, #f5f6f7); display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--muted, #69707b);">
                      <svg class="icon" width="24" height="24"><use href="<?= $base ?>icons.svg#i-check" /></svg>
                    </div>
                    <h3 style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: var(--ink);">All caught up</h3>
                    <p style="margin: 0; font-size: 14px; color: var(--muted, #69707b);">There are currently no complaints awaiting administrator attention.</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </section>
        </main>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
