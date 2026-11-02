<?php
/**
 * Campus Resolve - Admin Complaint Details Page
 * 
 * Allows administrators to review complaint details, inspect student info,
 * view attachments, see timeline history, and update complaint status with remarks.
 * Faithfully matches prototypes/prototype-4/admin-complaint-details.html.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = current_user();
$adminId = isset($user['id']) ? (int)$user['id'] : null;

$rawId = $_GET['id'] ?? $_POST['complaint_id'] ?? 0;
$complaintId = filter_var($rawId, FILTER_VALIDATE_INT) !== false ? (int)$rawId : 0;
if ($complaintId < 1) {
    set_flash('Please select a valid complaint to view.', 'error');
    header('Location: complaints.php');
    exit;
}

// -----------------------------------------------------------------------------
// Handle Status Update POST
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        set_flash('Security token expired. Please try again.', 'error');
    } else {
        $newStatus = trim((string)($_POST['status'] ?? ''));
        $remark    = trim((string)($_POST['remark'] ?? ''));

        // Normalize legacy aliases
        if ($newStatus === 'Approved') {
            $newStatus = 'In Progress';
        } elseif ($newStatus === 'Completed') {
            $newStatus = 'Resolved';
        }

        $validStatuses = ['Pending', 'In Progress', 'Resolved', 'Rejected'];
        if (!in_array($newStatus, $validStatuses, true)) {
            set_flash('Invalid status selected.', 'error');
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Update complaints table
                $stmtUpdate = $pdo->prepare("UPDATE `complaints` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
                $stmtUpdate->execute([$newStatus, $complaintId]);

                // 2. Insert update history
                $actionRemark = $remark !== '' ? $remark : "Status updated to {$newStatus} by administrator.";
                $stmtLog = $pdo->prepare("
                    INSERT INTO `complaint_updates` (`complaint_id`, `admin_id`, `status`, `remark`, `created_at`)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmtLog->execute([$complaintId, $adminId, $newStatus, $actionRemark]);

                $pdo->commit();
                set_flash("Complaint status updated to '{$newStatus}' successfully.", 'success');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Status update error: " . $e->getMessage());
                set_flash('A database error occurred while updating the complaint.', 'error');
            }
        }
    }
    header('Location: complaint_details.php?id=' . $complaintId);
    exit;
}

// -----------------------------------------------------------------------------
// Fetch Complaint and Submitter Information
// -----------------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        COALESCE(u.name, 'Unknown Student') AS student_name,
        COALESCE(u.email, '') AS student_email,
        COALESCE(u.user_code, '') AS student_code,
        COALESCE(u.department, 'Student') AS student_dept,
        COALESCE(u.status, 'active') AS student_status
    FROM `complaints` c
    LEFT JOIN `users` u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('The requested complaint could not be found.', 'error');
    header('Location: complaints.php');
    exit;
}

// -----------------------------------------------------------------------------
// Fetch Timeline History
// -----------------------------------------------------------------------------
$stmtUpdates = $pdo->prepare("
    SELECT u.*, a.name AS admin_name
    FROM `complaint_updates` u
    LEFT JOIN `users` a ON u.admin_id = a.id
    WHERE u.complaint_id = ?
    ORDER BY u.created_at ASC, u.id ASC
");
$stmtUpdates->execute([$complaintId]);
$updates = $stmtUpdates->fetchAll();

// Find latest administrative remark (if any)
$adminResponse = null;
foreach (array_reverse($updates) as $u) {
    if (!empty($u['admin_id']) && !empty($u['remark'])) {
        $adminResponse = $u;
        break;
    }
}

$active_nav = 'admin-complaints';
$crumb_title = $complaint['complaint_code'];
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Manage <?= e($complaint['complaint_code']) ?> — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css?v=<?= time() ?>" />
    <link rel="stylesheet" href="<?= $base ?>assets/css/attachment-preview.css" />
    <script src="<?= $base ?>assets/js/attachment-preview.js" defer></script>
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>
        <main id="main-content" class="app-main">
          <section class="detail-page">
            <a class="back-button" href="complaints.php">
              <svg class="icon" width="18" height="18">
                <use href="<?= $base ?>icons.svg#i-arrow-left" />
              </svg>
              Back to all complaints
            </a>

            <?php if ($flash): ?>
              <div class="form-alert <?= $flash['type'] === 'success' ? 'success' : '' ?>" role="status">
                <?= e($flash['message']) ?>
              </div>
            <?php endif; ?>

            <div class="detail-hero">
              <div>
                <p class="eyebrow"><?= e($complaint['complaint_code']) ?></p>
                <h1><?= e($complaint['title']) ?></h1>
                <p><?= e($complaint['category']) ?> · <?= e($complaint['location']) ?></p>
              </div>
              <div class="detail-status">
                <?= status_badge($complaint['status']) ?>
                <small>Last updated <?= e(friendly_time($complaint['updated_at'])) ?></small>
              </div>
            </div>

            <div class="detail-grid">
              <div class="detail-main">
                <!-- Section 1: Complaint Details -->
                <section class="panel detail-section">
                  <h2>Complaint details</h2>
                  <p class="detail-description">
                    <?= nl2br(e($complaint['description'])) ?>
                  </p>
                  <dl>
                    <div>
                      <dt>Category</dt>
                      <dd><?= e($complaint['category']) ?></dd>
                    </div>
                    <div>
                      <dt>Location</dt>
                      <dd><?= e($complaint['location']) ?></dd>
                    </div>
                    <div>
                      <dt>Submitted</dt>
                      <dd><?= e(format_date($complaint['created_at'], true)) ?></dd>
                    </div>
                    <div>
                      <dt>Last updated</dt>
                      <dd><?= e(format_datetime($complaint['updated_at'])) ?></dd>
                    </div>
                    <?php if (!empty($complaint['attachment'])): ?>
                      <div style="grid-column: 1 / -1;">
                        <dt>Attachment</dt>
                        <dd>
                          <?php require __DIR__ . '/../includes/attachment-preview.php'; ?>
                        </dd>
                      </div>
                    <?php endif; ?>
                  </dl>
                </section>

                <!-- Section 2: Status Timeline -->
                <section class="panel detail-section">
                  <h2>Status timeline</h2>
                  <ol class="timeline">
                    <?php if (!empty($updates)): ?>
                      <?php foreach ($updates as $update): ?>
                        <li class="complete">
                          <span>✓</span>
                          <div>
                            <strong><?= e($update['status']) ?></strong>
                            <small>
                              <?= e(format_datetime($update['created_at'])) ?>
                              <?php if (!empty($update['admin_name'])): ?>
                                · <?= e($update['admin_name']) ?>
                              <?php endif; ?>
                              — <?= e($update['remark']) ?>
                            </small>
                          </div>
                        </li>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <li class="complete">
                        <span>✓</span>
                        <div>
                          <strong>Submitted</strong>
                          <small><?= e(format_datetime($complaint['created_at'])) ?> — Complaint registered.</small>
                        </div>
                      </li>
                    <?php endif; ?>

                    <?php if ($complaint['status'] === 'In Progress'): ?>
                      <li class="current">
                        <span></span>
                        <div>
                          <strong>In Progress</strong>
                          <small>Currently being handled</small>
                        </div>
                      </li>
                      <li>
                        <span></span>
                        <div>
                          <strong>Resolved</strong>
                          <small>Awaiting completion</small>
                        </div>
                      </li>
                    <?php elseif ($complaint['status'] === 'Pending'): ?>
                      <li class="current">
                        <span></span>
                        <div>
                          <strong>Pending Review</strong>
                          <small>Awaiting administrative review</small>
                        </div>
                      </li>
                      <li>
                        <span></span>
                        <div>
                          <strong>In Progress</strong>
                          <small>Scheduled for resolution</small>
                        </div>
                      </li>
                      <li>
                        <span></span>
                        <div>
                          <strong>Resolved</strong>
                          <small>Final confirmation</small>
                        </div>
                      </li>
                    <?php elseif ($complaint['status'] === 'Resolved'): ?>
                      <li class="complete">
                        <span>✓</span>
                        <div>
                          <strong>Resolved</strong>
                          <small>Case successfully resolved and closed</small>
                        </div>
                      </li>
                    <?php elseif ($complaint['status'] === 'Rejected'): ?>
                      <li class="complete" style="color: var(--red);">
                        <span>✕</span>
                        <div>
                          <strong>Rejected</strong>
                          <small>Complaint marked as rejected</small>
                        </div>
                      </li>
                    <?php endif; ?>
                  </ol>
                </section>

                <!-- Section 3: Administrative Response -->
                <?php if ($adminResponse): ?>
                  <section class="panel detail-section response">
                    <div>
                      <span class="response-icon">
                        <svg class="icon" width="17" height="17">
                          <use href="<?= $base ?>icons.svg#i-check" />
                        </svg>
                      </span>
                      <div>
                        <p class="eyebrow">Administrative response</p>
                        <h2>Update from the team</h2>
                      </div>
                    </div>
                    <p><?= nl2br(e($adminResponse['remark'])) ?></p>
                    <small>
                      <?= e($adminResponse['admin_name'] ?? 'Campus Administration Team') ?> · 
                      <?= e(friendly_time($adminResponse['created_at'])) ?>
                    </small>
                  </section>
                <?php endif; ?>
              </div>

              <!-- Sidebar Controls & Student Card -->
              <aside class="detail-side">
                <!-- Manage Complaint Form Card -->
                <section class="panel admin-actions">
                  <p class="eyebrow">Manage complaint</p>
                  <h3>Update the status</h3>
                  <form method="POST" action="complaint_details.php" id="status-update-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="complaint_id" value="<?= (int)$complaint['id'] ?>" />

                    <label class="field">
                      <span>Complaint status</span>
                      <select name="status" id="status-select" required>
                        <option value="Pending" <?= $complaint['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $complaint['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= $complaint['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="Rejected" <?= $complaint['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                      </select>
                    </label>

                    <label class="field">
                      <span>Add an administrative remark</span>
                      <textarea
                        name="remark"
                        id="admin-remark"
                        rows="4"
                        placeholder="Share a helpful update for the student."
                      ><?= e($adminResponse['remark'] ?? '') ?></textarea>
                    </label>

                    <button
                      class="button primary full"
                      type="button"
                      data-open-modal="status-update-modal"
                      id="btn-open-status-modal"
                    >
                      Save update
                    </button>
                  </form>
                </section>

                <!-- Student Information Card -->
                <section class="panel student-card">
                  <p class="eyebrow">Submitted by</p>
                  <span class="avatar large"><?= e(user_initials($complaint['student_name'])) ?></span>
                  <h3><?= e($complaint['student_name']) ?></h3>
                  <p><?= e($complaint['student_email']) ?></p>
                  <small><?= e($complaint['student_dept']) ?> · <?= ucfirst(e($complaint['student_status'])) ?></small>
                </section>
              </aside>
            </div>
          </section>
        </main>
      </div>
    </div>

    <!-- Status Confirmation Modal -->
    <div class="modal-backdrop is-hidden" id="status-update-modal" aria-hidden="true">
      <section
        class="modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >
        <button
          class="modal-close"
          type="button"
          data-close-modal
          aria-label="Close"
        >
          <svg class="icon"><use href="<?= $base ?>icons.svg#i-close" /></svg>
        </button>
        <span class="modal-symbol">
          <svg class="icon" width="22" height="22">
            <use href="<?= $base ?>icons.svg#i-check" />
          </svg>
        </span>
        <h2 id="modal-title">Save this update?</h2>
        <p>
          The student will see the updated status and your response in their complaint timeline.
        </p>
        <div class="modal-actions">
          <button class="button secondary" type="button" data-close-modal>
            Cancel
          </button>
          <button class="button primary" type="button" id="confirm-status-update">
            Confirm & save
          </button>
        </div>
      </section>
    </div>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const confirmBtn = document.getElementById("confirm-status-update");
        const form = document.getElementById("status-update-form");
        if (confirmBtn && form) {
          confirmBtn.addEventListener("click", function () {
            form.submit();
          });
        }
      });
    </script>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
