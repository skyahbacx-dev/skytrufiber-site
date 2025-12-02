<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing ID");

$isThumb = isset($_GET["thumb"]);

// Retrieve both blobs
$stmt = $conn->prepare("
    SELECT media_blob, thumb_blob, media_type
    FROM chat_media
    WHERE id = ?
");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$stmt->bindColumn("thumb_blob", $thumb, PDO::PARAM_LOB);

$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

// If thumbnail requested and exists, load it
if ($isThumb && !empty($thumb)) {
    $binary = is_resource($thumb) ? stream_get_contents($thumb) : $thumb;
    header("Content-Type: image/jpeg");
    header("Cache-Control: public, max-age=31536000");
    echo $binary;
    exit;
}

// Otherwise load full media
$binary = is_resource($blob) ? stream_get_contents($blob) : $blob;

// Detect MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_buffer($finfo, $binary);
finfo_close($finfo);

if (!$mime) $mime = "application/octet-stream";

header("Content-Type: $mime");
header("Cache-Control: public, max-age=31536000");
echo $binary;
exit;
?>
