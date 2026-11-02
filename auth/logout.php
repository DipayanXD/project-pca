<?php
/**
 * Campus Resolve - Sign Out
 */
declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../includes/auth.php';

$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
}
set_flash('You have been signed out successfully.', 'info');
header("Location: {$base}auth/login.php");
exit;
