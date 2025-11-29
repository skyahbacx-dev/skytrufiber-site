<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit;

$stmt = $conn->prepare("SELECT media_blob, media_type FROM chat_media WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Missing file");

if ($file["media_type"] === "image") {
    header("Content-Type: image/jpeg");
} elseif ($file["media_type"] === "video") {
    header("Content-Type: video/mp4");
} else {
    header("Content-Type: application/octet-stream");
}

echo $file["media_blob"];
exit;
