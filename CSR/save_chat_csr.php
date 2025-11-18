<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

// ===== Backblaze credentials =====
$B2_KEY_ID = "005a548887f9c4f0000000002";
$B2_APP_KEY = "K005fOYaprINPto/Qdm9wex0w4v/L2k";
$B2_BUCKET_ID = "ahba-chat-media";   // Bucket name
$B2_BUCKET_URL = "https://s3.us-east-005.backblazeb2.com/ahba-chat-media";

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? '');
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "missing client"]);
    exit;
}

// ===== FILE UPLOAD to BACKBLAZE =====
$media_path = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {
    $name = $_FILES["files"]["name"][0];
    $tmp  = $_FILES["files"]["tmp_name"][0];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
    }

    if ($media_type) {
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

        // Upload using CURL to Backblaze S3 endpoint
        $remoteURL = "$B2_BUCKET_URL/$newName";
        $fp = fopen($tmp, "rb");

        $ch = curl_init($remoteURL);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($tmp));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode("$B2_KEY_ID:$B2_APP_KEY"),
            "Content-Type: application/octet-stream"
        ]);

        curl_exec($ch);
        curl_close($ch);

        $media_path = $remoteURL;
    }
}

// ===== Save chat message into DB =====
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
exit;
?>
