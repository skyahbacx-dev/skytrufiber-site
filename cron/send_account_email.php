<?php
include '../db_connect.php';
require __DIR__ . '/../vendor/autoload.php'; // Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Security token
if ($_GET['token'] ?? '' !== 'YOUR_SECRET_TOKEN') {
    http_response_code(403);
    exit('Unauthorized');
}

$email = $_GET['email'] ?? '';
if (!$email) exit('No email provided');

$stmt = $conn->prepare("SELECT full_name, account_number FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) exit('No user found');

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'skytrufiberbilling@gmail.com';
    $mail->Password = 'YOUR_APP_PASSWORD';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('skytrufiberbilling@gmail.com', 'SkyTruFiber Support');
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
    echo 'Email sent successfully!';
} catch (Exception $e) {
    echo 'Error: ' . $mail->ErrorInfo;
}
