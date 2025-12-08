<?php
session_start();
include '../db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$response = ['success'=>false, 'message'=>''];

if(!$email){
    $response['message'] = "Please enter your email.";
    echo json_encode($response); exit;
}
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
    $response['message'] = "Invalid email format.";
    echo json_encode($response); exit;
}

try{
    $stmt = $conn->prepare("SELECT full_name, account_number FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email'=>$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        $response['message'] = "No user found with that email.";
        echo json_encode($response); exit;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'skytrufiberbilling@gmail.com';
    $mail->Password = 'hmmt suww lpyt oheo';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('skytrufiberbilling@gmail.com', 'SkyTruFiber Support');
    $mail->addAddress($email, $user['full_name']);
    $mail->isHTML(true);
    $mail->Subject = "Your SkyTruFiber Account Number";
    $mail->Body = "
        Hello <b>{$user['full_name']}</b>,<br><br>
        Your account number is: <strong>{$user['account_number']}</strong><br><br>
        Regards,<br>SkyTruFiber Support Team
    ";
    $mail->send();
    $response['success'] = true;
    $response['message'] = "Email sent successfully!";

} catch(Exception $e){
    $response['message'] = "Failed to send email: ".$mail->ErrorInfo;
} catch(PDOException $e){
    $response['message'] = "Database error: ".$e->getMessage();
}

echo json_encode($response);
