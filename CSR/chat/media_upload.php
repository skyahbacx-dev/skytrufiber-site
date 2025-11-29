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

// Create message container row
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', '', TRUE, FALSE, NOW())
");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

// Insert each file as binary blob
foreach ($_FILES["media"]["name"] as $i => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$i];
    $fileType = $_FILES["media"]["type"][$i];
    $fileData = file_get_contents($tmpName);

    if ($fileData === false) {
        echo json_encode(["status" => "error", "msg" => "Failed to read uploaded file"]);
        exit;
    }

    $type = "file";
    if (strpos($fileType, "image") !== false) $type = "image";
    elseif (strpos($fileType, "video") !== false) $type = "video";

    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob)
        VALUES (?, ?, ?, ?)
    ");
    $mediaInsert->bindValue(1, $chatId, PDO::PARAM_INT);
    $mediaInsert->bindValue(2, $name);
    $mediaInsert->bindValue(3, $type);
    $mediaInsert->bindValue(4, $fileData, PDO::PARAM_LOB);
    $mediaInsert->execute();
}

echo json_encode(["status" => "ok", "chat_id" => $chatId]);
exit;
