<?php
session_start();
include "../db_connect.php";
require __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;

header("Content-Type: application/json");

$message      = trim($_POST["message"] ?? "");
$client_id    = intval($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id || !$csr_fullname) {
    echo json_encode(["status" => "error", "msg" => "Missing client or sender"]);
    exit;
}

// BACKBLAZE ACCESS
$bucket     = getenv("B2_BUCKET");
$endpoint   = getenv("B2_ENDPOINT");  // e.g. https://s3.us-east-005.backblazeb2.com
$keyId      = getenv("B2_KEY_ID");
$appKey     = getenv("B2_APP_KEY");

$s3 = new S3Client([
    "version" => "latest",
    "region" => "us-east-005",
    "endpoint" => $endpoint,
    "use_path_style_endpoint" => true,
    "credentials" => [
        "key"    => $keyId,
        "secret" => $appKey
    ]
]);

$media_url = null;
$media_type = null;

// FILE UPLOAD
if (!empty($_FILES["files"]["name"][0])) {

    $name  = $_FILES["files"]["name"][0];
    $tmp   = $_FILES["files"]["tmp_name"][0];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"]))  $media_type = "image";
    if (in_array($ext, ["mp4","mov","avi","mkv","webm"]))   $media_type = "video";

    $fileKey = "chat_media/" . time() . "_" . rand(1000,9999) . "." . $ext;

    try {
        $upload = $s3->putObject([
            "Bucket" => $bucket,
            "Key" => $fileKey,
            "Body" => fopen($tmp, "rb"),
            "ACL" => "public-read",
            "ContentType" => mime_content_type($tmp)
        ]);

        $media_url = $upload["ObjectURL"];

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
        exit;
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_url, media_type, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :url, :mt, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":url" => $media_url,
    ":mt"  => $media_type,
    ":csr" => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
?>
