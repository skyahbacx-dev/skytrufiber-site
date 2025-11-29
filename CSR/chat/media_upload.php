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

// Create container message entry
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', '', TRUE, FALSE, NOW())
");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

$uploadDirectory = "/tmp/chat_media/";
if (!file_exists($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

foreach ($_FILES["media"]["name"] as $index => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$index];
    $fileType = $_FILES["media"]["type"][$index];
    $fileName = round(microtime(true) * 1000) . "_" . preg_replace("/\s+/", "_", $name);

    $targetPath = $uploadDirectory . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        echo json_encode(["status" => "error", "msg" => "Upload failed"]);
        exit;
    }

    // Serve via PHP since /tmp is not publicly accessible
    $mediaDbPath = $fileName;

    $type = "file";
    if (strpos($fileType, "image") !== false) $type = "image";
    elseif (strpos($fileType, "video") !== false) $type = "video";

    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $mediaDbPath, $type]);
}

echo json_encode(["status" => "ok"]);
exit;
?>
