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

$file = $_FILES["media"] ?? null;
if (!$file || $file["error"] !== 0) {
    echo json_encode(["status" => "error", "msg" => "File upload error"]);
    exit;
}

$filename = time() . "_" . preg_replace("/\s+/", "_", $file["name"]);
$tmpPath  = "/tmp/chat_media/";

if (!is_dir($tmpPath)) mkdir($tmpPath, 0777, true);

$localTmpFile = $tmpPath . $filename;

if (!move_uploaded_file($file["tmp_name"], $localTmpFile)) {
    echo json_encode(["status" => "error", "msg" => "Move failed"]);
    exit;
}

// PUBLIC ACCESS folder (in project)
$publicPath = $_SERVER["DOCUMENT_ROOT"] . "/chat_media/";
if (!is_dir($publicPath)) mkdir($publicPath, 0777, true);

// Copy from /tmp -> /public/chat_media/
copy($localTmpFile, $publicPath . $filename);

// File path stored in DB for front-end display
$dbPath = "chat_media/" . $filename;

// Insert chat row
$stmt = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
                        VALUES (?, 'csr', '', 1, 0, NOW())");
$stmt->execute([$client_id]);
$chatID = $conn->lastInsertId();

// Insert media row
$mediaType = (strpos($file["type"], "image") !== false) ? "image" : "file";
$stmt2 = $conn->prepare("INSERT INTO chat_media (chat_id, media_path, media_type)
                         VALUES (?, ?, ?)");
$stmt2->execute([$chatID, $dbPath, $mediaType]);

echo json_encode(["status" => "ok"]);
exit;
?>
