<?php
/**
 * Campus Resolve - Admin Complaints Management
 * 
 * Displays all complaints submitted by all students in a clean HTML table.
 * Allows administrators to review details and update the complaint status.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = current_user();
$validStatuses = ['Pending', 'In Progress', 'Resolved', 'Rejected', 'Approved', 'Completed'];

// Handle status update via POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validate_csrf()) {
        set_flash('Session security token expired. Please try again.', 'error');
    } else {
        $complaintId = (int)($_POST['complaint_id'] ?? 0);
        $newStatus   = trim((string)($_POST['status'] ?? ''));

        // Normalize legacy aliases if submitted
        if ($newStatus === 'Approved') {
            $newStatus = 'In Progress';
        } elseif ($newStatus === 'Completed') {
            $newStatus = 'Resolved';
        }

        if ($complaintId < 1) {
            set_flash('Invalid complaint selected for update.', 'error');
        } elseif (!in_array($newStatus, ['Pending', 'In Progress', 'Resolved', 'Rejected'], true)) {
            set_flash('Invalid status selected. Must be Pending, In Progress, Resolved, or Rejected.', 'error');
        } else {
            // Verify complaint exists in database
            $stmtCheck = $pdo->prepare("SELECT `id`, `complaint_code`, `status` FROM `complaints` WHERE `id` = ?");
            $stmtCheck->execute([$complaintId]);
            $complaint = $stmtCheck->fetch();

            if (!$complaint) {
                set_flash('Complaint not found.', 'error');
            } else {
                // Update complaint status in complaints table
                $stmtUpdate = $pdo->prepare("UPDATE `complaints` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
                $stmtUpdate->execute([$newStatus, $complaintId]);

                // Record status update event in timeline history
                $adminId = isset($user['id']) ? (int)$user['id'] : null;
                $remark = "Status updated to {$newStatus} by administrator.";
                $stmtLog = $pdo->prepare("
                    INSERT INTO `complaint_updates` (`complaint_id`, `admin_id`, `status`, `remark`, `created_at`)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmtLog->execute([$complaintId, $adminId, $newStatus, $remark]);

                set_flash("Complaint {$complaint['complaint_code']} status updated to '{$newStatus}' successfully.", 'success');
            }
        }
    }
    header('Location: complaints.php');
    exit;
}

// Retrieve flash message (if any)
$flash = get_flash();

// Fetch all complaints submitted by all students
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.complaint_code,
        c.user_id,
        c.title,
        c.category,
        c.location,
        c.description,
        c.status,
        c.created_at,
        c.updated_at,
        COALESCE(u.name, 'Unknown Student') AS student_name,
        COALESCE(u.email, '') AS student_email,
        COALESCE(u.user_code, '') AS student_code
    FROM `complaints` c
    LEFT JOIN `users` u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$complaints = $stmt->fetchAll();

// Navigation state
$active_nav = 'admin-complaints';
$crumb_title = 'All Complaints';

/**
 * Determine details page URL if available.
 */
function get_details_page_url(int $complaintId): string {
    if (file_exists(__DIR__ . '/complaints/detail.php')) {
        return 'complaints/detail.php?id=' . $complaintId;
    }
    if (file_exists(__DIR__ . '/detail.php')) {
        return 'detail.php?id=' . $complaintId;
    }
    if (file_exists(__DIR__ . '/complaint_details.php')) {
        return 'complaint_details.php?id=' . $complaintId;
    }
    return '#';
}

/**
 * Render appropriate status badge.
 */
function render_complaint_status(string $status): string {
    return status_badge($status);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>All Complaints — Campus Resolve Admin</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
    <style>
      .alert-box {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        line-height: 1.4;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .alert-box.success {
        background: #eaf8f0;
        color: #19734f;
        border: 1px solid #bbf0d6;
      }
      .alert-box.error {
        background: #fdf0ef;
        color: #a7271d;
        border: 1px solid #f6d1ce;
      }
      .action-cluster {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }
      .status-form {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
      }
      .status-select {
        padding: 6px 10px;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        font-size: 12px;
        background: #fff;
        color: #374151;
        outline: none;
      }
      .status-select:focus {
        border-color: #2563eb;
      }
    </style>
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
              <p class="eyebrow">Campus administration</p>
              <h1>All Complaints</h1>
              <p>Review student submissions and update their resolution status.</p>
            </div>
          </section>

          <!-- Success / Error notification message -->
          <?php if ($flash): ?>
            <div class="alert-box <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
              <svg class="icon" width="16" height="16">
                <use href="<?= $base ?>icons.svg#<?= $flash['type'] === 'success' ? 'i-check' : 'i-close' ?>" />
              </svg>
              <span><?= e($flash['message']) ?></span>
            </div>
          <?php endif; ?>

          <!-- Complaints Table Panel -->
          <section class="panel table-panel">
            <div class="responsive-table">
              <?php if (!empty($complaints)): ?>
                <table>
                  <thead>
                    <tr>
                      <th>Complaint ID</th>
                      <th>Student Name</th>
                      <th>Complaint Title</th>
                      <th>Category</th>
                      <th>Submission Date</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($complaints as $c): 
                      $detailUrl = get_details_page_url((int)$c['id']);
                      $curStatus = $c['status'];
                    ?>
                      <tr data-complaint-id="<?= (int)$c['id'] ?>">
                        <td data-label="Complaint ID">
                          <a class="id-link" href="<?= e($detailUrl) ?>">
                            <?= e($c['complaint_code']) ?>
                          </a>
                        </td>
                        <td data-label="Student Name">
                          <strong><?= e($c['student_name']) ?></strong>
                          <small><?= e($c['student_email'] ?: $c['student_code']) ?></small>
                        </td>
                        <td data-label="Complaint Title">
                          <strong><?= e($c['title']) ?></strong>
                          <small><?= e($c['location']) ?></small>
                        </td>
                        <td data-label="Category">
                          <?= e($c['category']) ?>
                        </td>
                        <td data-label="Submission Date">
                          <?= e(format_date($c['created_at'], true)) ?>
                        </td>
                        <td data-label="Status">
                          <?= render_complaint_status($curStatus) ?>
                        </td>
                        <td data-label="Action">
                          <div class="action-cluster">
                            <?php if ($detailUrl !== '#'): ?>
                              <a class="button small secondary view-link" href="<?= e($detailUrl) ?>">
                                View
                                <svg class="icon" width="14" height="14">
                                  <use href="<?= $base ?>icons.svg#i-arrow" />
                                </svg>
                              </a>
                            <?php endif; ?>
                            <form method="POST" action="complaints.php" class="status-form">
                              <?= csrf_field() ?>
                              <input type="hidden" name="complaint_id" value="<?= (int)$c['id'] ?>" />
                              <select name="status" class="status-select" aria-label="Update status for <?= e($c['complaint_code']) ?>">
                                <option value="Pending" <?= ($curStatus === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= ($curStatus === 'In Progress' || $curStatus === 'Approved') ? 'selected' : '' ?>>In Progress</option>
                                <option value="Resolved" <?= ($curStatus === 'Resolved' || $curStatus === 'Completed') ? 'selected' : '' ?>>Resolved</option>
                                <option value="Rejected" <?= ($curStatus === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                              </select>
                              <button type="submit" class="button small primary">
                                Update
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <div class="table-empty">
                  <h3>No complaints recorded</h3>
                  <p>There are currently no student complaints submitted in the database.</p>
                </div>
              <?php endif; ?>
            </div>
          </section>
        </main>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
