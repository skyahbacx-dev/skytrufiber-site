<?php
session_start();
include "../db_connect.php";
include "b2_upload.php";   // must exist and return uploaded B2 URL

header("Content-Type: application/json");

$csr_user     = $_SESSION["csr_user"] ?? null;
$csr_fullname = $_SESSION["csr_fullname"] ?? "CSR";
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid session or client"]);
    exit;
}

// ============================================
// 1) Insert the base chat row first
// ============================================
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chat_id = $conn->lastInsertId(); // IMPORTANT for linking media records

// ============================================
// 2) Process media files (if any)
// ============================================
if (!empty($_FILES['files']['name'][0])) {

    foreach ($_FILES['files']['tmp_name'] as $index => $tmpFile) {

        if (!$tmpFile) continue;

        $origName = $_FILES['files']['name'][$index];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

        // Upload to Backblaze B2
        $uploadedUrl = b2_upload($tmpFile, $newName);

        if ($uploadedUrl) {

            $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? "image" :
                          (in_array($ext, ['mp4','mov','avi','mkv','webm']) ? "video" : null);

            if ($media_type) {
                $stmt2 = $conn->prepare("
                    INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
                    VALUES (:chat, :path, :type, NOW())
                ");
                $stmt2->execute([
                    ":chat" => $chat_id,
                    ":path" => $uploadedUrl,
                    ":type" => $media_type
                ]);
            }
        }
    }
}

// ============================================
echo json_encode(["status" => "ok"]);
