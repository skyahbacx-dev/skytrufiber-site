<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing ID");

$stmt = $conn->prepare("SELECT media_blob, media_path, media_type FROM chat_media WHERE id = ?");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

// Read blob into string if streaming resource
if (is_resource($blob)) {
    $binary = stream_get_contents($blob);
} else {
    $binary = $blob;
}

// Detect MIME type from binary header
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $binary);
finfo_close($finfo);

// Default fallback
if (!$mimeType) $mimeType = "application/octet-stream";

// Send correct Content-Type
header("Content-Type: $mimeType");
header("Content-Length: " . strlen($binary));

// Output binary directly
echo $binary;
exit;
