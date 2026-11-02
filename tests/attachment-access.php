<?php
/** Run against local XAMPP: php tests/attachment-access.php [http://localhost/project-pca] */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../config/db_connect.php';
require __DIR__ . '/../includes/attachments.php';
$baseUrl = rtrim($argv[1] ?? 'http://localhost/project-pca', '/');
if (!in_array(parse_url($baseUrl, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true)) {
    exit("Use a local test server only.\n");
}
$sessions = [];
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException($label);
    $checks++;
    echo "PASS: $label\n";
}
function test_session(array $user): string {
    global $sessions;
    $id = bin2hex(random_bytes(24));
    session_id($id);
    session_start();
    $_SESSION = ['user' => $user];
    session_write_close();
    $sessions[] = $id;
    return $id;
}
function request_attachment(string $path, string $session = ''): array {
    global $baseUrl;
    $handle = curl_init($baseUrl . '/' . $path);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10, CURLOPT_COOKIE => 'PHPSESSID=' . $session]);
    $response = curl_exec($handle);
    if ($response === false) throw new RuntimeException(curl_error($handle));
    $size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    $result = [curl_getinfo($handle, CURLINFO_RESPONSE_CODE), substr($response, 0, $size), substr($response, $size)];
    curl_close($handle);
    return $result;
}

// Do not alter users, complaints, attachments or existing sessions.
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');
try {
    $complaint = null;
    foreach ($pdo->query('SELECT c.* FROM complaints c JOIN users u ON u.id=c.user_id WHERE u.status=\'active\' AND c.attachment IS NOT NULL') as $row) {
        $file = complaint_attachment_file($row['attachment']);
        if ($file && complaint_attachment_image_mime($file)) { $complaint = $row; break; }
    }
    if (!$complaint) throw new RuntimeException('An existing active student complaint with an image is needed.');
    $users = $pdo->query('SELECT id, role, status, name, email FROM users WHERE status=\'active\'')->fetchAll();
    $owner = $admin = $other = null;
    foreach ($users as $user) {
        if ((int)$user['id'] === (int)$complaint['user_id']) $owner = $user;
        elseif ($user['role'] === 'student') $other = $user;
        if ($user['role'] === 'admin') $admin = $user;
    }
    if (!$owner || !$admin || !$other) throw new RuntimeException('Owner, admin and another active student are needed.');
    $ownerSession = test_session($owner);
    $adminSession = test_session($admin);
    $otherSession = test_session($other);
    // Even a stale session claiming admin must use the database role.
    $other['role'] = 'admin';
    $staleRoleSession = test_session($other);
    $url = 'attachment.php?id=' . $complaint['id'];
    check(request_attachment($url)[0] === 401, 'Anonymous attachment request denied');
    check(request_attachment($url, $otherSession)[0] === 404, 'Another student cannot view the image');
    check(request_attachment($url . '&download=1', $otherSession)[0] === 404, 'Another student cannot download the image');
    check(request_attachment($url, $staleRoleSession)[0] === 404, 'Stale session role cannot grant admin access');
    foreach (['owner' => $ownerSession, 'admin' => $adminSession] as $role => $session) {
        [$code, $headers, $body] = request_attachment($url, $session);
        check($code === 200 && hash('sha256', $body) === hash_file('sha256', $file), "$role receives the original image");
        check(stripos($headers, 'Content-Disposition: inline;') !== false, "$role can preview inline");
        check(stripos($headers, 'Cache-Control: private, no-store') !== false, "$role response is not cached");
        [$code, $headers, $body] = request_attachment($url . '&download=1', $session);
        check($code === 200 && stripos($headers, 'Content-Disposition: attachment;') !== false, "$role can download");
        $panel = $role === 'owner' ? 'student' : 'admin';
        [$code, , $html] = request_attachment($panel . '/complaint_details.php?id=' . $complaint['id'], $session);
        check($code === 200 && strpos($html, 'data-image-preview') !== false && strpos($html, 'data-preview-zoom-in') !== false, "$panel renders the shared thumbnail and controls");
    }
    check(request_attachment($complaint['attachment'])[0] === 403, 'Direct public upload URL is blocked by Apache');
    check(request_attachment('attachment.php?id[]=14', $ownerSession)[0] === 404, 'Malformed ID denied');
    check(request_attachment('attachment.php?id=2147483647', $ownerSession)[0] === 404, 'Missing complaint denied');
    check(complaint_attachment_file('uploads/complaints/../../config/db_connect.php') === null, 'Traversal rejected');
    check(complaint_attachment_file('uploads/complaints/..\\..\\config\\db_connect.php') === null, 'Windows traversal rejected');
    check(complaint_attachment_image_mime(__FILE__) === null, 'Non-image files cannot render inline');
    echo "$checks checks passed.\n";
} finally {
    foreach ($sessions as $id) {
        session_id($id);
        session_start();
        session_destroy();
    }
}
