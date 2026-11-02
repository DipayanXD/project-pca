<?php
/**
 * Campus Resolve - Update Complaint Status
 * 
 * Handles POST requests to update the status of a complaint.
 * Restricts access to administrators, validates input data,
 * updates the database using prepared statements, and redirects
 * back to the admin complaints list with a flash notification.
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';
require_once __DIR__ . '/../includes/helpers.php';

// Only accept POST requests
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: complaints.php');
    exit;
}

// Validate CSRF token if provided
if (isset($_POST['csrf_token']) && !validate_csrf()) {
    set_flash('Session security token expired. Please try again.', 'error');
    header('Location: complaints.php');
    exit;
}

// 1. Receive and sanitize inputs
$complaintId = (int)($_POST['complaint_id'] ?? 0);
$newStatus   = trim((string)($_POST['status'] ?? ''));

// 2. Allow only these status values
$allowedStatuses = ['Pending', 'Approved', 'Completed'];

// 3. Validate complaint ID
if ($complaintId < 1) {
    set_flash('Invalid complaint selected for update.', 'error');
    header('Location: complaints.php');
    exit;
}

// 4. Validate status value
if (!in_array($newStatus, $allowedStatuses, true)) {
    set_flash('Invalid status selected. Must be Pending, Approved, or Completed.', 'error');
    header('Location: complaints.php');
    exit;
}

// 5. Verify the complaint exists in the database
$stmtCheck = $pdo->prepare("SELECT `id`, `complaint_code`, `status` FROM `complaints` WHERE `id` = ?");
$stmtCheck->execute([$complaintId]);
$complaint = $stmtCheck->fetch();

if (!$complaint) {
    set_flash('Complaint not found.', 'error');
    header('Location: complaints.php');
    exit;
}

// 6. Update the complaint's status in the database using a prepared statement
$stmtUpdate = $pdo->prepare("UPDATE `complaints` SET `status` = ?, `updated_at` = NOW() WHERE `id` = ?");
$stmtUpdate->execute([$newStatus, $complaintId]);

// 7. Record the status change in the complaint updates history
$user = current_user();
$adminId = isset($user['id']) ? (int)$user['id'] : null;
$remark = "Status updated to {$newStatus} by administrator.";

$stmtLog = $pdo->prepare("
    INSERT INTO `complaint_updates` (`complaint_id`, `admin_id`, `status`, `remark`, `created_at`)
    VALUES (?, ?, ?, ?, NOW())
");
$stmtLog->execute([$complaintId, $adminId, $newStatus, $remark]);

// 8. Set success message and redirect back to admin complaints page
set_flash("Complaint {$complaint['complaint_code']} status updated to '{$newStatus}' successfully.", 'success');
header('Location: complaints.php');
exit;
