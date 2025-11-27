<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_POST["csr"] ?? null;

if (!$client_id || !$csr) {
    echo "Missing required data.";
    exit;
}

if (!isset($_FILES["media"])) {
    echo "No file uploaded.";
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

// Validate allowed formats
$allowed = ["jpg", "jpeg", "png", "gif", "mp4", "mov", "avi", "pdf", "doc", "docx"];
if (!in_array($fileType, $allowed)) {
    echo "Invalid file type.";
    exit;
}

$mediaType = "file";
if (in_array($fileType, ["jpg", "jpeg", "png", "gif"])) $mediaType = "image";
if (in_array($fileType, ["mp4", "mov", "avi"])) $mediaType = "video";

// Upload the file
if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    echo "Upload failed.";
    exit;
}

try {
    // Create a placeholder chat entry for the media message
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', NULL, TRUE, FALSE, NOW())
    ");
    $stmt->execute([$client_id]);

    $chatId = $conn->lastInsertId();

    // Insert media record
    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $mediaDbPath, $mediaType]);

    echo "OK";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
