<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$sender_type  = $_POST["sender_type"] ?? "client";
$message      = trim($_POST["message"] ?? '');
$username     = $_POST["username"] ?? '';

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "missing username"]);
    exit;
}

// Find client_id
$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :u LIMIT 1");
$stmt->execute([":u" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "unknown client"]);
    exit;
}

// Handle media upload
$media_path = null;
$media_type = null;

if (!empty($_FILES['file']['name'])) {

    include "b2_upload.php";

    $fileName = time() . "_" . $_FILES['file']['name'];
    $uploadedUrl = b2_upload($_FILES['file']['tmp_name'], $fileName);

    if ($uploadedUrl) {
        $media_path = $uploadedUrl;
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image' : 'video';
    }
}

// Save chat
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, NOW())
");

$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":mp"    => $media_path,
    ":mt"    => $media_type
]);

echo json_encode(["status" => "ok"]);
?>
