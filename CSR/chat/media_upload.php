<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;
$message   = $_POST["message"] ?? ""; // Optional message text

if (!$client_id || !$csr) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

// Create message container row first
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', ?, TRUE, FALSE, NOW())
");
$stmt->execute([$client_id, $message]);
$chatId = $conn->lastInsertId();

// If no files uploaded, just return success for text-only message
if (!isset($_FILES["media"]) || empty($_FILES["media"]["name"][0])) {
    echo json_encode(["status" => "ok", "chat_id" => $chatId, "msg" => "Text message saved"]);
    exit;
}

// Process uploaded media and store BLOB to database
foreach ($_FILES["media"]["name"] as $i => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$i];
    $fileType = $_FILES["media"]["type"][$i];
    $fileData = file_get_contents($tmpName);

    if ($fileData === false) {
        echo json_encode(["status" => "error", "msg" => "Failed to read file"]);
        exit;
    }

    // Determine type
    $type = "file";
    if (strpos($fileType, "image") !== false) $type = "image";
    elseif (strpos($fileType, "video") !== false) $type = "video";

    // Insert binary BLOB
    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $mediaInsert->bindValue(1, $chatId, PDO::PARAM_INT);
    $mediaInsert->bindValue(2, $name, PDO::PARAM_STR); // original filename only
    $mediaInsert->bindValue(3, $type, PDO::PARAM_STR);
    $mediaInsert->bindValue(4, $fileData, PDO::PARAM_LOB);
    $mediaInsert->execute();
}

echo json_encode(["status" => "ok", "chat_id" => $chatId, "msg" => "Upload complete"]);
exit;
?>
