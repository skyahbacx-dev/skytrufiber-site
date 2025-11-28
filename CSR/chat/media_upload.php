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
    echo json_encode(["status" => "error", "msg" => "No files"]);
    exit;
}

$tmpFolder = "/tmp/chat_media/";
$publicFolder = $_SERVER["DOCUMENT_ROOT"] . "/chat_media/";

if (!is_dir($tmpFolder)) mkdir($tmpFolder, 0777, true);
if (!is_dir($publicFolder)) mkdir($publicFolder, 0777, true);

$files = $_FILES["media"];
$fileCount = count($files["name"]);

for ($i = 0; $i < $fileCount; $i++) {

    $name = time() . "_" . preg_replace("/\s+/", "_", $files["name"][$i]);
    $tmpFile = $files["tmp_name"][$i];
    $tmpPath = $tmpFolder . $name;

    move_uploaded_file($tmpFile, $tmpPath);
    copy($tmpPath, $publicFolder . $name);

    $mediaDbPath = "chat_media/" . $name;

    $stmt = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
                            VALUES (?, 'csr', '', 1, 0, NOW())");
    $stmt->execute([$client_id]);
    $chatID = $conn->lastInsertId();

    $mediaType = (strpos($files["type"][$i], "image") !== false) ? "image" : "file";

    $stmt2 = $conn->prepare("INSERT INTO chat_media (chat_id, media_path, media_type)
                                VALUES (?, ?, ?)");
    $stmt2->execute([$chatID, $mediaDbPath, $mediaType]);
}

echo json_encode(["status" => "ok"]);
exit;
?>
