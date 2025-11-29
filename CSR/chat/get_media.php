<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing ID");

$stmt = $conn->prepare("
    SELECT media_blob, media_path
    FROM chat_media
    WHERE id = ?
");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

// Determine file MIME based on extension
$ext = strtolower(pathinfo($file["media_path"], PATHINFO_EXTENSION));

switch ($ext) {
    case "png":  header("Content-Type: image/png"); break;
    case "jpg":
    case "jpeg": header("Content-Type: image/jpeg"); break;
    case "gif":  header("Content-Type: image/gif"); break;
    case "mp4":  header("Content-Type: video/mp4"); break;
    default:     header("Content-Type: application/octet-stream");
}

// Convert bytea hex → binary stream
$binary = hex2bin(substr($file["media_blob"], 2));  // remove "\x"

echo $binary;
exit;
