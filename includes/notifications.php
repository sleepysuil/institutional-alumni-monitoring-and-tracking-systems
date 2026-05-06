<?php
require_once __DIR__ . '/semaphore.php';

function sendSMS($to, $message) {
    $result = sendSMSViaSemaphore($to, $message);
    return $result['success'] ?? false;
}

function sendSMSMessage($to, $message) {
    return sendSMS($to, $message);
}

function sendEmail($to, $subject, $body) {
    error_log("Email would be sent to: $to, Subject: $subject");
    return true;
}
?>