<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? '');
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "missing client"]);
    exit;
}

$media_path = null;
$media_type = null;

// SINGLE or MULTIPLE upload
if (!empty($_FILES['files']['name'][0])) {

    $orig = $_FILES['files']['name'][0];
    $tmp  = $_FILES['files']['tmp_name'][0];
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $media_type = "image";
    } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
        $media_type = "video";
    }

    if ($media_type) {
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;

        // Upload to Backblaze
        $uploadedUrl = b2_upload($tmp, $newName);

        if ($uploadedUrl) {
            $media_path = $uploadedUrl;  // full url returned
        }
    }
}

// INSERT chat
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, :csr, NOW())
");

$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":mp"    => $media_path,
    ":mt"    => $media_type,
    ":csr"   => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
