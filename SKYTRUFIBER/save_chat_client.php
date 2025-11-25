<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";

header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$sender_type = "client";
$username    = trim($_POST["username"] ?? "");
$message     = trim($_POST["message"] ?? "");

if (!$username) {
    echo json_encode(["status"=>"error","msg"=>"No username"]);
    exit;
}

// GET CLIENT ID
$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"Client not found"]);
    exit;
}

// INSERT BASE MESSAGE
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, created_at)
    VALUES (:cid, :stype, :msg, TRUE, NOW())
");
$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message
]);

$chat_id = $conn->lastInsertId();

// HANDLE MEDIA UPLOAD
if (!empty($_FILES["file"]["tmp_name"])) {

    $fileTmp  = $_FILES["file"]["tmp_name"];
    $fileName = time() . "_" . $_FILES["file"]["name"];
    $url      = b2_upload($fileTmp, $fileName);

    if ($url) {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? "image" :
                      (in_array($ext, ['mp4','mov','avi','mkv','webm']) ? "video" : null);

        $stmt = $conn->prepare("
            INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
            VALUES (:chat, :path, :type, NOW())
        ");
        $stmt->execute([
            ":chat" => $chat_id,
            ":path" => $url,
            ":type" => $media_type
        ]);
    }
}

echo json_encode(["status" => "ok"]);
exit;
?>
