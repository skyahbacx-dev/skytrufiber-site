<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

// DEBUG MODE: Print everything
header("Content-Type: application/json; charset=utf-8");

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;

$debugFile = __DIR__ . "/debug_media.log";

// Log request
file_put_contents($debugFile, 
    "---- MEDIA REQUEST " . date("Y-m-d H:i:s") . " ----\n" .
    "POST: " . json_encode($_POST) . "\n" .
    "SESSION: " . json_encode($_SESSION) . "\n" .
    "FILES: " . json_encode($_FILES) . "\n\n",
    FILE_APPEND
);

if (!$client_id || !$csr) {
    echo json_encode(["status" => "error", "msg" => "Missing client or session"]);
    exit;
}

if (!isset($_FILES["media"])) {
    echo json_encode(["status" => "error", "msg" => "No media file present"]);
    exit;
}

$file = $_FILES["media"];
$uploadDirectory = "../../upload/chat_media/";

if (!file_exists($uploadDirectory)) {
    file_put_contents($debugFile, "Upload dir missing, creating...\n", FILE_APPEND);
    mkdir($uploadDirectory, 0775, true);
}

$fileName     = time() . "_" . basename($file["name"]);
$targetPath   = $uploadDirectory . $fileName;
$mediaDbPath  = "upload/chat_media/" . $fileName;
$fileType     = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

file_put_contents($debugFile, "Saving to: $targetPath\n", FILE_APPEND);

$allowed = ["jpg", "jpeg", "png", "gif", "mp4", "mov", "avi", "pdf", "doc", "docx"];
if (!in_array($fileType, $allowed)) {
    file_put_contents($debugFile, "File type rejected: $fileType\n", FILE_APPEND);
    echo json_encode(["status" => "error", "msg" => "Invalid type $fileType"]);
    exit;
}

$mediaType = "file";
if (in_array($fileType, ["jpg", "jpeg", "png", "gif"])) $mediaType = "image";
if (in_array($fileType, ["mp4", "mov", "avi"])) $mediaType = "video";

if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    file_put_contents($debugFile, "MOVE FAILED: " . $file["error"] . "\n", FILE_APPEND);
    echo json_encode(["status" => "error", "msg" => "File move failed"]);
    exit;
}

try {
    // Insert into chat first
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', NULL, false, false, NOW())
    ");
    $stmt->execute([$client_id]);

    $chatId = $conn->lastInsertId();

    // Insert media
    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $mediaDbPath, $mediaType]);

    file_put_contents($debugFile, "SUCCESS chatId=$chatId path=$mediaDbPath type=$mediaType\n\n", FILE_APPEND);

    echo json_encode(["status" => "ok"]);
    exit;

} catch (Throwable $e) {

    // Log error to file and output more detail
    file_put_contents($debugFile, "ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);

    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
    exit;
}
