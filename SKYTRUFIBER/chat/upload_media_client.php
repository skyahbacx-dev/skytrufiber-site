<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

ini_set("display_errors",1);
error_reporting(E_ALL);

$username = $_POST["username"] ?? null;
$message  = trim($_POST["message"] ?? "");

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "Missing username"]);
    exit;
}

// Identify user
$stmt = $conn->prepare("
    SELECT id FROM users
    WHERE email = ? OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status"=>"error","msg"=>"User not found"]);
    exit;
}

$client_id = (int)$user["id"];

// Insert empty or text message
$ins = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$ins->execute([$client_id, $message]);

$chatID = $conn->lastInsertId();

// ============================
// PROCESS FILES
// ============================
if (!empty($_FILES["media"]["name"])) {

    foreach ($_FILES["media"]["name"] as $i => $name) {
        $tmp  = $_FILES["media"]["tmp_name"][$i];
        $type = $_FILES["media"]["type"][$i];

        if (!file_exists($tmp)) continue;

        $blob = file_get_contents($tmp);
        if (!$blob) continue;

        // Media type
        $mediaType = "file";
        if (strpos($type, "image") !== false) $mediaType = "image";
        if (strpos($type, "video") !== false) $mediaType = "video";

        $thumbBlob = null;

        // Generate thumbnail for images
        if ($mediaType === "image" && class_exists("Imagick")) {
            try {
                $img = new Imagick();
                $img->readImageBlob($blob);
                $img->thumbnailImage(250, 250, true);
                $thumbBlob = $img->getImageBlob();
                $img->clear();
                $img->destroy();
            } catch (Exception $e) {}
        }

        $m = $conn->prepare("
            INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, thumb_blob, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $m->execute([$chatID, $name, $mediaType, $blob, $thumbBlob]);
    }
}

echo json_encode([
    "status" => "ok",
    "chat_id" => $chatID,
    "msg" => "Uploaded"
]);
exit;
