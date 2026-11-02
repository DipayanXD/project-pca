<?php
/**
 * Campus Resolve - Student Complaint Details Page
 * 
 * Displays complete details, status badge, attachment, status timeline,
 * administrative response, and actions (Edit / Delete for Pending complaints).
 * Faithfully matches prototype-4 styling and layout.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_student.php';

$user = current_user();
$userId = (int)($user['id'] ?? 0);

$complaintId = (int)($_GET['id'] ?? 0);
if ($complaintId < 1) {
    set_flash('Please select a valid complaint to view.', 'error');
    header('Location: complaints.php');
    exit;
}

// Fetch complaint and verify student ownership
$stmt = $pdo->prepare("SELECT * FROM `complaints` WHERE `id` = ? AND `user_id` = ?");
$stmt->execute([$complaintId, $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('The requested complaint was not found or you do not have permission to view it.', 'error');
    header('Location: complaints.php');
    exit;
}

// Fetch complaint timeline history
$stmt = $pdo->prepare("
    SELECT u.*, admin.name as admin_name 
    FROM `complaint_updates` u
    LEFT JOIN `users` admin ON u.admin_id = admin.id
    WHERE u.complaint_id = ?
    ORDER BY u.created_at ASC, u.id ASC
");
$stmt->execute([$complaintId]);
$updates = $stmt->fetchAll();

// Find latest administrative remark (if any)
$adminResponse = null;
foreach (array_reverse($updates) as $u) {
    if (!empty($u['admin_id']) && !empty($u['remark'])) {
        $adminResponse = $u;
        break;
    }
}

$active_nav = 'complaints';
$crumb_title = $complaint['complaint_code'];
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= e($complaint['complaint_code']) ?> — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/student-sidebar.php'; ?>

      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main id="main-content" class="app-main">
          <section class="detail-page">
            <a class="back-button" href="complaints.php">
              <svg class="icon" width="18" height="18">
                <use href="<?= $base ?>icons.svg#i-arrow-left" />
              </svg>
              Back to my complaints
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
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 style="margin: 0;">Complaint details</h2>
                    <?php if ($complaint['status'] === 'Pending'): ?>
                      <div class="action-bar">
                        <a class="button secondary small" href="submit_complaint.php?id=<?= (int)$complaint['id'] ?>">
                          Edit complaint
                        </a>
                        <form method="post" action="complaints.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this pending complaint? This action cannot be undone.');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="complaint_id" value="<?= (int)$complaint['id'] ?>" />
                          <button class="button danger small" type="submit">Delete</button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </div>

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
                          <a class="view-link" href="<?= $base . e($complaint['attachment']) ?>" target="_blank" rel="noopener">
                            <svg class="icon" width="16" height="16"><use href="<?= $base ?>icons.svg#i-paperclip" /></svg>
                            View / Download Attachment (<?= e(basename($complaint['attachment'])) ?>)
                          </a>
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
                      <?php foreach ($updates as $idx => $update): ?>
                        <li class="complete">
                          <span>✓</span>
                          <div>
                            <strong><?= e($update['status']) ?></strong>
                            <small><?= e(format_datetime($update['created_at'])) ?> — <?= e($update['remark']) ?></small>
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
                          <small>Currently being handled by campus support</small>
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
                          <small>Review remarks in administrative update</small>
                        </div>
                      </li>
                    <?php endif; ?>
                  </ol>
                </section>

                <!-- Section 3: Administrative Response (If available) -->
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
                        <h2>Update from the campus team</h2>
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

              <!-- Sidebar Card -->
              <aside class="detail-side">
                <section class="panel contact-card">
                  <h3>Need to add context?</h3>
                  <p>
                    If details have changed or you have follow-up information, contact the administration office with your complaint code <strong><?= e($complaint['complaint_code']) ?></strong>.
                  </p>
                  <button
                    class="text-button"
                    type="button"
                    data-toast="Support desk reference prepared for <?= e($complaint['complaint_code']) ?>."
                  >
                    Contact support
                    <svg class="icon" width="18" height="18">
                      <use href="<?= $base ?>icons.svg#i-arrow" />
                    </svg>
                  </button>
                </section>
              </aside>
            </div>
          </section>
        </main>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
