<?php
require_once '../config/constants.php';
require_once '../includes/semaphore.php';

echo "<h1>Semaphore SMS Test</h1>";

// Check API key
if (!defined('SEMAPHORE_API_KEY') || empty(SEMAPHORE_API_KEY)) {
    echo "<p style='color:red'>❌ API key not configured in config/constants.php</p>";
    exit;
}

echo "<p>✅ API Key: " . substr(SEMAPHORE_API_KEY, 0, 10) . "...</p>";

// Test connection (account info)
echo "<h2>Testing API Connection...</h2>";
$test = testSemaphoreConnection();

if ($test['connected']) {
    echo "<p style='color:green'>✅ API connection successful!</p>";
    echo "<p>Account: " . ($test['account_name'] ?? 'N/A') . "</p>";
    echo "<p>Balance: " . ($test['balance'] ?? '0') . " credits</p>";
    echo "<p>Status: " . ($test['status'] ?? 'N/A') . "</p>";
} else {
    echo "<p style='color:orange'>⚠️ API connection test returned HTTP " . ($test['http_code'] ?? 'N/A') . "</p>";
    echo "<p>Error: " . ($test['error'] ?? 'Unknown') . "</p>";
    echo "<p>Note: The send function may still work even if this test fails.</p>";
}

// Send test SMS
echo "<h2>Send Test SMS</h2>";
echo "<form method='post'>";
echo "<label>Phone Number (09xxxxxxxxx):</label><br>";
echo "<input type='text' name='phone' placeholder='09123456789' value='09850871125' required><br><br>";
echo "<label>Message:</label><br>";
echo "<textarea name='message' rows='3' style='width:100%' required>Hello from USAT Alumni System! This is a test message.</textarea><br><br>";
echo "<button type='submit' name='send'>Send SMS</button>";
echo "</form>";

if (isset($_POST['send'])) {
    $phone = $_POST['phone'];
    $message = $_POST['message'];
    
    echo "<h3>Result:</h3>";
    $result = sendSMSViaSemaphore($phone, $message);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color:green'>✅ SMS sent successfully to $phone!</p>";
    } else {
        echo "<p style='color:red'>❌ SMS failed: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
}

// Check balance
echo "<h2>Account Balance</h2>";
$balance = getSemaphoreBalance();
if ($balance['success']) {
    echo "<p style='color:green'>💰 Balance: " . $balance['balance'] . " credits</p>";
} else {
    echo "<p style='color:orange'>⚠️ " . ($balance['error'] ?? 'Unable to fetch balance') . "</p>";
}
?>