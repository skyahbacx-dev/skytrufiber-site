<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";

header("Content-Type: application/json");

$username = $_POST["username"] ?? "";
$message  = trim($_POST["message"] ?? "");

$stmt = $conn->prepare("SELECT id FROM clients WHERE name=:n LIMIT 1");
$stmt->execute([":n" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"client not found"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES["file"]["name"])) {
    $tmp  = $_FILES["file"]["tmp_name"];
    $name = $_FILES["file"]["name"];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) $media_type = "image";
    if (in_array($ext, ["mp4","avi","mov","mkv","webm"])) $media_type = "video";

    if ($media_type) {
        $timestamp = time();
        $rand = rand(1000,9999);
        $fileName = "chat/client_{$client_id}/client/{$timestamp}_{$rand}.{$ext}";

        $uploadedUrl = b2_upload($tmp, $fileName);
        if ($uploadedUrl) $media_path = $uploadedUrl;
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
    VALUES (:cid, 'client', :msg, :path, :type, NOW())
");

$stmt->execute([
    ":cid"=>$client_id,
    ":msg"=>$message,
    ":path"=>$media_path,
    ":type"=>$media_type
]);

echo json_encode(["status" => "ok"]);
