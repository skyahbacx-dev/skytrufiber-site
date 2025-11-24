<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$sender_type = $_POST["sender_type"] ?? 'client';
$message     = trim($_POST["message"] ?? '');
$username    = $_POST["username"] ?? '';

if (!$username) {
    echo json_encode(["status"=>"error","msg"=>"no username"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name"=>$username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"client not found"]);
    exit;
}

// ----- B2 UPLOAD -----
include "../b2_upload.php";

$media_path = null;
$media_type = null;

if (!empty($_FILES['file']['tmp_name'])) {

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . "_" . $_FILES['file']['name'];

    $url = b2_upload($fileTmp, $fileName);

    if ($url) {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image' : 'video';
        $media_path = $url;
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, NOW())
");

$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":mp"    => $media_path,
    ":mt"    => $media_type,
]);

echo json_encode(["status"=>"ok"]);
