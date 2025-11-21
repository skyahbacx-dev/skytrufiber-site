<?php
session_start();
include "../db_connect.php";
require __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;

$message      = trim($_POST["message"] ?? "");
$client_id    = intval($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client"]);
    exit;
}

$bucket     = getenv("B2_BUCKET");
$endpoint   = getenv("B2_ENDPOINT");
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

$media_url  = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {
    $fileName = $_FILES["files"]["name"][0];
    $tmpName  = $_FILES["files"]["tmp_name"][0];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"]))  $media_type = "image";
    if (in_array($ext, ["mp4","mov","avi","mkv","webm"]))   $media_type = "video";

    $key = "chat_media/" . time() . "_" . rand(1000,9999) . "." . $ext;

    try {
        $result = $s3->putObject([
            "Bucket" => $bucket,
            "Key"    => $key,
            "Body"   => fopen($tmpName, "rb"),
            "ACL"    => "public-read",
            "ContentType" => mime_content_type($tmpName)
        ]);
        $media_url = $result["ObjectURL"];
    } catch (Exception $e) {
        echo json_encode(["status"=>"error","msg"=>$e->getMessage()]);
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

echo json_encode(["status"=>"ok"]);
?>
