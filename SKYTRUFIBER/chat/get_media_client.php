<?php
require_once "../../db_connect.php";

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) exit("Bad ID");

$stmt = $conn->prepare("
    SELECT media_blob, media_type
    FROM chat_media
    WHERE id = ?
");
$stmt->execute([$id]);

$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

$binary = is_resource($blob) ? stream_get_contents($blob) : $blob;

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_buffer($finfo, $binary);
finfo_close($finfo);

if (!$mime) $mime = "application/octet-stream";

// Allow browser caching — improves reload speed
header("Content-Type: $mime");
header("Cache-Control: public, max-age=604800");
header("Content-Length: " . strlen($binary));

echo $binary;
exit;
?>
