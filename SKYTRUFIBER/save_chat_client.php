<?php
session_start();
include '../db_connect.php';
include "../b2_upload.php";      // uploader
header('Content-Type: application/json');

$sender_type = $_POST["sender_type"] ?? 'client';
$message     = trim($_POST["message"] ?? '');
$username    = trim($_POST["username"] ?? '');

if (!$username) {
    echo json_encode(["status"=>"error","msg"=>"no username"]);
    exit;
}

// GET CLIENT ID
$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name"=>$username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"client not found"]);
    exit;
}

// INSERT BASE CHAT MESSAGE (no media yet)
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, seen, created_at)
    VALUES (:cid, :stype, :msg, false, NOW())
");
$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
]);

$chat_id = $conn->lastInsertId();

// HANDLE MEDIA FILE (optional)
if (!empty($_FILES['file']['tmp_name'])) {
    $fileTmp  = $_FILES['file']['tmp_name'];
    $original = $_FILES['file']['name'];
    $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $newName  = time() . "_" . rand(1000,9999) . "." . $ext;

    $url      = b2_upload($fileTmp, $newName);

    if ($url) {
        $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp'])
                      ? 'image'
                      : 'video';

        $stmt2 = $conn->prepare("
            INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
            VALUES (:chat, :path, :type, NOW())
        ");
        $stmt2->execute([
            ":chat" => $chat_id,
            ":path" => $url,
            ":type" => $media_type
        ]);
    }
}

echo json_encode([
    "status" => "ok",
    "chat_id" => $chat_id
]);
exit;
?>
