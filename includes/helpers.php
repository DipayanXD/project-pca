<?php
/**
 * Campus Resolve - View and Utility Helpers
 */

declare(strict_types=1);

/**
 * Safe HTML escaping.
 */
function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSS class and markup for complaint status badge.
 */
function status_badge(string $status): string {
    $normalized = strtolower(trim($status));
    $cls = 'pending';
    $label = 'Pending';

    if ($normalized === 'in progress' || $normalized === 'in-progress' || $normalized === 'under review') {
        $cls = 'in-progress';
        $label = 'In Progress';
    } elseif ($normalized === 'resolved' || $normalized === 'completed') {
        $cls = 'resolved';
        $label = 'Resolved';
    } elseif ($normalized === 'rejected') {
        $cls = 'rejected';
        $label = 'Rejected';
    }

    return '<span class="status ' . e($cls) . '"><i></i>' . e($label) . '</span>';
}

/**
 * Format date for display (e.g. "Sep 10" or "Sep 10, 2026").
 */
function format_date(?string $datetime, bool $includeYear = false): string {
    if (!$datetime) return '';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '';
    return date($includeYear ? 'M j, Y' : 'M j', $timestamp);
}

/**
 * Format datetime for timeline/detail (e.g. "Sep 10, 2:15 PM").
 */
function format_datetime(?string $datetime): string {
    if (!$datetime) return '';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '';
    return date('M j, g:i A', $timestamp);
}

/**
 * Return friendly relative time or formatted string.
 */
function friendly_time(?string $datetime): string {
    if (!$datetime) return 'Recently';
    $time = strtotime($datetime);
    if (!$time) return 'Recently';
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if (date('Y-m-d', $time) === date('Y-m-d', $now)) {
        return 'Today, ' . date('g:i A', $time);
    }
    if (date('Y-m-d', $time) === date('Y-m-d', strtotime('-1 day', $now))) {
        return 'Yesterday, ' . date('g:i A', $time);
    }
    return date('M j, Y', $time);
}
