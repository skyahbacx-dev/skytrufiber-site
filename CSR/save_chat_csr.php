<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

// REQUIRED DATA
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? "";
$sender_type  = "csr";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client"]);
    exit;
}

// ==== BACKBLAZE CONFIG ====
$bucketName = "ahba-chat-media";
$bucketURL  = "https://s3.us-east-005.backblazeb2.com/$bucketName/";
$keyID      = "005a548887f9c4f0000000002";
$appKey     = "K005fOYaprINPto/Qdm9wex0w4v/L2k";

// ==== PROCESS FILES ====
$media_path = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {
    $fileName   = $_FILES["files"]["name"][0];
    $tmpPath    = $_FILES["files"]["tmp_name"][0];
    $ext        = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newName    = time() . "_" . rand(1000,9999) . "." . $ext;

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
    }

    if ($media_type) {
        $fileData = file_get_contents($tmpPath);

        // UPLOAD TO B2 S3-Compatible Storage
        $ch = curl_init("https://s3.us-east-005.backblazeb2.com/$bucketName/$newName");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode("$keyID:$appKey"),
            "Content-Type: application/octet-stream",
            "Content-Length: " . strlen($fileData)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        // PUBLIC URL STORED IN DB
        $media_path = $bucketURL . $newName;
    }
}

// ==== SAVE MESSAGE DATABASE ====
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
