<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$message = trim($_POST["message"] ?? '');
$username = trim($_POST["username"] ?? '');

if (!$username) { echo json_encode(["status"=>"err","msg"=>"no username"]); exit; }

// get client id
$stmt = $conn->prepare("SELECT id FROM clients WHERE name=:n LIMIT 1");
$stmt->execute([":n"=>$username]);
$client_id = $stmt->fetchColumn();
if(!$client_id){ echo json_encode(["status"=>"err","msg"=>"no client"]); exit; }

$sender_type = "client";
$uploadDir = __DIR__ . "/../upload/";
$media_path = null;
$media_type = null;

if (!empty($_FILES['file']['name'])) {

    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $tmp = $_FILES['file']['tmp_name'];

    if (in_array($ext,['jpg','jpeg','png','gif','webp'])) {
        $media_type='image';
        $folder=$uploadDir."chat_images/";
        $rel="upload/chat_images/";
    } else if (in_array($ext,['mp4','mov','avi','mkv','webm'])) {
        $media_type='video';
        $folder=$uploadDir."chat_videos/";
        $rel="upload/chat_videos/";
    }

    if ($media_type) {
        if (!is_dir($folder)) mkdir($folder,0775,true);
        $newName = time()."_".rand(1000,9999).".".$ext;
        if (move_uploaded_file($tmp, $folder.$newName)) {
            $media_path = $rel.$newName;
        }
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
    VALUES (:cid,:type,:msg,:path,:mt,NOW())
");
$stmt->execute([
    ":cid"=>$client_id,
    ":type"=>$sender_type,
    ":msg"=>$message,
    ":path"=>$media_path,
    ":mt"=>$media_type
]);

echo json_encode(["status"=>"ok"]);
