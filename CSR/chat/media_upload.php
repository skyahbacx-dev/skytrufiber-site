<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;
$message   = $_POST["message"] ?? "";

if (!$client_id || !$csr) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

// Create main chat record FIRST (text or placeholder)
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', ?, TRUE, FALSE, NOW())
");
$stmt->execute([$client_id, $message]);
$chatId = $conn->lastInsertId();

// TEXT ONLY MESSAGE
if (!isset($_FILES["media"]) || empty($_FILES["media"]["name"][0])) {
    echo json_encode(["status" => "ok", "chat_id" => $chatId, "msg" => "Text saved"]);
    exit;
}

// MEDIA FILES
foreach ($_FILES["media"]["name"] as $i => $name) {

    $tmpName = $_FILES["media"]["tmp_name"][$i];

    // SKIP empty tmp entries to avoid "Path cannot be empty"
    if (!$tmpName || !file_exists($tmpName)) {
        continue;
    }

    $fileType = $_FILES["media"]["type"][$i];
    $fileData = file_get_contents($tmpName);

    if ($fileData === false) {
        echo json_encode(["status" => "error", "msg" => "Failed to read uploaded file"]);
        exit;
    }

    // Determine file type
    $type = "file";
    if (strpos($fileType, "image") !== false)  $type = "image";
    elseif (strpos($fileType, "video") !== false) $type = "video";

    // Insert BLOB into DB
    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $mediaInsert->bindValue(1, $chatId, PDO::PARAM_INT);
    $mediaInsert->bindValue(2, $name, PDO::PARAM_STR);
    $mediaInsert->bindValue(3, $type, PDO::PARAM_STR);
    $mediaInsert->bindValue(4, $fileData, PDO::PARAM_LOB);
    $mediaInsert->execute();
}

echo json_encode(["status" => "ok", "chat_id" => $chatId, "msg" => "Upload complete"]);
exit;
?>
