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

if (empty($_FILES["media"]["name"])) {
    echo json_encode(["status" => "error", "msg" => "No files received"]);
    exit;
}

// Create container message row
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', '', TRUE, FALSE, NOW())
");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

// Public directory
$uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/upload/chat_media/";
$thumbDir  = $_SERVER["DOCUMENT_ROOT"] . "/upload/chat_media/thumbs/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

foreach ($_FILES["media"]["name"] as $i => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$i];
    $fileType = $_FILES["media"]["type"][$i];
    $fileName = round(microtime(true) * 1000) . "_" . preg_replace("/\s+/", "_", $name);

    $targetFile = $uploadDir . $fileName;
    $thumbFile  = $thumbDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetFile)) continue;

    // Generate thumbnail if image
    if (strpos($fileType, "image") !== false) {
        createThumbnail($targetFile, $thumbFile, 420); // max width 420px
        $type = "image";
    } elseif (strpos($fileType, "video") !== false) {
        $type = "video";
    } else {
        $type = "file";
    }

    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type)
        VALUES (?, ?, ?)
    ");
    $mediaInsert->execute([$chatId, $fileName, $type]);
}

// Success
echo json_encode(["status" => "ok"]);

// THUMBNAIL CREATOR
function createThumbnail($src, $dest, $targetWidth) {
    $info = getimagesize($src);
    if (!$info) return;

    list($width, $height) = $info;
    $ratio = $height / $width;
    $newHeight = $targetWidth * $ratio;

    $thumb = imagecreatetruecolor($targetWidth, $newHeight);

    switch ($info['mime']) {
        case 'image/jpeg': $source = imagecreatefromjpeg($src); break;
        case 'image/png':  $source = imagecreatefrompng($src);  break;
        case 'image/webp': $source = imagecreatefromwebp($src); break;
        default: return;
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0,
        $targetWidth, $newHeight, $width, $height);

    imagejpeg($thumb, $dest, 75); // compressed
}
?>
