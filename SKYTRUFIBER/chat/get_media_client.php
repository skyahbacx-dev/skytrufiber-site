<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing file ID");

$stmt = $conn->prepare("
    SELECT media_blob, media_type, media_path
    FROM chat_media
    WHERE id = ?
");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("File not found");

$binary = $file["media_blob"];
$type = $file["media_type"];
$path = $file["media_path"];

// Detect MIME based on stored filename extension
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

switch ($ext) {
    case "jpg":
    case "jpeg":
        header("Content-Type: image/jpeg");
        break;
    case "png":
        header("Content-Type: image/png");
        break;
    case "gif":
        header("Content-Type: image/gif");
        break;
    case "mp4":
        header("Content-Type: video/mp4");
        header("Accept-Ranges: bytes");
        break;
    default:
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$path\"");
}

// required for binary response
header("Content-Length: " . strlen($binary));

// Output raw blob
echo $binary;
exit;
?>
