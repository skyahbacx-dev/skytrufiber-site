<?php
session_start();
require "../db_connect.php";

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? '');
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) exit("missing client");

// ===== BACKBLAZE B2 CONFIG =====
require '../vendor/autoload.php';

use Aws\S3\S3Client;

$bucketName = "ahba-chat-media";

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-005',
    'endpoint' => 'https://s3.us-east-005.backblazeb2.com',
    'credentials' => [
        'key'    => '005a548887f9c4f0000000002',
        'secret' => 'K005fOYaprINPto/Qdm9wex0w4v/L2k',
    ],
]);

$media_path = null;
$media_type = null;

// ===== MULTIPLE FILE SUPPORT =====
if (!empty($_FILES["files"]["name"][0])) {

    $fileName = $_FILES["files"]["name"][0];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $tmp = $_FILES["files"]["tmp_name"][0];

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
    }

    if ($media_type) {
        // === create dated directory ===
        $datePath = date("Y/m/d");
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $uploadPath = "$datePath/$newName"; // FILE KEY

        try {
            $s3->putObject([
                'Bucket' => $bucketName,
                'Key'    => $uploadPath,
                'SourceFile' => $tmp,
                'ACL' => 'public-read'
            ]);

            $media_path = "https://$bucketName.s3.us-east-005.backblazeb2.com/$uploadPath";

        } catch (Exception $e) {
            die("Upload failed: " . $e->getMessage());
        }
    }
}

// ===== SAVE MESSAGE & MEDIA IN DB =====
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

echo "ok";
?>
