<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

if (!$username)
    exit(json_encode(["status"=>"error", "msg"=>"missing username"]));

// --- USER LOOKUP (PostgreSQL-SAFE) ---
$stmt = $conn->prepare("
    SELECT id 
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error", "msg"=>"invalid user"]));

$client_id = (int)$user["id"];

// INSERT MAIN CHAT ROW
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);

$chatId = $conn->lastInsertId();

// --------------------------------------------
// UPLOAD AND STORE MEDIA
// --------------------------------------------
foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {

    if (!file_exists($tmp)) continue;

    $blob = file_get_contents($tmp);
    if (!$blob) continue;

    $type = $_FILES["media"]["type"][$i];
    $name = $_FILES["media"]["name"][$i];

    // Detect media type
    $mediaType = "file";
    if (strpos($type, "image") !== false) $mediaType = "image";
    if (strpos($type, "video") !== false) $mediaType = "video";

    // Generate thumbnail (only for images)
    $thumb = null;

    if ($mediaType === "image" && extension_loaded("imagick")) {
        try {
            $im = new Imagick();
            $im->readImageBlob($blob);

            // Smart thumbnail: keep aspect ratio
            $im->thumbnailImage(450, 450, true);

            $thumb = $im->getImageBlob();
            $im->destroy();
        } catch (Exception $e) {
            // Thumbnail failed — safe fallback
            $thumb = null;
        }
    }

    // Store in DB
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
