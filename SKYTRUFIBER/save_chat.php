<?php
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$sender_type = $_POST['sender_type'] ?? 'client';
$message     = trim($_POST['message'] ?? '');
$username    = $_POST['username'] ?? '';

if ($message === '') {
    echo json_encode(['status'=>'error','msg'=>'empty']);
    exit;
}

// Get or Create Client
$stmt = $conn->prepare("SELECT id FROM clients WHERE LOWER(name)=LOWER(:name) LIMIT 1");
$stmt->execute([':name'=>$username]);
$client_id = $stmt->fetchColumn();

if(!$client_id){
    $ins=$conn->prepare("INSERT INTO clients (name, created_at) VALUES (:n, NOW()) RETURNING id");
    $ins->execute([':n'=>$username]);
    $client_id = $ins->fetchColumn();
}

// Save chat
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at)
    VALUES (:cid, :stype, :msg, NOW())
");
$insert->execute([
    ':cid'=>$client_id,
    ':stype'=>$sender_type,
    ':msg'=>$message
]);

echo json_encode(['status'=>'ok']);
?>
