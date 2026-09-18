<?php
/**
 * auth_check_student.php (and auth_check_borrower.php)
 * Redirects to student login if not a logged-in student.
 */
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$base = $base ?? '../';

if (!is_logged_in()) {
    set_flash('Please sign in to access your student workspace.', 'info');
    header("Location: {$base}auth/login.php");
    exit;
}

if (!is_student()) {
    if (is_admin()) {
        header("Location: {$base}admin/dashboard.php");
        exit;
    }
    set_flash('Access restricted to student accounts.', 'error');
    header("Location: {$base}auth/login.php");
    exit;
}
