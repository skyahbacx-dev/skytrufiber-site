<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");

$client_id = $_POST["client_id"] ?? null;
$sender    = $_POST["sender_type"] ?? null;

if (!$client_id || !$sender) {
    echo json_encode(["status" => "error", "msg" => "Missing required data"]);
    exit;
}

if (!isset($_FILES["media"])) {
    echo json_encode(["status" => "error", "msg" => "No file uploaded"]);
    exit;
}

$file = $_FILES["media"];
$uploadDirectory = "../../upload/chat_media/";

if (!file_exists($uploadDirectory)) {
    mkdir($uploadDirectory, 0775, true);
}

$fileName     = time() . "_" . basename($file["name"]);
$targetPath   = $uploadDirectory . $fileName;
$mediaDbPath  = "upload/chat_media/" . $fileName;
$fileType     = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

$allowed = ["jpg", "jpeg", "png", "gif", "mp4", "mov", "avi", "pdf", "doc", "docx"];
if (!in_array($fileType, $allowed)) {
    echo json_encode(["status" => "error", "msg" => "Invalid file type"]);
    exit;
}

$mediaType = "file";
if (in_array($fileType, ["jpg", "jpeg", "png", "gif"])) $mediaType = "image";
if (in_array($fileType, ["mp4", "mov", "avi"])) $mediaType = "video";

if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    echo json_encode(["status" => "error", "msg" => "Upload failed"]);
    exit;
}

try {
    // create chat placeholder entry
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, ?, NULL, 1, 0, NOW())
    ");
    $stmt->execute([$client_id, $sender]);

    $chatId = $conn->lastInsertId();

    // insert media detail record
    $insertMedia = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $insertMedia->execute([$chatId, $mediaDbPath, $mediaType]);

    echo json_encode(["status" => "ok"]);
    exit;

} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
    exit;
}
