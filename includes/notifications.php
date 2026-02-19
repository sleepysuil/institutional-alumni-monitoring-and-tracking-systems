<?php
function sendEmail($to, $subject, $body) {
    // Placeholder – integrate PHPMailer here
    error_log("Email to $to: $subject");
    return true;
}

function sendSMS($number, $message) {
    // Placeholder – integrate SMS API here
    error_log("SMS to $number: $message");
    return true;
}
?>