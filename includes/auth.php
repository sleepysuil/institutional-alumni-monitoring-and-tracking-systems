<?php
/*
 * Loaded at the top of every protected page.
 * - starts the session with safer cookie settings
 * - signs people out after a period of inactivity
 * - stops browsers from showing cached private pages after logout (Back button)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,          // JavaScript cannot read the session cookie
        'samesite' => 'Lax',         // not sent with cross-site POSTs
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Inactivity timeout (seconds). Override by defining SESSION_TIMEOUT in config/db.php.
// Default is 2 hours so alumni filling in the long tracer survey are not logged out mid-way.
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 7200);

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['message'] = 'Your session expired. Please log in again.';
        redirect('../index.php');
    }
    $_SESSION['last_activity'] = time();
}

if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}