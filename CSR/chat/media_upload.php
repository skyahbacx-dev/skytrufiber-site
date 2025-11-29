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

// Create group message entry (single wrapper for all files)
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', '', TRUE, FALSE, NOW())
");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

// Upload directory in Render (must be writable)
$uploadDirectory = "/tmp/chat_media/";
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

foreach ($_FILES["media"]["name"] as $index => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$index];
    $fileType = $_FILES["media"]["type"][$index];

    // More unique filename using microtime
    $fileName = round(microtime(true) * 1000) . "_" . preg_replace("/\s+/", "_", $name);
    $targetPath = $uploadDirectory . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        continue; // skip failed file but continue others
    }

    // Normalize DB path for front-end
    $mediaDbPath = "tmp/chat_media/" . $fileName;

    // Determine file type
    if (strpos($fileType, "image") !== false) {
        $type = "image";
    } elseif (strpos($fileType, "video") !== false) {
        $type = "video";
    } else {
        $type = "file";
    }

    // Save reference in DB
    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $mediaDbPath, $type]);
}

echo json_encode(["status" => "ok", "chat_id" => $chatId]);
exit;
?>
