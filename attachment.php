<?php
/** Authorized delivery for both inline previews and downloads. Never accept a file path from the request. */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/attachments.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');

function attachment_not_found(): void {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Attachment not available.');
}

if (!is_logged_in()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Please sign in to view this attachment.');
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    attachment_not_found();
}

require_once __DIR__ . '/config/db_connect.php';
// Recheck the account so a disabled account or outdated role cannot retain access.
$stmt = $pdo->prepare('SELECT c.attachment FROM complaints c JOIN users u ON u.id = ?
    WHERE c.id = ? AND u.status = \'active\'
    AND (u.role = \'admin\' OR (u.role = \'student\' AND c.user_id = u.id))');
$stmt->execute([(int)current_user()['id'], $id]);
$attachment = $stmt->fetchColumn();
$file = $attachment ? complaint_attachment_file($attachment) : null;
if ($file === null) {
    attachment_not_found();
}

$imageMime = complaint_attachment_image_mime($file);
$download = ($_GET['download'] ?? '') === '1' || $imageMime === null;
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file));
$stream = fopen($file, 'rb');
if ($stream === false) {
    attachment_not_found();
}
session_write_close();
header('Content-Type: ' . ($imageMime ?? 'application/octet-stream'));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
header('Content-Length: ' . fstat($stream)['size']);
if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    fpassthru($stream);
}
fclose($stream);
