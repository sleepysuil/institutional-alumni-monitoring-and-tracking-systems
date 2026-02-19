<?php
require_once '../config/db.php';
require_once '../includes/notifications.php';
$type = $_GET['type'] ?? '';
if ($type == 'tracer') {
    $alumni = $pdo->query("SELECT phone, first_name FROM alumni")->fetchAll();
    foreach ($alumni as $a) {
        sendSMS($a['phone'], "Please update your tracer survey.");
    }
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>'Invalid type']);
}
?>  