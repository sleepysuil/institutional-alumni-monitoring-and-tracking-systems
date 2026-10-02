<?php
if (!defined('SEMAPHORE_API_URL')) define('SEMAPHORE_API_URL', 'https://api.semaphore.co/api/v4/messages');
if (!defined('SEMAPHORE_ACCOUNT_URL')) define('SEMAPHORE_ACCOUNT_URL', 'https://api.semaphore.co/api/v4/account');

/**
 * Normalises a Philippine mobile number to 09xxxxxxxxx.
 * Accepts 09171234567, 639171234567, +63 917 123 4567 and 9171234567. Returns null if invalid.
 */
function normalizePhMobile($phone) {
    $phone = preg_replace('/[^0-9]/', '', (string)$phone);
    if (strpos($phone, '63') === 0 && strlen($phone) === 12) $phone = '0' . substr($phone, 2);
    elseif (strlen($phone) === 10 && $phone[0] === '9')       $phone = '0' . $phone;
    return (strlen($phone) === 11 && strpos($phone, '09') === 0) ? $phone : null;
}

/** Shared cURL call. Returns [httpCode, body, curlError]. */
function semaphoreRequest($url, $postData = null, $timeout = 30) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    // Certificates are verified by default. On a local XAMPP without a CA bundle this fails with
    // "SSL certificate problem": fix it by setting curl.cainfo in php.ini, or (local testing ONLY) add
    // define('SEMAPHORE_VERIFY_SSL', false); to your config.
    $verify = !defined('SEMAPHORE_VERIFY_SSL') || SEMAPHORE_VERIFY_SSL;
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify ? 2 : 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    if (PHP_VERSION_ID < 80000) curl_close($ch);
    return [$code, $body === false ? '' : $body, $err];
}

function sendSMSViaSemaphore($phone, $message) {
    if (!defined('SEMAPHORE_API_KEY') || SEMAPHORE_API_KEY === '') {
        return ['success' => false, 'error' => 'Semaphore API key is not configured'];
    }
    $number = normalizePhMobile($phone);
    if ($number === null) {
        return ['success' => false, 'error' => 'Invalid phone number format. Must be 09xxxxxxxxx'];
    }
    if (trim((string)$message) === '') {
        return ['success' => false, 'error' => 'Message cannot be empty'];
    }

    [$httpCode, $response, $curlError] = semaphoreRequest(SEMAPHORE_API_URL, [
        'apikey'     => SEMAPHORE_API_KEY,
        'number'     => $number,
        'message'    => $message,
        'sendername' => defined('SEMAPHORE_SENDER') ? SEMAPHORE_SENDER : 'SEMAPHORE',
    ]);
    error_log("Semaphore SMS - HTTP: $httpCode, Response: " . substr($response, 0, 200));

    if ($curlError) {
        return ['success' => false, 'error' => "cURL Error: $curlError"];
    }

    $result = json_decode($response, true);

    // Errors come back as an object like {"error": "..."} (or {"apikey": ["..."]})
    if (is_array($result) && !isset($result[0])) {
        $err = $result['error'] ?? (is_array(reset($result)) ? implode(' ', reset($result)) : reset($result));
        return ['success' => false, 'error' => $err ?: "Semaphore returned HTTP $httpCode"];
    }

    // Success is a list of message objects; status comes back capitalised ("Queued", "Pending", "Sent")
    if (is_array($result) && isset($result[0]['status'])) {
        $status = strtolower((string)$result[0]['status']);
        if (in_array($status, ['queued', 'pending', 'sent'], true)) {
            return ['success' => true, 'message_id' => $result[0]['message_id'] ?? null];
        }
        return ['success' => false, 'error' => 'Message failed: ' . ($result[0]['message'] ?? $result[0]['status'])];
    }

    // The old code treated HTTP 403 and unreadable replies as "success". That hid real failures
    // (wrong API key, no credits) and showed them as delivered, so they are now reported as failures.
    return ['success' => false, 'error' => $httpCode === 403
        ? 'Semaphore rejected the request (HTTP 403). Check the API key and credit balance.'
        : "Unexpected response from Semaphore (HTTP $httpCode)"];
}

function testSemaphoreConnection() {
    if (!defined('SEMAPHORE_API_KEY') || empty(SEMAPHORE_API_KEY)) {
        return ['connected' => false, 'error' => 'API key not configured'];
    }
    [$httpCode, $response, $curlError] = semaphoreRequest(SEMAPHORE_ACCOUNT_URL . '?' . http_build_query(['apikey' => SEMAPHORE_API_KEY]), null, 10);
    if ($curlError) {
        return ['connected' => false, 'error' => "cURL Error: $curlError"];
    }
    $result = json_decode($response, true);
    if ($httpCode == 200 && isset($result['account_id'])) {
        return [
            'connected'    => true,
            'account_name' => $result['account_name'] ?? 'N/A',
            'balance'      => $result['credit_balance'] ?? 0,
            'status'       => $result['status'] ?? 'active',
        ];
    }
    return ['connected' => false, 'http_code' => $httpCode, 'error' => $result['error'] ?? 'Unknown error'];
}

function getSemaphoreBalance() {
    if (!defined('SEMAPHORE_API_KEY') || empty(SEMAPHORE_API_KEY)) {
        return ['success' => false, 'error' => 'API key not configured'];
    }
    [$httpCode, $response] = semaphoreRequest(SEMAPHORE_ACCOUNT_URL . '?' . http_build_query(['apikey' => SEMAPHORE_API_KEY]), null, 10);
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if ($result && isset($result['credit_balance'])) {
            return ['success' => true, 'balance' => $result['credit_balance']];
        }
    }
    return ['success' => false, 'error' => 'Unable to fetch balance'];
}