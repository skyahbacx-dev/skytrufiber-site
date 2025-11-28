<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;

if (!$client_id || !$csr) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

if (!isset($_FILES["media"])) {
    echo json_encode(["status" => "error", "msg" => "No file received"]);
    exit;
}

$file = $_FILES["media"];

// ===============================
// Validate and prepare file
// ===============================
$allowed = ["jpg", "jpeg", "png", "gif", "mp4", "mov", "avi", "pdf", "doc", "docx"];
$fileExt = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowed)) {
    echo json_encode(["status" => "error", "msg" => "Invalid file type"]);
    exit;
}

$uploadDirectory = "/tmp/chat_media/";
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

$fileName = time() . "_" . preg_replace("/\s+/", "_", $file["name"]);
$targetPath = $uploadDirectory . $fileName;

// ===============================
// Move file to /tmp
// ===============================
if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    echo json_encode([
        "status" => "error",
        "msg" => "Upload failed",
        "debug" => [
            "target" => $targetPath,
            "tmp" => $file["tmp_name"]
        ]
    ]);
    exit;
}

// Determine media type
$mediaType = "file";
if (in_array($fileExt, ["jpg", "jpeg", "png", "gif"])) $mediaType = "image";
if (in_array($fileExt, ["mp4", "mov", "avi"])) $mediaType = "video";

// Path saved to DB (relative path)
$mediaDbPath = "tmp/chat_media/" . $fileName;

// ===============================
// Insert placeholder chat message
// ===============================
try {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', '', 1, 0, NOW())
    ");
    $stmt->execute([$client_id]);

    $chatId = $conn->lastInsertId();

    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $mediaDbPath, $mediaType]);

    echo json_encode([
        "status" => "ok",
        "path" => $mediaDbPath,
        "type" => $mediaType
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
    exit;
}
?>
