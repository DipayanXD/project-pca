<?php
/**
 * Campus Resolve - Student Dashboard
 * Faithfully matches prototype-4 styling, layout, animations, and MySQL data integration.
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_student.php';

$currentUser = current_user();
$userId = (int)($currentUser['id'] ?? 0);
$fullName = $currentUser['name'] ?? 'Student';
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
    'total'       => 0,
    'pending'     => 0,
    'in_progress' => 0,
    'resolved'    => 0,
    'rate'        => 0,
];
$recentComplaints = [];

if (isset($pdo)) {
    try {
        // Query total complaints for student
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `complaints` WHERE `user_id` = ?");
        $stmt->execute([$userId]);
        $stats['total'] = (int)$stmt->fetchColumn();

        // Query pending complaints
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `complaints` WHERE `user_id` = ? AND `status` = 'Pending'");
        $stmt->execute([$userId]);
        $stats['pending'] = (int)$stmt->fetchColumn();

        // Query in-progress / under review complaints
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `complaints` WHERE `user_id` = ? AND `status` IN ('In Progress', 'Under Review')");
        $stmt->execute([$userId]);
        $stats['in_progress'] = (int)$stmt->fetchColumn();

        // Query resolved complaints
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `complaints` WHERE `user_id` = ? AND `status` = 'Resolved'");
        $stmt->execute([$userId]);
        $stats['resolved'] = (int)$stmt->fetchColumn();

        // Calculate resolution completion rate
        if ($stats['total'] > 0) {
            $stats['rate'] = (int)round(($stats['resolved'] / $stats['total']) * 100);
        }

        // Query recent 5 complaints
        $stmt = $pdo->prepare("SELECT `id`, `complaint_code`, `title`, `category`, `location`, `status`, `created_at` 
                               FROM `complaints` 
                               WHERE `user_id` = ? 
                               ORDER BY `created_at` DESC 
                               LIMIT 5");
        $stmt->execute([$userId]);
        $recentComplaints = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Dashboard query exception: " . $e->getMessage());
    }
}

$active_nav = 'dashboard';
$crumb_title = 'Dashboard';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Student Dashboard — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/student-sidebar.php'; ?>

      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main id="main-content" class="app-main">
          <section class="page-head dashboard-head">
            <div>
              <p class="eyebrow">Your workspace</p>
              <h1><?= e($greeting) ?>, <?= e($firstName) ?>.</h1>
              <p>Here’s what’s happening with your complaints.</p>
            </div>
            <a class="button primary" href="<?= $base ?>student/submit_complaint.php">
              <svg class="icon"><use href="<?= $base ?>icons.svg#i-plus" /></svg>New complaint
            </a>
          </section>

          <section class="stats-grid">
            <article class="stat-card">
              <div>
                <p>Total complaints</p>
                <strong><?= $stats['total'] ?></strong>
                <span>All submitted</span>
              </div>
              <span class="stat-icon total">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-doc" /></svg>
              </span>
            </article>
            <article class="stat-card">
              <div>
                <p>Pending</p>
                <strong><?= $stats['pending'] ?></strong>
                <span>Awaiting review</span>
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
                <span><?= $stats['rate'] ?>% complete</span>
              </div>
              <span class="stat-icon resolved">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-check" /></svg>
              </span>
            </article>
          </section>

          <section class="content-grid">
            <div class="panel recent-panel">
              <div class="panel-head">
                <div>
                  <h2>Recent complaints</h2>
                  <p>Your latest updates, all in one place.</p>
                </div>
                <a class="text-button" href="<?= $base ?>student/complaints.php">
                  View all
                  <svg class="icon" width="18" height="18"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
                </a>
              </div>
              <div class="responsive-table">
                <?php if (!empty($recentComplaints)): ?>
                  <table>
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Complaint</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th><span class="sr-only">Action</span></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentComplaints as $c): ?>
                        <tr>
                          <td data-label="ID">
                            <a class="id-link" href="<?= $base ?>student/complaint_details.php?id=<?= (int)$c['id'] ?>">
                              <?= e($c['complaint_code']) ?>
                            </a>
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
                            <a class="view-link" href="<?= $base ?>student/complaint_details.php?id=<?= (int)$c['id'] ?>">
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
                      <svg class="icon" width="24" height="24"><use href="<?= $base ?>icons.svg#i-doc" /></svg>
                    </div>
                    <h3 style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: var(--ink);">No complaints submitted yet</h3>
                    <p style="margin: 0 0 20px; font-size: 14px; color: var(--muted, #69707b);">When you raise an issue or report a concern, you can track its status and follow-ups right here.</p>
                    <a class="button primary small" href="<?= $base ?>student/submit_complaint.php">
                      Submit a complaint
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <aside class="help-card">
              <div class="help-icon">
                <svg class="icon" width="24" height="24"><use href="<?= $base ?>icons.svg#i-file-plus" /></svg>
              </div>
              <h2>Need to report something?</h2>
              <p>Share the details and your campus team can take it from there.</p>
              <a class="button" href="<?= $base ?>student/submit_complaint.php">Submit a complaint</a>
            </aside>
          </section>
        </main>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
