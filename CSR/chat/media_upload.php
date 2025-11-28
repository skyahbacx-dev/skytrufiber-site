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

// temp upload directory (Writable on Render)
$uploadDirectory = "/tmp/chat_media/";
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

$file = $_FILES["media"];
$fileName = time() . "_" . preg_replace("/\s+/", "_", $file["name"]);
$targetPath = $uploadDirectory . $fileName;

if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    echo json_encode(["status" => "error", "msg" => "Move failed"]);
    exit;
}

// Save file in permanent location: public CDN or DB reference
$mediaDbPath = "tmp/chat_media/" . $fileName;

$stmt = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
                        VALUES (?, 'csr', '', TRUE, FALSE, NOW())");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

$mediaInsert = $conn->prepare("INSERT INTO chat_media (chat_id, media_path, media_type)
                               VALUES (?, ?, ?)");
$mediaInsert->execute([$chatId, $mediaDbPath, 'image']);

echo json_encode(["status" => "ok"]);
exit;
?>
