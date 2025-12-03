<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

if (!$username)
    exit(json_encode(["status"=>"error", "msg"=>"missing username"]));

// PostgreSQL user lookup
$stmt = $conn->prepare("
    SELECT id FROM users
    WHERE email = ?
       OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error", "msg"=>"invalid user"]));

$client_id = (int)$user["id"];

// Create chat row
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, 1, 0, NOW())
");
$insert->execute([$client_id, $message]);

// PostgreSQL last insert id
$chatId = $conn->lastInsertId("chat_id_seq");

// Handle uploads
foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {

    if (!file_exists($tmp)) continue;

    $blob = file_get_contents($tmp);
    if (!$blob) continue;

    $type = $_FILES["media"]["type"][$i];
    $name = $_FILES["media"]["name"][$i];

    $mediaType = "file";
    if (strpos($type, "image") !== false) $mediaType = "image";
    if (strpos($type, "video") !== false) $mediaType = "video";

    $thumb = null;

    if ($mediaType === "image" && extension_loaded("imagick")) {
        $im = new Imagick();
        $im->readImageBlob($blob);
        $im->thumbnailImage(250, 250, true);
        $thumb = $im->getImageBlob();
        $im->destroy();
    }

    $m = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, thumb_blob, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $m->execute([$chatId, $name, $mediaType, $blob, $thumb]);
}

echo json_encode([
    "status" => "ok",
    "msg" => "media uploaded",
    "chat_id" => $chatId
]);
