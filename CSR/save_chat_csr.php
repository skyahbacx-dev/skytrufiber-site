<?php
session_start();
include "../db_connect.php";
include "b2_upload.php";  // Backblaze upload function

header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

// SESSION VALIDATION
$csr_user     = $_SESSION["csr_user"] ?? null;
$csr_fullname = $_SESSION["csr_fullname"] ?? "CSR";
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid session or client"]);
    exit;
}

// INSERT BASE MESSAGE FIRST
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, csr_fullname, delivered, created_at)
    VALUES (:cid, 'csr', :msg, :csr, TRUE, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chat_id = $conn->lastInsertId();

// HANDLE MULTIPLE FILE UPLOADS
if (!empty($_FILES["media"]["name"][0])) {
    foreach ($_FILES["media"]["tmp_name"] as $i => $tmpFile) {
        if (!$tmpFile) continue;

        $originalName = $_FILES["media"]["name"][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $newFile = time() . "_" . rand(1000, 9999) . "." . $ext;

        // Upload to Backblaze
        $url = b2_upload($tmpFile, $newFile);

        if ($url) {
            $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? "image" :
                          (in_array($ext, ['mp4','mov','avi','mkv','webm']) ? "video" : null);

            $stmt = $conn->prepare("
                INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
                VALUES (:chat_id, :path, :type, NOW())
            ");

            $stmt->execute([
                ":chat_id" => $chat_id,
                ":path"    => $url,
                ":type"    => $media_type
            ]);
        }
    }
}

echo json_encode(["status" => "ok"]);
exit;
?>
