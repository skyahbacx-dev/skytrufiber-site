<?php
require_once "../../db_connect.php";
session_start();

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    echo "Missing client ID";
    exit;
}

if (!isset($_FILES["media"])) {
    echo "No file uploaded";
    exit;
}

$file = $_FILES["media"];
$uploadDir = "../../upload/chat_media/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filename = time() . "_" . basename($file["name"]);
$targetPath = $uploadDir . $filename;

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowed = ["jpg", "jpeg", "png", "gif", "mp4", "mov", "avi", "pdf", "doc", "docx"];

if (!in_array($ext, $allowed)) {
    echo "Invalid file type";
    exit;
}

$mediaType = "file";
if (in_array($ext, ["jpg", "jpeg", "png", "gif"])) $mediaType = "image";
if (in_array($ext, ["mp4", "mov", "avi"])) $mediaType = "video";

if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    echo "Upload failed";
    exit;
}

$dbPath = "upload/chat_media/" . $filename;

// Insert placeholder chat message
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at)
    VALUES (?, 'csr', NULL, NOW())
");
$stmt->execute([$client_id]);

$chatID = $conn->lastInsertId();

$mediaStmt = $conn->prepare("
    INSERT INTO chat_media (chat_id, media_path, media_type)
    VALUES (?, ?, ?)
");
$mediaStmt->execute([$chatID, $dbPath, $mediaType]);

echo "OK";
