<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

// -----------------------------
// VALIDATE USER
// -----------------------------
$stmt = $conn->prepare("
    SELECT id 
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "msg" => "invalid user"]);
    exit;
}

$client_id = (int)$user["id"];

// -----------------------------
// INSERT CHAT ROW FIRST
// -----------------------------
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);

$chatId = $conn->lastInsertId();

// -----------------------------
// PROCESS UPLOADED MEDIA
// -----------------------------
foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {

    if (!file_exists($tmp)) continue;

    $blob = file_get_contents($tmp);
    if (!$blob) continue;

    $mime = $_FILES["media"]["type"][$i];
    $name = $_FILES["media"]["name"][$i];

    // Determine type
    $type = "file";
    if (strpos($mime, "image") !== false) $type = "image";
    if (strpos($mime, "video") !== false) $type = "video";

    // Thumbnail generator
    $thumb = null;

    if ($type === "image" && extension_loaded("imagick")) {
        $im = new Imagick();
        $im->readImageBlob($blob);
        $im->thumbnailImage(250, 250, true);
        $thumb = $im->getImageBlob();
        $im->destroy();
    }

    // Save to DB
    $m = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, thumb_blob, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $m->execute([$chatId, $name, $type, $blob, $thumb]);
}

echo json_encode([
    "status" => "ok",
    "chat_id" => $chatId,
    "msg" => "upload complete"
]);
