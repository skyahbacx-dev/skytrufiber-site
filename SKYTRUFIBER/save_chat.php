<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$sender_type  = $_POST['sender_type'] ?? 'client';
$message      = trim($_POST['message'] ?? '');
$username     = $_POST['username'] ?? '';

$media_path = null;
$media_type = null;

/* MEDIA UPLOAD */
if (!empty($_FILES['file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if(in_array($ext,['jpg','jpeg','png','gif','webp'])){ $media_type='image'; $folder="../uploads/chat_images/"; }
    elseif(in_array($ext,['mp4','mov','avi','mkv','webm'])){ $media_type='video'; $folder="../uploads/chat_videos/"; }

    if($media_type){
        $newName=time()."_".rand(1000,9999).".".$ext;
        $path=$folder.$newName;
        move_uploaded_file($_FILES['file']['tmp_name'],$path);
        $media_path=substr($path,3);
    }
}

/* CLIENT HANDLER */
if ($sender_type==='client') {
    $stmt=$conn->prepare("SELECT id FROM clients WHERE name=:n LIMIT 1");
    $stmt->execute([':n'=>$username]);
    $row=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$row){
        $ins=$conn->prepare("INSERT INTO clients (name,created_at) VALUES (:n,NOW()) RETURNING id");
        $ins->execute([':n'=>$username]);
        $client_id=$ins->fetchColumn();
    } else $client_id=$row['id'];

    $stmt2=$conn->prepare("
      INSERT INTO chat (client_id,sender_type,message,media_path,media_type,created_at)
      VALUES(:cid,'client',:m,:mp,:mt,NOW())
    ");
    $stmt2->execute([':cid'=>$client_id,':m'=>$message,':mp'=>$media_path,':mt'=>$media_type]);

    echo json_encode(['status'=>'ok','client_id'=>$client_id]);
    exit;
}

/* CSR MESSAGE HANDLER */
if ($sender_type==='csr' && isset($_POST['client_id'])) {
    $client_id=(int)$_POST['client_id'];
    $stmt=$conn->prepare("
      INSERT INTO chat(client_id,sender_type,message,media_path,media_type,created_at)
      VALUES(:cid,'csr',:m,:mp,:mt,NOW())
    ");
    $stmt->execute([':cid'=>$client_id,':m'=>$message,':mp'=>$media_path,':mt'=>$media_type]);
    echo json_encode(['status'=>'ok']);
    exit;
}

echo json_encode(['status'=>'error']);
?>
