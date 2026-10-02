<?php
require_once __DIR__ . '/semaphore.php';

/*
 * Message text is saved with cleanInput(), which turns ' & " < > into HTML codes (e.g. &#039;).
 * Real SMS/emails must contain the plain characters, so decode just before sending.
 */
function plainMessageText($text) {
    return html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sendSMS($to, $message) {
    $result = sendSMSViaSemaphore($to, plainMessageText($message));
    return $result['success'] ?? false;
}

function sendSMSMessage($to, $message) {
    return sendSMS($to, $message);
}

/** Last reason sendEmail() failed (empty if it worked). */
function getLastEmailError() {
    return $GLOBALS['__last_email_error'] ?? '';
}

/**
 * Sends a real email. Returns true/false (the old version only logged and always returned true).
 *
 * Preferred setup (reliable): PHPMailer + SMTP. Run "composer require phpmailer/phpmailer" in your
 * project root and add to config/db.php (or another config file):
 *     define('SMTP_HOST', 'smtp.gmail.com');
 *     define('SMTP_PORT', 587);
 *     define('SMTP_USER', 'yourname@gmail.com');
 *     define('SMTP_PASS', 'your-app-password');
 *     define('MAIL_FROM', 'yourname@gmail.com');
 *     define('MAIL_FROM_NAME', 'USAT Alumni Office');
 * Without that, it falls back to PHP's mail(), which does not work on a default XAMPP install;
 * in that case it returns false and the message history shows the email as failed.
 */
function sendEmail($to, $subject, $body, $replyTo = null) {
    $GLOBALS['__last_email_error'] = '';
    $to = trim((string)$to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $GLOBALS['__last_email_error'] = 'Invalid email address';
        return false;
    }
    $subject = plainMessageText($subject);
    $body = plainMessageText($body);
    $from = defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@localhost';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'USAT Alumni System';

    // ---- Option 1: PHPMailer over SMTP ----
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../vendor/autoload.php'] as $autoload) {
            if (file_exists($autoload)) { require_once $autoload; break; }
        }
    }
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') && defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->Port       = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
            $mail->SMTPSecure = $mail->Port === 465 ? 'ssl' : 'tls';
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $mail->addReplyTo($replyTo);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
            $mail->AltBody = $body;
            $mail->send();
            return true;
        } catch (Throwable $e) {
            $GLOBALS['__last_email_error'] = $e->getMessage();
            error_log("Email to $to failed: " . $e->getMessage());
            return false;
        }
    }

    // ---- Option 2: PHP mail() ----
    $enc = function_exists('mb_encode_mimeheader')
        ? fn($t) => mb_encode_mimeheader($t, 'UTF-8')
        : fn($t) => '=?UTF-8?B?' . base64_encode($t) . '?=';
    $headers = "From: " . $enc($fromName) . " <$from>\r\n"
             . ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? "Reply-To: $replyTo\r\n" : '')
             . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $sent = @mail($to, $enc($subject), $body, $headers);
    if (!$sent) {
        $GLOBALS['__last_email_error'] = 'No email transport configured (install PHPMailer and set SMTP_* constants)';
        error_log("Email to $to failed: " . $GLOBALS['__last_email_error']);
    }
    return $sent;
}