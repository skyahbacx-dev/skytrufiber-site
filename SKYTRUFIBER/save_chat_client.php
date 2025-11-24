<?php
session_start();
include "../db_connect.php";
include "CSR/b2_upload.php";
header("Content-Type: application/json");

$username = $_POST["username"] ?? "";
$message  = trim($_POST["message"] ?? "");

if (!$username) { echo json_encode(["status"=>"error"]); exit; }

$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :n LIMIT 1");
$stmt->execute([":n" => $username]);
$client_id = intval($stmt->fetchColumn());

if (!$client_id) { echo json_encode(["status"=>"error"]); exit; }

$media_path = null;
$media_type = null;

/* file upload */
if (!empty($_FILES["file"]["name"])) {
    $fileTmp  = $_FILES["file"]["tmp_name"];
    $fileName = time() . "_" . basename($_FILES["file"]["name"]);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) $media_type = "image";
    elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) $media_type = "video";

    if ($media_type) {
        $uploaded = b2_upload($fileTmp, "chat/$fileName");
        if ($uploaded) $media_path = $uploaded;
    }
}

/* Insert message */
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
    VALUES (:cid, 'client', :msg, :p, :t, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":p"   => $media_path,
    ":t"   => $media_type
]);

echo json_encode(["status"=>"ok"]);
