<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';

$email = trim($_POST['email'] ?? '');

if (!$email) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your email.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    exit;
}

/* ============================================================
   CHECK IF EMAIL EXISTS IN USERS TABLE
============================================================ */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'Email not found in our records.'
    ]);
    exit;
}

/* ============================================================
   SEND REQUEST TO GITHUB ACTION CRON SCRIPT
============================================================ */
$token = 'AE92JF83HF82HSLA29FD';
$apiUrl = "https://ahbadevt.com/cron/send_account_email.php?token=$token&email=" . urlencode($email);

$response = @file_get_contents($apiUrl);

/* ============================================================
   HANDLE RESPONSE
============================================================ */
if ($response && strpos(strtolower($response), 'success') !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Email sent! Please check your inbox.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to send email. Please try again later.'
    ]);
}
