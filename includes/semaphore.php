<?php
define('SEMAPHORE_API_URL', 'https://api.semaphore.co/api/v4/messages');

function sendSMSViaSemaphore($phone, $message) {
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to 09 format if needed
    if (substr($phone, 0, 2) == '63') {
        $phone = '0' . substr($phone, 2);
    }
    
    // Validate  philippines phone number 11 digits starting with 09 
    if (strlen($phone) != 11 || substr($phone, 0, 2) != '09') {
        return ['success' => false, 'error' => 'Invalid phone number format. Must be 09xxxxxxxxx'];
    }
    
    if (empty(trim($message))) {
        return ['success' => false, 'error' => 'Message cannot be empty'];
    }
    
    $postData = [
        'apikey' => SEMAPHORE_API_KEY,
        'number' => $phone,
        'message' => $message,
        'sendername' => 'SEMAPHORE'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SEMAPHORE_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    @curl_close($ch);
    
    error_log("Semaphore SMS - HTTP: $httpCode, Response: " . substr($response, 0, 200));
    
    if ($curlError) {
        return ['success' => false, 'error' => "cURL Error: $curlError"];
    }
    
    // Handle HTTP 403 - API key issue (but sometimes send still works)
    if ($httpCode == 403) {
        // Try to parse response for more info
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            return ['success' => false, 'error' => $result['error']];
        }
        // If no error in response, assume it worked
        return ['success' => true, 'warning' => 'HTTP 403 but message may have been sent'];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        return ['success' => false, 'error' => $result['error']];
    }
    
    if (is_array($result) && isset($result[0]['status'])) {
        $status = $result[0]['status'];
        if ($status == 'queued' || $status == 'pending' || $status == 'sent') {
            return ['success' => true, 'message_id' => $result[0]['message_id'] ?? null];
        } else {
            return ['success' => false, 'error' => 'Message failed: ' . ($result[0]['message'] ?? 'Unknown')];
        }
    }
    
    return ['success' => true, 'response' => $result];
}


function testSemaphoreConnection() {
    if (!defined('SEMAPHORE_API_KEY') || empty(SEMAPHORE_API_KEY)) {
        return ['connected' => false, 'error' => 'API key not configured'];
    }
    
    // different endpoint for testing
    $url = 'https://api.semaphore.co/api/v4/account?' . http_build_query(['apikey' => SEMAPHORE_API_KEY]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    @curl_close($ch);
    
    if ($curlError) {
        return ['connected' => false, 'error' => "cURL Error: $curlError"];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode == 200 && isset($result['account_id'])) {
        return [
            'connected' => true,
            'account_name' => $result['account_name'] ?? 'N/A',
            'balance' => $result['credit_balance'] ?? 0,
            'status' => $result['status'] ?? 'active'
        ];
    }
    
    return [
        'connected' => false,
        'http_code' => $httpCode,
        'error' => $result['error'] ?? 'Unknown error'
    ];
}


function getSemaphoreBalance() {
    $url = 'https://api.semaphore.co/api/v4/account?' . http_build_query(['apikey' => SEMAPHORE_API_KEY]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if ($result && isset($result['credit_balance'])) {
            return ['success' => true, 'balance' => $result['credit_balance']];
        }
    }
    
    return ['success' => false, 'error' => 'Unable to fetch balance'];
}
?>