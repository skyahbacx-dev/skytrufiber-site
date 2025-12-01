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

// detect real MIME from DB if available
$mime = ($type === "image") ? "image/jpeg"
      : (($type === "video") ? "video/mp4"
      : "application/octet-stream");

header("Content-Type: $mime");
header("Content-Transfer-Encoding: binary");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Ensure clean output buffer
while (ob_get_level()) {
    ob_end_clean();
}

echo pg_unescape_bytea($binary);
exit;
?>
