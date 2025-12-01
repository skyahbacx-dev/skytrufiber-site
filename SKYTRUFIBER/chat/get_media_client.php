<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing file ID");

$stmt = $conn->prepare("
    SELECT media_blob, media_type
    FROM chat_media
    WHERE id = ?
");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("File not found");

$binary = $file["media_blob"];
$type = $file["media_type"];

// Set correct content headers
switch ($type) {
    case "image":
        header("Content-Type: image/jpeg");
        header("Content-Disposition: inline");
        break;

    case "video":
        header("Content-Type: video/mp4");
        header("Content-Disposition: inline");
        header("Accept-Ranges: bytes");
        break;

    default:
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment");
        break;
}

// Output raw binary safely
echo $binary;
exit;
?>
