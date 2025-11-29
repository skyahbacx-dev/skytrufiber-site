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

// Create chat container row
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', '', TRUE, FALSE, NOW())
");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

// Correct directory for Railway / GitHub deployment
$uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/CSR/upload/chat_media/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach ($_FILES["media"]["name"] as $i => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$i];
    $fileType = $_FILES["media"]["type"][$i];

    $fileName = round(microtime(true) * 1000) . "_" . preg_replace("/\s+/", "_", $name);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        echo json_encode(["status" => "error", "msg" => "Upload failed"]);
        exit;
    }

    $type = "file";
    if (strpos($fileType, "image") !== false) $type = "image";
    elseif (strpos($fileType, "video") !== false) $type = "video";

    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $fileName, $type]);
}

echo json_encode(["status" => "ok", "chat_id" => $chatId]);
exit;
?>
