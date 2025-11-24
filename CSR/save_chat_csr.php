<?php
session_start();
include "../db_connect.php";
include "b2_upload.php"; 
header("Content-Type: application/json");

$csr_user     = $_SESSION["csr_user"] ?? null;
$csr_fullname = $_SESSION["csr_fullname"] ?? "CSR";
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status"=>"error", "msg"=>"Invalid session or client"]);
    exit;
}

// Insert chat record first
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chat_id = $conn->lastInsertId();  // important

// Handle media upload(s)
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['tmp_name'] as $index => $tmpFile) {

        if (!$tmpFile) continue;

        $origName = $_FILES['files']['name'][$index];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

        $url = b2_upload($tmpFile, $newName);

        if ($url) {
            $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image' : 'video';

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

echo json_encode(["status"=>"ok"]);
