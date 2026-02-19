<?php
require_once '../includes/admin_header.php';
$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';
if ($id && in_array($status, ['reviewed','accepted','rejected'])) {
    $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$status, $id]);
    $_SESSION['message'] = "Application status updated.";
}
redirect('applications.php');
?>