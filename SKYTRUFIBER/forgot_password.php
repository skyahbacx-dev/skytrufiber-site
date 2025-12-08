<?php
header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
if (!$email) {
    echo json_encode(['success'=>false,'message'=>'Please enter your email.']);
    exit;
}

$token = 'AE92JF83HF82HSLA29FD';
$url = "https://ahbadevt.com/cron/send_account_email.php?token=$token&email=".urlencode($email);

$response = file_get_contents($url);
if($response && strpos($response,'success')!==false){
    echo json_encode(['success'=>true,'message'=>'Email sent successfully!']);
} else {
    echo json_encode(['success'=>false,'message'=>$response ?: 'Error sending email.']);
}
