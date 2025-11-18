<?php
// CSR/save_chat_csr.php
session_start();
require_once "../db_connect.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? "");
$client_id    = isset($_POST["client_id"]) ? (int)$_POST["client_id"] : 0;
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client"]);
    exit;
}

if ($message === "" && empty($_FILES["files"]["name"][0])) {
    echo json_encode(["status" => "error", "msg" => "Empty message"]);
    exit;
}

$media_url  = null;
$media_type = null;

// handle first file (DB schema only has single media_url/media_type)
if (!empty($_FILES["files"]["name"][0])) {
    $origName = $_FILES["files"]["name"][0];
    $tmpName  = $_FILES["files"]["tmp_name"][0];

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ["jpg","jpeg","png","gif","webp"]);
    $isVideo = in_array($ext, ["mp4","mov","avi","mkv","webm"]);

    if ($isImage || $isVideo) {
        $media_type = $isImage ? "image" : "video";

        $baseDir = realpath(__DIR__ . "/../upload");
        if ($baseDir === false) {
            $baseDir = __DIR__ . "/../upload";
        }

        if ($isImage) {
            $folder = $baseDir . "/chat_images/";
            $relFolder = "upload/chat_images/";
        } else {
            $folder = $baseDir . "/chat_videos/";
            $relFolder = "upload/chat_videos/";
        }

        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }

        $newName = time() . "_" . mt_rand(1000, 9999) . "." . $ext;
        $fullPath = $folder . $newName;

        if (move_uploaded_file($tmpName, $fullPath)) {
            // path relative to CSR folder (for <img src=""> in browser)
            $media_url = $relFolder . $newName;
        }
    }
}

$sql = "
    INSERT INTO chat (client_id, sender_type, message, media_url, media_type, csr_fullname, created_at, updated_at)
    VALUES (:cid, :stype, :msg, :murl, :mtype, :csr, NOW(), NOW())
";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":murl"  => $media_url,
    ":mtype" => $media_type,
    ":csr"   => $csr_fullname,
]);

echo json_encode(["status" => "ok"]);
