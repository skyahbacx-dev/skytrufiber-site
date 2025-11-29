<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) {
    http_response_code(400);
    die("Missing id");
}

$stmt = $conn->prepare("
    SELECT media_blob, media_type, media_path
    FROM chat_media
    WHERE id = ?
");
$stmt->execute([$id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    http_response_code(404);
    die("Not found");
}

// Determine mime based on file extension
$filename = $media["media_path"];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$mime = "application/octet-stream";
if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) $mime = "image/" . $ext;
if ($ext === "mp4") $mime = "video/mp4";
if ($ext === "pdf") $mime = "application/pdf";

header("Content-Type: $mime");
header("Content-Length: " . strlen($media["media_blob"]));

echo $media["media_blob"];
exit;
