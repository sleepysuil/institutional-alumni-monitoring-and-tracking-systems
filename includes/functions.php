<?php
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return ($_SESSION['role'] ?? '') === 'admin'; }
function isAlumni() { return ($_SESSION['role'] ?? '') === 'alumni'; }
function redirect($url) { header("Location: $url"); exit; }
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60).' minutes ago';
    if ($diff < 86400) return floor($diff/3600).' hours ago';
    if ($diff < 2592000) return floor($diff/86400).' days ago';
    return date('M j, Y', $time);
}
?>