<?php
/**
 * Campus Resolve - Admin Dashboard
 * Displays high-level operations overview, complaint metrics, activity trends,
 * and complaints requiring administrative attention.
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = current_user();
$adminName = $user['name'] ?? 'Administrator';
$nameParts = explode(' ', trim($adminName));
$adminFirstName = $nameParts[0] ?? 'Admin';

// Dynamic time greeting
$hour = (int)date('G');
if ($hour < 12) {
    $timeGreeting = 'Good morning';
} elseif ($hour < 17) {
    $timeGreeting = 'Good afternoon';
} else {
    $timeGreeting = 'Good evening';
}

// Initial stats
$stats = [
    'total'       => 0,
    'pending'     => 0,
    'in_progress' => 0,
    'resolved'    => 0,
    'rejected'    => 0,
    'students'    => 0,
    'rate'        => 0,
];
$days = [];
$recentComplaints = [];

if (isset($pdo)) {
    try {
        // 1. Complaint statistics by status
        $stmtStats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status IN ('In Progress', 'Under Review') THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
            FROM `complaints`
        ");
        $statsRow = $stmtStats->fetch() ?: [];

        $stats['total']       = (int)($statsRow['total'] ?? 0);
        $stats['pending']     = (int)($statsRow['pending'] ?? 0);
        $stats['in_progress'] = (int)($statsRow['in_progress'] ?? 0);
        $stats['resolved']    = (int)($statsRow['resolved'] ?? 0);
        $stats['rejected']    = (int)($statsRow['rejected'] ?? 0);

        if ($stats['total'] > 0) {
            $stats['rate'] = (int)round(($stats['resolved'] / $stats['total']) * 100);
        }

        // 2. Total registered students
        $stmtStudents = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'student'");
        $stats['students'] = (int)$stmtStudents->fetchColumn();

        // 3. Past 7 days activity (ending today)
        for ($i = 6; $i >= 0; $i--) {
            $time = strtotime("-{$i} days");
            $dKey = date('Y-m-d', $time);
            $days[$dKey] = [
                'day'   => date('D', $time),
                'label' => date('M j', $time),
                'count' => 0
            ];
        }

        $startDate = array_key_first($days);
        $stmtActivity = $pdo->prepare("
            SELECT DATE(created_at) as c_date, COUNT(*) as c_count
            FROM `complaints`
            WHERE DATE(created_at) >= ?
            GROUP BY DATE(created_at)
        ");
        $stmtActivity->execute([$startDate]);
        while ($row = $stmtActivity->fetch()) {
            $d = $row['c_date'];
            if (isset($days[$d])) {
                $days[$d]['count'] = (int)$row['c_count'];
            }
        }

        // 4. Complaints requiring attention (Pending & In Progress first)
        $stmtRecent = $pdo->query("
            SELECT c.*, u.name as student_name, u.email as student_email
            FROM `complaints` c
            JOIN `users` u ON c.user_id = u.id
            WHERE c.status IN ('Pending', 'In Progress', 'Under Review')
            ORDER BY 
              CASE 
                WHEN c.status = 'Pending' THEN 1
                WHEN c.status = 'In Progress' THEN 2
                ELSE 3
              END,
              c.created_at DESC
            LIMIT 10
        ");
        $recentComplaints = $stmtRecent->fetchAll();
    } catch (PDOException $e) {
        error_log("Admin dashboard query exception: " . $e->getMessage());
    }
}

// Calculate max for bar scaling (baseline of 4 so bars scale nicely)
$maxDayCount = !empty($days) ? max(array_column($days, 'count')) : 0;
$chartMax = max(4, $maxDayCount);

// Navigation metadata for components
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
            <h1><?= e($timeGreeting) ?>, <?= e($adminFirstName) ?>.</h1>
            <p>Here’s the current complaint and resolution picture across campus.</p>
          </section>

          <!-- Metrics summary grid -->
          <section class="stats-grid admin-stats">
            <article class="stat-card">
              <div>
                <p>Total complaints</p>
                <strong><?= number_format($stats['total']) ?></strong>
                <span>All recorded</span>
              </div>
              <span class="stat-icon total">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-doc" /></svg>
              </span>
            </article>

            <article class="stat-card">
              <div>
                <p>Pending</p>
                <strong><?= number_format($stats['pending']) ?></strong>
                <span>Needs review</span>
              </div>
              <span class="stat-icon pending">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-settings" /></svg>
              </span>
            </article>

            <article class="stat-card">
              <div>
                <p>In progress</p>
                <strong><?= number_format($stats['in_progress']) ?></strong>
                <span>Being handled</span>
              </div>
              <span class="stat-icon progress">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-settings" /></svg>
              </span>
            </article>

            <article class="stat-card">
              <div>
                <p>Resolved</p>
                <strong><?= number_format($stats['resolved']) ?></strong>
                <span><?= $stats['rate'] ?>% resolution rate</span>
              </div>
              <span class="stat-icon resolved">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-check" /></svg>
              </span>
            </article>

            <article class="stat-card">
              <div>
                <p>Total students</p>
                <strong><?= number_format($stats['students']) ?></strong>
                <span>Registered users</span>
              </div>
              <span class="stat-icon total">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-users" /></svg>
              </span>
            </article>
          </section>

          <!-- Main operational panels -->
          <section class="admin-dashboard">
            <!-- Activity chart panel -->
            <div class="panel activity-panel">
              <div class="panel-head">
                <div>
                  <h2>Complaint activity</h2>
                  <p>Incoming requests over the past 7 days.</p>
                </div>
                <span class="legend"><i></i>Received</span>
              </div>
              <div
                class="chart"
                aria-label="Bar chart showing complaint activity over the past 7 days"
              >
                <div class="chart-lines"></div>
                <?php foreach ($days as $dayData): 
                  $pct = $dayData['count'] > 0 
                    ? max(12, min(95, (int)round(($dayData['count'] / $chartMax) * 90))) 
                    : 4;
                ?>
                  <div class="bar" title="<?= e($dayData['label']) ?>: <?= $dayData['count'] ?> complaints">
                    <i style="height: <?= $pct ?>%;"></i>
                    <span><?= e($dayData['day']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Needs attention table panel -->
            <div class="panel recent-panel">
              <div class="panel-head">
                <div>
                  <h2>Needs attention</h2>
                  <p>Recent campus concerns requiring action.</p>
                </div>
                <a class="text-button" href="<?= $base ?>admin/complaints.php">
                  View all
                  <svg class="icon" width="18" height="18">
                    <use href="<?= $base ?>icons.svg#i-arrow" />
                  </svg>
                </a>
              </div>

              <?php if (empty($recentComplaints)): ?>
                <div class="empty-state" style="padding: 48px 24px; text-align: center;">
                  <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--soft, #f5f6f7); display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--muted, #69707b);">
                    <svg class="icon" width="24" height="24"><use href="<?= $base ?>icons.svg#i-check" /></svg>
                  </div>
                  <h3 style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: var(--ink, #1e293b);">All caught up</h3>
                  <p style="margin: 0; font-size: 14px; color: var(--muted, #69707b);">There are currently no complaints awaiting administrator attention.</p>
                </div>
              <?php else: ?>
                <div class="responsive-table">
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
                      <?php foreach ($recentComplaints as $c): 
                        $detailUrl = $base . 'admin/complaint_details.php?id=' . (int)$c['id'];
                      ?>
                        <tr>
                          <td data-label="ID">
                            <a class="id-link" href="<?= $detailUrl ?>">
                              <?= e($c['complaint_code']) ?>
                            </a>
                          </td>
                          <td data-label="Student">
                            <strong><?= e($c['student_name']) ?></strong>
                            <small><?= e($c['student_email']) ?></small>
                          </td>
                          <td data-label="Complaint">
                            <a href="<?= $detailUrl ?>" style="text-decoration: none; color: inherit;">
                              <strong><?= e($c['title']) ?></strong>
                            </a>
                            <small><?= e($c['location']) ?></small>
                          </td>
                          <td data-label="Category"><?= e($c['category']) ?></td>
                          <td data-label="Date"><?= format_date($c['created_at']) ?></td>
                          <td data-label="Status">
                            <?= status_badge($c['status']) ?>
                          </td>
                          <td>
                            <a class="view-link" href="<?= $detailUrl ?>">
                              View
                              <svg class="icon" width="15" height="15">
                                <use href="<?= $base ?>icons.svg#i-arrow" />
                              </svg>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </section>
        </main>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
