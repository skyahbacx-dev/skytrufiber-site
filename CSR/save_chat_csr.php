<?php
session_start();
include "../db_connect.php";

date_default_timezone_set("Asia/Manila");

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "missing client"]);
    exit;
}

// BACKBLAZE CONFIG
$bucketName = "ahba-chat-media";
$bucketURL  = "https://s3.us-east-005.backblazeb2.com/ahba-chat-media";
$keyID      = "005a548887f9c4f0000000002";
$appKey     = "K005fOYaprINPto/Qdm9wex0w4v/L2k";

require "../vendor/autoload.php";

use Aws\S3\S3Client;

$s3 = new S3Client([
    "endpoint" => "https://s3.us-east-005.backblazeb2.com",
    "region"   => "us-east-005",
    "version"  => "latest",
    "credentials" => [
        "key"    => $keyID,
        "secret" => $appKey
    ]
]);

$media_path = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {
    $name0 = $_FILES["files"]["name"][0];
    $tmp0  = $_FILES["files"]["tmp_name"][0];
    $ext   = strtolower(pathinfo($name0, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
        $folder = "chat_images/";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
        $folder = "chat_videos/";
    }

    if ($media_type) {
        $newName  = time() . "_" . rand(1000,9999) . "." . $ext;
        $keyName  = $folder . $newName;

        $s3->putObject([
            "Bucket" => $bucketName,
            "Key"    => $keyName,
            "SourceFile" => $tmp0,
            "ACL"    => "public-read",
        ]);

        $media_path = $keyName;   // Stored minimal path
    }
}

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
?>
