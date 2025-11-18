<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message      = trim($_POST["message"] ?? '');
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"missing client"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES['files']['name'][0])) {

    $baseFolder = __DIR__ . "/upload/";
    $name0 = $_FILES['files']['name'][0];
    $tmp0  = $_FILES['files']['tmp_name'][0];
    $ext   = strtolower(pathinfo($name0, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $media_type = "image";
        $folder = $baseFolder . "chat_images/";
        $relFolder = "CSR/upload/chat_images/";
    } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
        $media_type = "video";
        $folder = $baseFolder . "chat_videos/";
        $relFolder = "CSR/upload/chat_videos/";
    }

    if ($media_type) {
        if (!is_dir($folder)) mkdir($folder,0777,true);
        $newName = time()."_".rand(1111,9999).".".$ext;
        $fullPath = $folder.$newName;

        if (move_uploaded_file($tmp0, $fullPath)) {
            $media_path = $relFolder.$newName;
        }
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
    ":csr" => $csr_fullname,
]);

echo json_encode(["status"=>"ok"]);
