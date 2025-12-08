<?php
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Security token to prevent public access
if ($_GET['token'] !== 'YOUR_SECRET_TOKEN') {
    http_response_code(403);
    exit('Unauthorized');
}

$email = $_GET['email'] ?? '';
if (!$email) exit('No email provided');

$stmt = $conn->prepare("SELECT full_name, account_number FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) exit('No user found');

// PHPMailer code
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'skytrufiberbilling@gmail.com';
    $mail->Password = 'hmmt suww lpyt oheo';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('skytrufiberbilling@gmail.com','SkyTruFiber Support');
    $mail->addAddress($user['email'],$user['full_name']);
    $mail->isHTML(true);
    $mail->Subject = "Your SkyTruFiber Account Number";
    $mail->Body = "Hello <b>{$user['full_name']}</b>,<br>Your account number is: <strong>{$user['account_number']}</strong><br>Regards,<br>SkyTruFiber Support Team";

    $mail->send();
    echo 'Email sent successfully';
} catch(Exception $e){
    echo 'Error: '.$mail->ErrorInfo;
}
