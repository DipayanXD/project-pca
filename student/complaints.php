<?php
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_student.php';

$user = current_user();
$userId = (int)$user['id'];
$categories = ['Infrastructure', 'IT', 'Facilities', 'Laboratory', 'Other'];
$statuses = ['Pending', 'In Progress', 'Resolved', 'Rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawId = $_POST['complaint_id'] ?? 0;
$complaintId = filter_var($rawId, FILTER_VALIDATE_INT) !== false ? (int)$rawId : 0;
    if (!validate_csrf()) {
        set_flash('Your session token expired. Please try again.', 'error');
    } elseif ($complaintId < 1) {
        set_flash('That complaint could not be found.', 'error');
    } else {
        $stmt = $pdo->prepare("SELECT `attachment` FROM `complaints` WHERE `id` = ? AND `user_id` = ? AND `status` = 'Pending'");
        $stmt->execute([$complaintId, $userId]);
        $complaint = $stmt->fetch();
        if (!$complaint) {
            set_flash('Only your pending complaints can be deleted.', 'error');
        } else {
            $stmt = $pdo->prepare("DELETE FROM `complaints` WHERE `id` = ? AND `user_id` = ? AND `status` = 'Pending'");
            $stmt->execute([$complaintId, $userId]);
            if ($stmt->rowCount() === 1) {
                if ($complaint['attachment']) {
                    $filePath = dirname(__DIR__) . '/' . $complaint['attachment'];
                    if (is_file($filePath)) unlink($filePath);
                }
                set_flash('Complaint deleted.', 'success');
            } else {
                set_flash('The complaint could not be deleted. Please try again.', 'error');
            }
        }
    }
    header('Location: complaints.php');
    exit;
}

$search = trim((string)($_GET['q'] ?? ''));
$categoryFilter = (string)($_GET['category'] ?? '');
$statusFilter = (string)($_GET['status'] ?? '');
$sortFilter = (string)($_GET['sort'] ?? 'new');

$sql = 'SELECT `id`, `complaint_code`, `title`, `category`, `location`, `status`, `created_at` FROM `complaints` WHERE `user_id` = ?';
$params = [$userId];
if ($search !== '') {
    $sql .= ' AND (`complaint_code` LIKE ? OR `title` LIKE ? OR `location` LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (in_array($categoryFilter, $categories, true)) {
    $sql .= ' AND `category` = ?';
    $params[] = $categoryFilter;
}
if (in_array($statusFilter, $statuses, true)) {
    $sql .= ' AND `status` = ?';
    $params[] = $statusFilter;
}

if ($sortFilter === 'old') {
    $sql .= ' ORDER BY `created_at` ASC';
} elseif ($sortFilter === 'status') {
    $sql .= ' ORDER BY `status` ASC, `created_at` DESC';
} else {
    $sql .= ' ORDER BY `created_at` DESC';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$active_nav = 'complaints';
$crumb_title = 'My Complaints';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>My Complaints — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css?v=<?= time() ?>" />
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/student-sidebar.php'; ?>
      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>
        <main id="main-content" class="app-main">
          <section class="page-head list-head">
            <div>
              <p class="eyebrow">Your complaint history</p>
              <h1>My Complaints</h1>
              <p>Review your reports and follow their current status.</p>
            </div>
            <a class="button primary" href="<?= $base ?>student/submit_complaint.php">
              <svg class="icon"><use href="<?= $base ?>icons.svg#i-plus" /></svg>New complaint
            </a>
          </section>
          <?php $flash = get_flash(); if ($flash): ?>
            <div class="form-alert <?= ($flash['type'] ?? '') === 'success' ? 'success' : '' ?>" role="<?= ($flash['type'] ?? '') === 'success' ? 'status' : 'alert' ?>">
              <?= e($flash['message']) ?>
            </div>
          <?php endif; ?>

          <section class="panel table-panel">
            <div class="table-controls">
              <label class="search-input">
                <svg class="icon"><use href="<?= $base ?>icons.svg#i-search" /></svg>
                <input
                  id="complaint-search"
                  name="q"
                  value="<?= e($search) ?>"
                  placeholder="Search by title or ID"
                  aria-label="Search complaints"
                />
              </label>
              <div class="filter-row">
                <select
                  name="status"
                  data-complaint-filter="status"
                  aria-label="Filter by status"
                >
                  <option value="">All statuses</option>
                  <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                  <?php endforeach; ?>
                </select>
                <select
                  name="category"
                  data-complaint-filter="category"
                  aria-label="Filter by category"
                >
                  <option value="">All categories</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                  <?php endforeach; ?>
                </select>
                <select name="sort" data-complaint-sort aria-label="Sort complaints">
                  <option value="new" <?= $sortFilter === 'new' ? 'selected' : '' ?>>Newest first</option>
                  <option value="old" <?= $sortFilter === 'old' ? 'selected' : '' ?>>Oldest first</option>
                  <option value="status" <?= $sortFilter === 'status' ? 'selected' : '' ?>>Status</option>
                </select>
              </div>
            </div>
            <div class="loading-line is-hidden" aria-hidden="true"></div>
            <div class="responsive-table">
              <table id="complaint-table" class="<?= empty($complaints) ? 'is-hidden' : '' ?>">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Complaint</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($complaints as $complaint): ?>
                    <tr
                      data-status="<?= e($complaint['status']) ?>"
                      data-category="<?= e($complaint['category']) ?>"
                      data-date="<?= (int)strtotime($complaint['created_at']) ?>"
                    >
                      <td data-label="ID">
                        <a class="id-link" href="<?= $base ?>student/complaint_details.php?id=<?= (int)$complaint['id'] ?>">
                          <?= e($complaint['complaint_code']) ?>
                        </a>
                      </td>
                      <td data-label="Complaint">
                        <a href="<?= $base ?>student/complaint_details.php?id=<?= (int)$complaint['id'] ?>" style="text-decoration: none; color: inherit;">
                          <strong><?= e($complaint['title']) ?></strong>
                        </a>
                        <small><?= e($complaint['location']) ?></small>
                      </td>
                      <td data-label="Category"><?= e($complaint['category']) ?></td>
                      <td data-label="Date"><?= e(format_date($complaint['created_at'], true)) ?></td>
                      <td data-label="Status"><?= status_badge($complaint['status']) ?></td>
                      <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                          <a class="view-link" href="<?= $base ?>student/complaint_details.php?id=<?= (int)$complaint['id'] ?>">
                            View
                            <svg class="icon" width="14" height="14"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
                          </a>
                          <?php if ($complaint['status'] === 'Pending'): ?>
                            <a class="view-link" style="color: #4b5563;" href="<?= $base ?>student/submit_complaint.php?id=<?= (int)$complaint['id'] ?>">Edit</a>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this pending complaint? This action cannot be undone.');">
                              <?= csrf_field() ?>
                              <input type="hidden" name="complaint_id" value="<?= (int)$complaint['id'] ?>" />
                              <button class="view-link" style="color: var(--red); cursor: pointer;" type="submit">Delete</button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="table-empty <?= !empty($complaints) ? 'is-hidden' : '' ?>" id="complaint-empty">
              <svg class="icon" width="28" height="28">
                <use href="<?= $base ?>icons.svg#i-search" />
              </svg>
              <h3><?= $search || $categoryFilter || $statusFilter ? 'No matching complaints' : 'No complaints recorded' ?></h3>
              <p><?= $search || $categoryFilter || $statusFilter ? 'Try another search term or clear a filter.' : 'When you submit a complaint, it will appear here.' ?></p>
              <?php if ($search || $categoryFilter || $statusFilter): ?>
                <button class="button secondary" type="button" data-clear-filters>
                  Clear filters
                </button>
              <?php else: ?>
                <a class="button primary small" href="<?= $base ?>student/submit_complaint.php">
                  Submit a complaint
                </a>
              <?php endif; ?>
            </div>
          </section>
        </main>
      </div>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
