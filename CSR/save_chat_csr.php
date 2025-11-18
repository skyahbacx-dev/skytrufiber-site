<?php
session_start();
include '../db_connect.php';
header("Content-Type: application/json");

$client_id    = (int)($_POST["client_id"] ?? 0);
$message      = trim($_POST["message"] ?? '');
$csr_fullname = $_POST["csr_fullname"] ?? '';
$sender_type  = "csr";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client ID"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at, csr_fullname)
    VALUES (:cid, :stype, :msg, NOW(), :full)
    RETURNING id
");
$stmt->execute([
    ":cid" => $client_id,
    ":stype" => $sender_type,
    ":msg" => $message,
    ":full" => $csr_fullname
]);
$chat_id = $stmt->fetchColumn();

// MULTIPLE FILES
if (!empty($_FILES['files']['name'][0])) {

    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {

        $name = $_FILES['files']['name'][$i];
        $tmp  = $_FILES['files']['tmp_name'][$i];
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $type = "image";
            $folder = "../upload/chat_images/";
            $relFolder = "upload/chat_images/";
        } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
            $type = "video";
            $folder = "../upload/chat_videos/";
            $relFolder = "upload/chat_videos/";
        } else continue;

        if (!is_dir($folder)) @mkdir($folder,0775,true);

        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $finalPath = $folder . $newName;

        if (move_uploaded_file($tmp, $finalPath)) {
            $media_path = $relFolder . $newName;

            $m = $conn->prepare("
                INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
                VALUES (:cid, :mp, :mt, NOW())
            ");

            $m->execute([
                ":cid" => $chat_id,
                ":mp"  => $media_path,
                ":mt"  => $type
            ]);
        }
    }
}

echo json_encode(["status" => "ok"]);
exit;
?>
