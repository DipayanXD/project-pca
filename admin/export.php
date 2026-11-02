<?php
/**
 * Campus Resolve - Export Complaints to CSV
 * Allows administrators to download a CSV of all complaints and their details.
 */

declare(strict_types=1);

$base = $base ?? '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth_check_admin.php';

// Fetch all complaints with submitter details
$stmt = $pdo->query("
    SELECT 
        c.id,
        c.complaint_code,
        u.name AS student_name,
        u.email AS student_email,
        u.user_code AS student_code,
        u.department AS student_department,
        c.category,
        c.title,
        c.location,
        c.description,
        c.status,
        c.attachment,
        c.created_at,
        c.updated_at
    FROM `complaints` c
    LEFT JOIN `users` u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$complaints = $stmt->fetchAll();

// Clean output buffer before sending headers
if (ob_get_level()) {
    ob_end_clean();
}

$filename = 'complaints-export-' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header row
fputcsv($output, [
    'Complaint Code',
    'Title',
    'Category',
    'Location',
    'Status',
    'Student Name',
    'Student Email',
    'Student ID',
    'Department',
    'Description',
    'Attachment',
    'Date Submitted',
    'Last Updated'
]);

// Data rows
foreach ($complaints as $row) {
    fputcsv($output, [
        $row['complaint_code'],
        $row['title'],
        $row['category'],
        $row['location'],
        $row['status'],
        $row['student_name'] ?? 'Unknown',
        $row['student_email'] ?? '',
        $row['student_code'] ?? '',
        $row['student_department'] ?? '',
        $row['description'],
        $row['attachment'] ?? 'None',
        $row['created_at'],
        $row['updated_at']
    ]);
}

fclose($output);
exit;

