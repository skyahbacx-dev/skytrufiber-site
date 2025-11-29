<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit;

$stmt = $conn->prepare("SELECT media_blob, media_type FROM chat_media WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("File missing");

header("Content-Type: " . ($file["media_type"] === "image" ? "image/jpeg" :
                           ($file["media_type"] === "video" ? "video/mp4" : "application/octet-stream")));
echo $file["media_blob"];
exit;
?>
