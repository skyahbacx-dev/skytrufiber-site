<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$sender_type = "csr";
$message  = trim($_POST["message"] ?? '');
$client_id = $_POST["client_id"] ?? 0;
$csr_user = $_POST["csr_user"] ?? '';
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES['file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $media_type = 'image';
        $folder = "../uploads/chat_images/";
    } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
        $media_type = 'video';
        $folder = "../uploads/chat_videos/";
    }

    if ($media_type) {
        $newName = time()."_".rand(1000,9999).".".$ext;
        $path = $folder.$newName;
        move_uploaded_file($_FILES['file']['tmp_name'], $path);
        $media_path = substr($path,3);
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
?>
