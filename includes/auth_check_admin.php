<?php
/**
 * auth_check_admin.php
 * Redirects to admin login if not a logged-in admin.
 */
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$base = $base ?? '../';

if (!is_logged_in()) {
    set_flash('Please sign in as administrator to access this area.', 'info');
    header("Location: {$base}auth/admin_login.php");
    exit;
}

if (!is_admin()) {
    set_flash('Access restricted to administrators.', 'error');
    header("Location: {$base}student/dashboard.php");
    exit;
}
