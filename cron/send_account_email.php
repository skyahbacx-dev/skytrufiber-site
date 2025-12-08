<?php
require __DIR__ . '/../vendor/autoload.php'; // Composer autoload
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Read environment variables from GitHub Actions
$token = $_ENV['TOKEN'] ?? '';
$email = $_ENV['EMAIL_TO'] ?? '';
$smtpUser = $_ENV['SMTP_USERNAME'] ?? '';
$smtpPass = $_ENV['SMTP_PASSWORD'] ?? '';

// Security token check
if ($token !== 'AE92JF83HF82HSLA29FD') {
    http_response_code(403);
    exit('Unauthorized: invalid token');
}

if (!$email) {
    http_response_code(400);
    exit('No email provided');
}

// Fetch user from database
$stmt = $conn->prepare("SELECT full_name, account_number FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('No user found');
}

// Send email via PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($smtpUser, 'SkyTruFiber Support');
    $mail->addAddress($user['email'], $user['full_name']);

    $mail->isHTML(true);
    $mail->Subject = 'Your SkyTruFiber Account Number';
    $mail->Body = "
        <p>Hello <b>{$user['full_name']}</b>,</p>
        <p>Your account number is: <strong>{$user['account_number']}</strong></p>
        <p>This serves as your login password.</p>
        <p>Regards,<br>SkyTruFiber Support Team</p>
    ";

    $mail->send();
    echo "Email sent successfully!";
} catch (Exception $e) {
    http_response_code(500);
    echo "Mailer Error: " . $mail->ErrorInfo;
}
