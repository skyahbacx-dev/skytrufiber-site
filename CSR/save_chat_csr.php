<?php
session_start();
include "../db_connect.php";
include "b2_upload.php";

header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

// SESSION USER
$csr_user     = $_SESSION["csr_user"] ?? null;
$csr_fullname = $_SESSION["csr_fullname"] ?? "CSR";

// POST DATA
$message   = trim($_POST["message"] ?? "");
$client_id = intval($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid session or client"]);
    exit;
}

// INSERT TEXT MESSAGE
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, csr_fullname, seen, created_at)
    VALUES (:cid, 'csr', :msg, :csr, false, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chat_id = $conn->lastInsertId();

// HANDLE MEDIA UPLOADS
if (!empty($_FILES["files"]["name"][0])) {
    foreach ($_FILES["files"]["tmp_name"] as $i => $tmpFile) {

        if (!$tmpFile) continue;

        $originalName = $_FILES["files"]["name"][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;

        // Upload to Backblaze B2
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

// RESPONSE FOR FRONTEND DELIVERY STATUS
echo json_encode([
    "status" => "ok",
    "chat_id" => $chat_id,
    "client_id" => $client_id
]);
exit;
?>
