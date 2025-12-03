<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

// Validate username
if (!$username) {
    exit(json_encode(["status" => "error", "msg" => "missing username"]));
}

// Lookup user
$stmt = $conn->prepare("
    SELECT id 
    FROM users
    WHERE email = ? COLLATE utf8mb4_general_ci
       OR full_name = ? COLLATE utf8mb4_general_ci
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit(json_encode(["status" => "error", "msg" => "invalid user"]));
}

$client_id = (int)$user["id"];

// Create DB chat message row
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, 1, 0, NOW())
");
$insert->execute([$client_id, $message]);
$chatId = $conn->lastInsertId();


// =======================================================================
// PROCESS FILES
// =======================================================================

foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {

    if (!file_exists($tmp)) continue;

    $blob = file_get_contents($tmp);
    if (!$blob) continue;

    $mime  = $_FILES["media"]["type"][$i];   // browser MIME
    $name  = $_FILES["media"]["name"][$i];
    $size  = filesize($tmp);

    // Determine media type
    $mediaType = "file";
    if (strpos($mime, "image") !== false) $mediaType = "image";
    if (strpos($mime, "video") !== false) $mediaType = "video";

    // ===================================================================
    // IMAGE THUMBNAIL (WebP) + ROTATION FIX
    // ===================================================================
    $thumbBlob = null;

    if ($mediaType === "image" && extension_loaded("imagick")) {
        try {
            $im = new Imagick();
            $im->readImageBlob($blob);

            // Rotate according to EXIF — VERY IMPORTANT
            if (method_exists($im, "setImageOrientation")) {
                $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            }

            // Create thumbnail
            $im->thumbnailImage(300, 300, true);

            // Convert to WebP
            $im->setImageFormat("webp");
            $thumbBlob = $im->getImageBlob();

            $im->destroy();

        } catch (Exception $e) {
            // fallback: no thumbnail
            $thumbBlob = null;
        }
    }

    // ===================================================================
    // STORE FILE IN DB
    // ===================================================================
    $stmt = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, thumb_blob, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $chatId,
        $name,         // original filename
        $mediaType,    // image / video / file
        $blob,         // full BLOB
        $thumbBlob     // null or real thumbnail
    ]);
}


// =======================================================================
// FINISHED
// =======================================================================
echo json_encode([
    "status" => "ok",
    "msg"    => "media uploaded",
    "chat_id" => $chatId
]);
?>
