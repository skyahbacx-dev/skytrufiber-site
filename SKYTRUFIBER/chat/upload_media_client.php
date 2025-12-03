<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

if (!$username)
    exit(json_encode(["status"=>"error", "msg"=>"missing username"]));

// Find user
$stmt = $conn->prepare("
    SELECT id FROM users
    WHERE email = $1
       OR full_name = $1
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error", "msg"=>"invalid user"]));

$client_id = (int)$user["id"];

// Insert the chat row first
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES ($1, 'client', $2, TRUE, FALSE, NOW())
    RETURNING id
");
$insert->execute([$client_id, $message]);
$chatId = $insert->fetchColumn();

// If no files → return success
if (!isset($_FILES["media"]["tmp_name"]) || count($_FILES["media"]["tmp_name"]) === 0) {
    echo json_encode(["status"=>"ok", "msg"=>"no media", "chat_id"=>$chatId]);
    exit;
}

// Loop media
foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {

    if (!file_exists($tmp)) continue;

    $blob = file_get_contents($tmp);
    if (!$blob) continue;

    $origName = $_FILES["media"]["name"][$i];
    $mime = $_FILES["media"]["type"][$i];

    // Detect media type
    $mediaType = "file";
    if (strpos($mime, "image") !== false) $mediaType = "image";
    if (strpos($mime, "video") !== false) $mediaType = "video";

    // Process thumbnails for images only
    $thumbBlob = null;

    if ($mediaType === "image" && extension_loaded("imagick")) {

        try {
            $img = new Imagick();
            $img->readImageBlob($blob);
            $img->setImageFormat("jpeg");
            $img->thumbnailImage(350, 350, true);
            $thumbBlob = $img->getImageBlob();
            $img->destroy();
        } catch (Exception $e) {
            $thumbBlob = null;
        }
    }

    // Insert into chat_media (BYTEA)
    $save = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, thumb_blob, created_at)
        VALUES ($1, $2, $3, $4, $5, NOW())
    ");
    $save->bindValue(1, $chatId, PDO::PARAM_INT);
    $save->bindValue(2, $origName, PDO::PARAM_STR);
    $save->bindValue(3, $mediaType, PDO::PARAM_STR);
    $save->bindValue(4, $blob, PDO::PARAM_LOB);
    $save->bindValue(5, $thumbBlob, PDO::PARAM_LOB);

    $save->execute();
}

echo json_encode([
    "status" => "ok",
    "msg"    => "media uploaded",
    "chat_id" => $chatId
]);
