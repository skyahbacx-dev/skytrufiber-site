<?php
session_start();
include "../db_connect.php";

require "../vendor/autoload.php";

use BackblazeB2\Client;

// AUTH KEYS (from your B2 bucket)
$B2_KEY_ID  = "005a548887f9c4f0000000002";
$B2_APP_KEY = "K005fOYaprINPto/Qdm9wex0w4v/L2k";
$B2_BUCKET = "ahba-chat-media";

$client = new Client($B2_KEY_ID, $B2_APP_KEY);

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

if (!empty($_FILES['files']['name'][0])) {
    $fileName = time() . "_" . rand(1000,9999) . "_" . $_FILES['files']['name'][0];
    $tmp = $_FILES['files']['tmp_name'][0];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $media_type = "image";
    } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
        $media_type = "video";
    }

    if ($media_type) {
        $upload = $client->upload([
            'BucketName' => $B2_BUCKET,
            'FileName'   => $fileName,
            'Body'       => fopen($tmp, 'r')
        ]);

        $media_path = $upload->getDownloadUrl();
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
