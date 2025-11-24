<?php
session_start();
include "../db_connect.php";
include "b2_upload.php";  // Backblaze uploader function
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

// =========================================
// VALIDATE SESSION & POST DATA
// =========================================
$csr_user     = $_SESSION["csr_user"] ?? null;
$csr_fullname = $_SESSION["csr_fullname"] ?? "CSR";
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid session or client"]);
    exit;
}

// =========================================
// INSERT BASE CHAT MESSAGE FIRST
// =========================================
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chat_id = $conn->lastInsertId(); // Needed for chat_media linking

// =========================================
// HANDLE FILE UPLOADS TO BACKBLAZE B2
// =========================================
if (!empty($_FILES["files"]["name"][0])) {

    foreach ($_FILES["files"]["tmp_name"] as $i => $tmpFile) {
        if (!$tmpFile) continue;

        $originalName = $_FILES["files"]["name"][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

        // Upload to Backblaze B2
        $url = b2_upload($tmpFile, $newName);

        if ($url) {
            $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp'])
                ? "image"
                : (in_array($ext, ['mp4','mov','avi','mkv','webm']) ? "video" : null);

            // Insert into chat_media table
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

// =========================================
// RESPONSE
// =========================================
echo json_encode(["status" => "ok"]);
exit;
?>
