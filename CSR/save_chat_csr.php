<?php
session_start();
include "../db_connect.php";
include "b2_upload.php"; 
header("Content-Type: application/json");

if (!isset($_SESSION["csr_user"])) {
    echo json_encode(["status"=>"error","msg"=>"Unauthorized"]);
    exit;
}

$csr_fullname = $_SESSION["csr_fullname"];
$client_id    = intval($_POST["client_id"] ?? 0);
$message      = trim($_POST["message"] ?? "");

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"Missing client"]);
    exit;
}

$media_path = null;
$media_type = null;

/* Handle ONE uploaded file */
if (!empty($_FILES["file"]["name"])) {
    $fileTmp  = $_FILES["file"]["tmp_name"];
    $fileName = time() . "_" . basename($_FILES["file"]["name"]);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
    }

    if ($media_type) {
        $uploaded = b2_upload($fileTmp, "chat/$fileName");
        if ($uploaded) {
            $media_path = $uploaded;
        }
    }
}

/* Save message */
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :path, :type, :csr, NOW())
");
$stmt->execute([
    ":cid"  => $client_id,
    ":msg"  => $message,
    ":path" => $media_path,
    ":type" => $media_type,
    ":csr"  => $csr_fullname,
]);

echo json_encode(["status"=>"ok"]);
