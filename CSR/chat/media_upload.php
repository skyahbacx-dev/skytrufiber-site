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

if (empty($_FILES["media"]["name"])) {
    echo json_encode(["status" => "error", "msg" => "No files received"]);
    exit;
}

// Create chat container record
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', '', TRUE, FALSE, NOW())
");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

// PUBLIC upload directory
$uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/CSR/upload/chat_media/";

// Ensure folder exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach ($_FILES["media"]["name"] as $i => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$i];
    $fileType = $_FILES["media"]["type"][$i];
    $cleanFile = preg_replace("/\s+/", "_", $name);

    $fileName = round(microtime(true) * 1000) . "_" . $cleanFile;
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetFile)) {
        echo json_encode(["status" => "error", "msg" => "Upload failed"]);
        exit;
    }

    // Detect file type
    $type = "file";
    if (strpos($fileType, "image") !== false) {
        $type = "image";
    } elseif (strpos($fileType, "video") !== false) {
        $type = "video";
    }

    // Create the PUBLIC PATH to store in DB
    // Example: https://ahbadevt.com/CSR/upload/chat_media/12345_photo.jpg
    $publicPath = "/CSR/upload/chat_media/" . $fileName;

    // Save to DB
    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $publicPath, $type]);
}

echo json_encode(["status" => "ok", "chat_id" => $chatId]);
exit;
?>
