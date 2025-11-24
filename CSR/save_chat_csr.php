<?php
session_start();
include "../db_connect.php";
include "b2_upload.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$csr_user     = $_SESSION["csr_user"] ?? null;
$csr_fullname = $_SESSION["csr_fullname"] ?? "CSR";
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status"=>"error", "msg"=>"Invalid session or client"]);
    exit;
}

$media_url  = null;
$media_type = null;

/* Handle file uploads */
if (!empty($_FILES["files"]["name"][0])) {

    $fileName = $_FILES["files"]["name"][0];
    $tmpName  = $_FILES["files"]["tmp_name"][0];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) $media_type = "image";
    if (in_array($ext, ["mp4","mov","avi","mkv","webm"]))  $media_type = "video";

    $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

    // Upload to Backblaze
    $b2 = b2_upload($tmpName, $newName);

    if ($b2) {
        $media_url = $b2; // full https url for viewing online
    }
}

/* INSERT into chat */
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

echo json_encode([
    "status" => "ok",
    "media_url" => $media_url,
    "media_type" => $media_type
]);
