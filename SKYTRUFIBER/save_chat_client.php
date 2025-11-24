<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$sender_type = $_POST["sender_type"] ?? 'client';
$message     = trim($_POST["message"] ?? '');
$username    = $_POST["username"] ?? '';

if (!$username) {
    echo json_encode(["status"=>"error","msg"=>"no username"]);
    exit;
}

// Lookup client by name
$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name"=>$username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"client not found"]);
    exit;
}

include "../b2_upload.php";

$media_path = null;
$media_type = null;

if (!empty($_FILES['file']['tmp_name'])) {

    $fileTmp  = $_FILES['file']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $fileName = time() . "_" . rand(1000, 9999) . "." . $ext;

    $url = b2_upload($fileTmp, $fileName);

    if ($url) {
        $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp'])
            ? 'image'
            : 'video';
        $media_path = $url;
    }
}

// Insert base message
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at)
    VALUES (:cid, :stype, :msg, NOW())
");
$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message
]);

$chat_id = $conn->lastInsertId();

// If media exists, save to chat_media table
if ($media_path) {
    $stmtMedia = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
        VALUES (:chat, :path, :type, NOW())
    ");
    $stmtMedia->execute([
        ":chat" => $chat_id,
        ":path" => $media_path,
        ":type" => $media_type
    ]);
}

echo json_encode(["status"=>"ok"]);
exit;
?>
