<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

// --------------------------------------
// Validate & sanitize input
// --------------------------------------
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$thumb = isset($_GET["thumb"]) ? intval($_GET["thumb"]) : 0;

if ($id <= 0) {
    http_response_code(400);
    exit("Invalid ID");
}

// --------------------------------------
// Fetch media row
// --------------------------------------
$stmt = $conn->prepare("
    SELECT media_type, media_blob, thumb_blob
    FROM chat_media
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    http_response_code(404);
    exit("Media not found");
}

$type = $media["media_type"];
$blob = ($thumb && !empty($media["thumb_blob"]))
        ? $media["thumb_blob"]
        : $media["media_blob"];

if (!$blob) {
    http_response_code(404);
    exit("No data");
}

// --------------------------------------
// Set correct MIME type
// --------------------------------------
$mime = "application/octet-stream";

if ($type === "image") {
    $mime = "image/jpeg";
}
elseif ($type === "video") {
    $mime = "video/mp4";
}
else {
    $mime = "application/octet-stream";
}

header("Content-Type: $mime");
header("Content-Length: " . strlen($blob));
header("Cache-Control: public, max-age=86400");

// --------------------------------------
// Output the media
// --------------------------------------
echo $blob;
exit;
