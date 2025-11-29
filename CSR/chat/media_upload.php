<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;
$message   = $_POST["message"] ?? "";   // text message included

if (!$client_id || !$csr) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

// Create a chat record container
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', ?, TRUE, FALSE, NOW())
");
$stmt->execute([$client_id, $message]);
$chatId = $conn->lastInsertId();

// If there are no files, simply return (text only message)
if (empty($_FILES["media"]["name"])) {
    echo json_encode(["status" => "ok", "chat_id" => $chatId]);
    exit;
}

// Storage directory for Railway
$uploadDirectory = "/tmp/chat_media/";
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

foreach ($_FILES["media"]["name"] as $index => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$index];
    $fileType = $_FILES["media"]["type"][$index];

    // Unique file name
    $fileName = round(microtime(true) * 1000) . "_" . preg_replace("/\s+/", "_", $name);
    $targetPath = $uploadDirectory . $fileName;

    // Move uploaded file to temporary storage
    if (!move_uploaded_file($tmpName, $targetPath)) {
        echo json_encode(["status" => "error", "msg" => "File move failed"]);
        exit;
    }

    // DB stores only filename
    $type = "file";
    if (strpos($fileType, "image") !== false) {
        $type = "image";
    } elseif (strpos($fileType, "video") !== false) {
        $type = "video";
    }

    // Insert into chat_media table
    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $fileName, $type]);
}

// Success response
echo json_encode(["status" => "ok", "chat_id" => $chatId]);
exit;

?>
