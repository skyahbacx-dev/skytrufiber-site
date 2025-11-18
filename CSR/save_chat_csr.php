<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? '');
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client"]);
    exit;
}

// upload base
$uploadBaseDir = __DIR__ . "/../upload/";
$media_path = null;
$media_type = null;

// MULTIPLE FILES support (use first file only because DB has single media column)
if (!empty($_FILES['files']['name'][0])) {

    $name0 = $_FILES['files']['name'][0];
    $tmp0  = $_FILES['files']['tmp_name'][0];
    $ext   = strtolower(pathinfo($name0, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $media_type = "image";
        $folder     = $uploadBaseDir . "chat_images/";
        $relFolder  = "upload/chat_images/";
    } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
        $media_type = "video";
        $folder     = $uploadBaseDir . "chat_videos/";
        $relFolder  = "upload/chat_videos/";
    }

    if ($media_type) {
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $newName  = time() . "_" . rand(1000,9999) . "." . $ext;
        $fullPath = $folder . $newName;

        if (move_uploaded_file($tmp0, $fullPath)) {
            $media_path = $relFolder . $newName;
        }
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":stype" => $sender_type,
    ":msg" => $message,
    ":mp"  => $media_path,
    ":mt"  => $media_type,
    ":csr" => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
