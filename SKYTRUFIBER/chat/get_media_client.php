<?php
require_once "../../db_connect.php";

$id = (int)($_GET["id"] ?? 0);
$thumbReq = isset($_GET["thumb"]);

// Load row
$stmt = $conn->prepare("
    SELECT media_type, media_blob, thumb_blob
    FROM chat_media
    WHERE id = ?
");
$stmt->execute([$id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) exit("");

// If thumbnail requested and exists
if ($thumbReq && !empty($media["thumb_blob"])) {
    header("Content-Type: image/jpeg");
    echo $media["thumb_blob"];
    exit;
}

// MAIN FILE
$type = $media["media_type"];

if ($type === "image") header("Content-Type: image/jpeg");
else if ($type === "video") header("Content-Type: video/mp4");
else header("Content-Type: application/octet-stream");

echo $media["media_blob"];
?>
