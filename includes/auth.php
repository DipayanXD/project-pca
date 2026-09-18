<?php
/**
 * Campus Resolve - Authentication & Session Helper
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get currently authenticated user array or null.
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if a user session is active.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Check if the active user is an administrator.
 */
function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * Check if the active user is a student.
 */
function is_student(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'student';
}

/**
 * Require authentication. Redirects to login if not authenticated.
 */
function require_login(string $redirect = ''): void {
    global $base;
    $b = $base ?? '../';
    $target = !empty($redirect) ? $redirect : "{$b}auth/login.php";
    if (!is_logged_in()) {
        set_flash('Please sign in to access this page.', 'info');
        header("Location: {$target}");
        exit;
    }
}

/**
 * Require student role.
 */
function require_student(): void {
    global $base;
    $b = $base ?? '../';
    require_login("{$b}auth/login.php");
    if (!is_student()) {
        if (is_admin()) {
            header("Location: {$b}admin/dashboard.php");
            exit;
        }
        set_flash('Access restricted to student accounts.', 'error');
        header("Location: {$b}auth/login.php");
        exit;
    }
}

/**
 * Require admin role.
 */
function require_admin(): void {
    global $base;
    $b = $base ?? '../';
    require_login("{$b}auth/admin_login.php");
    if (!is_admin()) {
        set_flash('Access restricted to administrators.', 'error');
        header("Location: {$b}student/dashboard.php");
        exit;
    }
}

/**
 * Set a session flash notification.
 */
function set_flash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type, // 'success', 'error', 'info'
    ];
}

/**
 * Retrieve and clear the flash notification.
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate or get current CSRF token.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Return hidden HTML input for CSRF.
 */
function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate incoming CSRF token.
 */
function validate_csrf(): bool {
    $incoming = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($incoming) && hash_equals($_SESSION['csrf_token'] ?? '', $incoming);
}

/**
 * Get user initials for avatars.
 */
function user_initials(?string $name): string {
    if (!$name) return 'CR';
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        if (mb_strlen($initials) >= 2) break;
    }
    return $initials ?: 'CR';
}
