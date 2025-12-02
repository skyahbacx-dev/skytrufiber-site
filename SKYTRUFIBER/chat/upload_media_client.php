<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

// Increase limits to avoid upload 502 errors
ini_set("upload_max_filesize", "50M");
ini_set("post_max_size", "50M");
ini_set("memory_limit", "512M");
ini_set("max_execution_time", 300);

$username = $_POST["username"] ?? null;
$message  = trim($_POST["message"] ?? "");

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "Missing username"]);
    exit;
}

// Accept email OR full name
$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ? OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo json_encode(["status" => "error", "msg" => "Invalid user"]);
    exit;
}

$client_id = (int)$client["id"];

// Insert Chat Message (sender: client)
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);
$chatId = $conn->lastInsertId();

// ============================
// PROCESS ATTACHED MEDIA
// ============================
if (!empty($_FILES["media"]["name"])) {

    foreach ($_FILES["media"]["name"] as $i => $name) {

        $tmp  = $_FILES["media"]["tmp_name"][$i];
        $type = $_FILES["media"]["type"][$i];

        if (!file_exists($tmp)) continue;

        $blob = file_get_contents($tmp);
        if (!$blob) continue;

        // Determine media type
        $mediaType = "file";
        if (strpos($type, "image") !== false) $mediaType = "image";
        elseif (strpos($type, "video") !== false) $mediaType = "video";

        // Generate thumbnail (image only)
        $thumbBlob = null;
        if ($mediaType === "image") {
            try {
                if (class_exists("Imagick")) {
                    $img = new Imagick();
                    $img->readImageBlob($blob);
                    $img->thumbnailImage(250, 250, true);
                    $thumbBlob = $img->getImageBlob();
                    $img->clear();
                    $img->destroy();
                }
            } catch (Exception $e) {
                $thumbBlob = null; // fails safely
            }
        }

        // Store media & thumbnail
        $m = $conn->prepare("
            INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, thumb_blob, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $m->bindValue(1, $chatId, PDO::PARAM_INT);
        $m->bindValue(2, $name, PDO::PARAM_STR);
        $m->bindValue(3, $mediaType, PDO::PARAM_STR);
        $m->bindValue(4, $blob, PDO::PARAM_LOB);
        $m->bindValue(5, $thumbBlob, PDO::PARAM_LOB);
        $m->execute();
    }
}

// Successful response
echo json_encode([
    "status" => "ok",
    "msg" => "Message delivered",
    "chat_id" => $chatId
]);

exit;
?>
