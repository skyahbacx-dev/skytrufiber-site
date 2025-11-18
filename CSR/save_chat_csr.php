<?php
session_start();
include "../db_connect.php";
require "../vendor/autoload.php"; // REQUIRED for Backblaze SDK

use BackblazeB2\Client;

header("Content-Type: application/json");

$client_id    = $_POST["client_id"] ?? 0;
$message      = trim($_POST["message"] ?? "");
$csr_fullname = $_POST["csr_fullname"] ?? "";
$sender_type  = "csr";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client"]);
    exit;
}

// initialize B2 client
$b2 = new Client(
    "005a548887f9c4f0000000002",  // Key ID
    "K005fOYaprINPto/Qdm9wex0w4v/L2k"  // Application Key
);

$bucketName = "ahba-chat-media";

// upload file if exists
$media_url = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {
    $filename = time() . "_" . rand(10000, 99999) . "_" . $_FILES["files"]["name"][0];
    $tmp = $_FILES["files"]["tmp_name"][0];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
    }

    $fileContent = file_get_contents($tmp);

    $response = $b2->upload([
        'BucketName' => $bucketName,
        'FileName'   => $filename,
        'Body'       => $fileContent,
        'ContentType' => $_FILES["files"]["type"][0]
    ]);

    $media_url = $response->getUrl();
}

// insert chat
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_url, media_type, csr_fullname, created_at)
    VALUES (:cid, :stype, :msg, :url, :mt, :csr, NOW())
");

$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":url"   => $media_url,
    ":mt"    => $media_type,
    ":csr"   => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
exit;
