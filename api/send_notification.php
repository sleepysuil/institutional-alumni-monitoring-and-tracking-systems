<?php
require_once '../config/db.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

if ($type == 'tracer') {
    // Get all alumni with phone numbers
    $alumni = $pdo->query("SELECT phone, first_name FROM alumni WHERE phone IS NOT NULL AND phone != ''")->fetchAll();
    
    $success_count = 0;
    foreach ($alumni as $a) {
        $message = "Dear {$a['first_name']}, please update your tracer survey at USAT Alumni System.";
        if (sendSMS($a['phone'], $message)) {
            $success_count++;
        }
        usleep(100000); // Small delay
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Tracer survey notifications sent to $success_count of " . count($alumni) . " alumni."
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid type']);
}
?>