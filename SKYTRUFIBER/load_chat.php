<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$client_id = 0;

if(isset($_GET['client_id'])){
    $client_id = (int)$_GET['client_id'];
}
elseif(isset($_GET['client'])){
    $stmt = $conn->prepare("SELECT id FROM clients WHERE name=:n LIMIT 1");
    $stmt->execute([':n'=>$_GET['client']]);
    $client_id=$stmt->fetchColumn();
}

if(!$client_id){
    echo json_encode([]);
    exit;
}

$stmt=$conn->prepare("
    SELECT ch.*, c.name AS client_name
    FROM chat ch
    JOIN clients c ON ch.client_id=c.id
    WHERE ch.client_id=:cid
    ORDER BY ch.created_at ASC
");
$stmt->execute([':cid'=>$client_id]);

$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
$messages=[];
foreach($rows as $row){
    $messages[]=[
       'client_id'   =>$client_id,
       'message'     =>$row['message'],
       'sender_type' =>$row['sender_type'],
       'media_path'  =>$row['media_path'],
       'media_type'  =>$row['media_type'],
       'created_at'  =>date("M d g:i A", strtotime($row['created_at']." +8 hours")),
       'client_name' =>$row['client_name']
    ];
}
echo json_encode($messages);
?>
