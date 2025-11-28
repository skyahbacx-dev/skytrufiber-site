<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

// MUST be correct absolute path for Neon hosting
$uploadDirectory = $_SERVER['DOCUMENT_ROOT'] . "/upload/chat_media/";

require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$sender = $_SESSION["csr_user"] ?? null;

if (!$client_id || !$sender) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

if (!isset($_FILES["media"])) {
    echo json_encode(["status" => "error", "msg" => "No file"]);
    exit;
}

$file = $_FILES["media"];

// Ensure folder exists
if (!file_exists($uploadDirectory)) {
    mkdir($uploadDirectory, 0775, true);
}

$fileName = time() . "_" . basename($file["name"]);
$targetPath = $uploadDirectory . $fileName;
$mediaDbPath = "upload/chat_media/" . $fileName;

// Validate allowed types
$allowed = ["jpg", "jpeg", "png", "gif", "mp4", "mov", "avi", "pdf"];
$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    echo json_encode(["status" => "error", "msg" => "Invalid format"]);
    exit;
}

$mediaType = (in_array($ext, ["jpg", "jpeg", "png", "gif"])) ? "image" : "file";

// upload file
if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    echo json_encode(["status" => "error", "msg" => "Upload failed"]);
    exit;
}

try {
    // insert placeholder chat
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', NULL, FALSE, FALSE, NOW())
    ");
    $stmt->execute([$client_id]);

    $chatId = $conn->lastInsertId();

    // insert media reference
    $media = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $media->execute([$chatId, $mediaDbPath, $mediaType]);

    echo json_encode(["status" => "ok"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
