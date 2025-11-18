<?php
session_start();
include "../db_connect.php";

use Aws\S3\S3Client;
require '../vendor/autoload.php';

header("Content-Type: application/json");

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? '');
$client_id    = intval($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client"]);
    exit;
}

# Backblaze Credentials
$bucketName = "ahba-chat-media";
$region     = "s3.us-east-005";
$folder     = "CSR_CHAT";

$s3 = new S3Client([
    "version" => "latest",
    "region"  => $region,
    "endpoint" => "https://$region.backblazeb2.com",
    "use_path_style_endpoint" => true,
    "credentials" => [
        "key"    => "005a548887f9c4f0000000002",
        "secret" => "K005fOYaprINPto/Qdm9wex0w4v/L2k"
    ]
]);

$uploadedMedia = [];

if (!empty($_FILES["files"]["name"][0])) {
    foreach ($_FILES["files"]["name"] as $index => $name) {
        $tmp = $_FILES["files"]["tmp_name"][$index];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $mediaType = (in_array($ext, ["jpg","jpeg","png","gif","webp"])) ? "image" : "video";

        $key = "$folder/$client_id/" . ($mediaType === "image" ? "images/" : "videos/") . time() . "_".rand(1000,9999).".".$ext;

        try {
            $s3->putObject([
                'Bucket' => $bucketName,
                'Key'    => $key,
                'SourceFile' => $tmp,
                'ACL'    => 'public-read'
            ]);

            $publicURL = "https://$region.backblazeb2.com/$bucketName/$key";

            $uploadedMedia[] = [
                "url" => $publicURL,
                "type" => $mediaType
            ];

        } catch(Exception $e) {
            echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
            exit;
        }
    }
}

# Insert message to chat
$stmt = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
                        VALUES (:cid, :type, :msg, :csr, NOW())");
$stmt->execute([
    ":cid" => $client_id,
    ":type" => $sender_type,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chat_id = $conn->lastInsertId();

# Insert media
foreach ($uploadedMedia as $m) {
    $stmt2 = $conn->prepare("INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
                             VALUES (:cid, :path, :type, NOW())");
    $stmt2->execute([
        ":cid"  => $chat_id,
        ":path" => $m["url"],
        ":type" => $m["type"]
    ]);
}

echo json_encode(["status" => "ok"]);
