<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$email = 'whotf1srandomguy@gmail.com';
$password = 'admin123'; //can change
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    echo "User with email $email already exists.<br>";
} else {
    // Insert new admin
    $insert = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
    if ($insert->execute([$email, $hashed])) {
        echo "Admin user created successfully.<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
        echo "Hash: $hashed<br>";
    } else {
        echo "Failed to create admin user.<br>";
    }
}
?>