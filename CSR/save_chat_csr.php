<?php
// ======================================================================
// save_chat_csr.php — FINAL FULL FILE (Backblaze Support + Preview + DB)
// ======================================================================

session_start();
include "../db_connect.php";
require __DIR__ . "b2_upload.php";  // uploader helper
header("Content-Type: application/json");

$message      = trim($_POST["message"] ?? "");
$client_id    = intval($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? "";
$sender_type  = "csr";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client id"]);
    exit;
}

// =============================
// MEDIA UPLOAD TO BACKBLAZE B2
// =============================

$media_url  = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {

    $fileName = $_FILES["files"]["name"][0];
    $tmpName  = $_FILES["files"]["tmp_name"][0];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4", "mov", "avi", "webm", "mkv"])) {
        $media_type = "video";
    }

    if ($media_type) {
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

        // upload to Backblaze (returns URL or null)
        $uploadedURL = b2_upload($tmpName, "chat_media/" . $newName);

        if ($uploadedURL) {
            $media_url = $uploadedURL;
        } else {
            echo json_encode(["status" => "error", "msg" => "Upload failed"]);
            exit;
        }
    }
}

// =============================
// SAVE to database
// =============================
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_url, media_type, csr_fullname, created_at, seen)
    VALUES (:cid, :stype, :msg, :murl, :mtype, :csr, NOW(), 0)
");

$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":murl"  => $media_url,
    ":mtype" => $media_type,
    ":csr"   => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
exit;
?>
