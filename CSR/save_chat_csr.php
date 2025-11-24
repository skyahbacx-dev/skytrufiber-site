<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";

header('Content-Type: application/json');

$message      = trim($_POST["message"] ?? '');
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "missing client"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES["file"]["name"])) {
    $tmp  = $_FILES["file"]["tmp_name"];
    $name = $_FILES["file"]["name"];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) $media_type = "image";
    if (in_array($ext, ["mp4","avi","mov","mkv","webm"]))  $media_type = "video";

    if ($media_type) {
        $timestamp = time();
        $rand = rand(1000,9999);
        $fileName = "chat/client_{$client_id}/csr_{$csr_fullname}/{$timestamp}_{$rand}.{$ext}";

        $uploadedUrl = b2_upload($tmp, $fileName);
        if ($uploadedUrl) $media_path = $uploadedUrl;
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :path, :type, :csr, NOW())
");

$stmt->execute([
    ":cid"  => $client_id,
    ":msg"  => $message,
    ":path" => $media_path,
    ":type" => $media_type,
    ":csr"  => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
