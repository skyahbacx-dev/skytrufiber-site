<?php
session_start();
include "../db_connect.php";
include "b2_upload.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$csr_user  = $_SESSION["csr_user"] ?? null;
$message   = trim($_POST["message"] ?? "");
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid session or client"]);
    exit;
}

/* 1) INSERT BASE CHAT ROW */
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at)
    VALUES (:cid, 'csr', :msg, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message
]);

$chat_id = $conn->lastInsertId();

/* 2) FILE UPLOADS → chat_media table */
if (!empty($_FILES["media"]["name"][0])) {

    foreach ($_FILES["media"]["tmp_name"] as $i => $tmpFile) {
        if (!$tmpFile) continue;

        $originalName = $_FILES["media"]["name"][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

        $url = b2_upload($tmpFile, $newName);

        if ($url) {
            $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp'])
                ? "image"
                : (in_array($ext, ['mp4','mov','avi','mkv','webm']) ? "video" : null);

            $stmtMedia = $conn->prepare("
                INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
                VALUES (:chat, :path, :type, NOW())
            ");
            $stmtMedia->execute([
                ":chat" => $chat_id,
                ":path" => $url,
                ":type" => $media_type
            ]);
        }
    }
}

/* 3) mark delivered to CSR (client will receive after poll) */
$conn->prepare("UPDATE chat SET delivered = true WHERE id = :id")
    ->execute([":id" => $chat_id]);

echo json_encode(["status" => "ok"]);
exit;
