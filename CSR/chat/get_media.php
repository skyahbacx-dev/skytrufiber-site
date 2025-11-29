<?php
// get_media.php - Outputs BLOB media file from database

require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) {
    http_response_code(400);
    die("Missing ID");
}

$stmt = $conn->prepare("SELECT media_blob, media_type, media_path FROM chat_media WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    die("File not found");
}

$blob = $file["media_blob"];
$ext  = strtolower(pathinfo($file["media_path"], PATHINFO_EXTENSION));
$type = $file["media_type"];

// AUTO DETECT MIME TYPE
switch ($type) {
    case "image":
        switch ($ext) {
            case "png":  header("Content-Type: image/png"); break;
            case "gif":  header("Content-Type: image/gif"); break;
            case "webp": header("Content-Type: image/webp"); break;
            default:     header("Content-Type: image/jpeg");
        }
        break;

    case "video":
        header("Content-Type: video/mp4");
        header("Accept-Ranges: bytes");
        break;

    default: // generic file download
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$file[media_path]\"");
        break;
}

// Disable additional output buffering
header("Content-Length: " . strlen($blob));
echo $blob;
exit;
?>
