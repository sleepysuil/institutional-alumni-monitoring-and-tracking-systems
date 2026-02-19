<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}
?>