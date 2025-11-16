<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = $_POST["client_id"] ?? 0;
$message   = trim($_POST["message"] ?? "");
$csr_fullname = $_SESSION["csr_fullname"];

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client ID"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES["file"]["name"])) {
    $ext = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
        $folder = "../uploads/chat_images/";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
        $folder = "../uploads/chat_videos/";
    }

    if ($media_type) {
        $newFile = time()."_".rand(1000,9999).".".$ext;
        $path = $folder.$newFile;
        move_uploaded_file($_FILES["file"]["tmp_name"], $path);
        $media_path = substr($path, 3);
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :mp, :mt, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":mp"  => $media_path,
    ":mt"  => $media_type,
    ":csr" => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
