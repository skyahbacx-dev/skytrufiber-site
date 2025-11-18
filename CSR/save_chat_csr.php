<?php
session_start();
require "../db_connect.php";

use Aws\S3\S3Client;
require "../vendor/autoload.php"; // Ensure Composer AWS SDK installed

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? '');
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

// AWS/B2 config
$bucket = "ahba-chat-media";
$endpoint = "https://s3.us-east-005.backblazeb2.com";
$keyId = "005a548887f9c4f0000000002"; 
$appKey = "K005fOYaprINPto/Qdm9wex0w4v/L2k";

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-005',
    'endpoint' => $endpoint,
    'credentials' => [
        'key' => $keyId,
        'secret' => $appKey
    ]
]);

$media_url = null;
$media_type = null;

if (!empty($_FILES['files']['name'][0])) {
    $fileName = time() . "_" . basename($_FILES["files"]["name"][0]);
    $fileTmp  = $_FILES["files"]["tmp_name"][0];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($fileType, ["jpg", "jpeg", "png", "gif", "webp"])) {
        $media_type = "image";
    } elseif (in_array($fileType, ["mp4", "mov", "avi", "mkv", "webm"])) {
        $media_type = "video";
    }

    $s3->putObject([
        "Bucket" => $bucket,
        "Key"    => "chat-media/" . $fileName,
        "SourceFile" => $fileTmp,
        "ACL"    => "public-read"
    ]);

    $media_url = "$endpoint/$bucket/chat-media/$fileName";
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
    VALUES (:cid, :stype, :msg, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":stype" => $sender_type,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chatId = $conn->lastInsertId();

// If media uploaded — link in chat_media table
if ($media_url) {
    $stmt2 = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
        VALUES (:id, :mp, :mt, NOW())
    ");
    $stmt2->execute([
        ":id" => $chatId,
        ":mp" => $media_url,
        ":mt" => $media_type
    ]);
}

echo json_encode(["status" => "ok"]);
