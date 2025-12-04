<?php
require_once "../../db_connect.php";

$id = (int)($_GET["id"] ?? 0);
$thumb = isset($_GET["thumb"]);

if ($id <= 0) exit("bad id");

$stmt = $conn->prepare("
    SELECT media_blob, thumb_blob, media_type
    FROM chat_media
    WHERE id = ?
");
$stmt->execute([$id]);

$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$stmt->bindColumn("thumb_blob", $thumbBlob, PDO::PARAM_LOB);

$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) exit("missing file");

$data = $thumb && !empty($thumbBlob) ? $thumbBlob : $blob;

$binary = is_resource($data) ? stream_get_contents($data) : $data;

// detect mime
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_buffer($finfo, $binary);
finfo_close($finfo);

header("Content-Type: $mime");
header("Cache-Control: public, max-age=604800");
echo $binary;
