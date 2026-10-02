<?php
// NOTE: this file intentionally has no PHP closing tag at the end. The old file had a stray space
// after it, which was sent to the browser on every request and can cause "headers already sent" errors.

function cleanInput($data) {
    // (string) cast: trim(null) is deprecated in PHP 8.1+
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function statusBadgeClass($status) {
    $map = [
        'Employed' => 'bg-success',
        'Self-Employed' => 'bg-info',
        'Unemployed' => 'bg-danger',
        'Pursuing Higher Education' => 'bg-warning',
        'No Data' => 'bg-secondary',
        'No Survey' => 'bg-secondary',
    ];
    return $map[$status] ?? 'bg-secondary';
}

function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin()    { return ($_SESSION['role'] ?? '') === 'admin'; }
function isAlumni()   { return ($_SESSION['role'] ?? '') === 'alumni'; }

function redirect($url) {
    header("Location: $url");
    exit;
}

function timeAgo($datetime) {
    $time = strtotime((string)$datetime);
    if (!$time) return '';
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';              // also covers timestamps slightly in the future
    $plural = fn($n, $unit) => $n . ' ' . $unit . ($n == 1 ? '' : 's') . ' ago';   // "1 minute ago", not "1 minutes ago"
    if ($diff < 3600)    return $plural((int)floor($diff / 60), 'minute');
    if ($diff < 86400)   return $plural((int)floor($diff / 3600), 'hour');
    if ($diff < 2592000) return $plural((int)floor($diff / 86400), 'day');
    return date('M j, Y', $time);
}