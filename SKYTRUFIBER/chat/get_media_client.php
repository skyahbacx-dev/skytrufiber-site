<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing ID");

$stmt = $conn->prepare("SELECT media_blob, media_path, media_type FROM chat_media WHERE id = ?");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

// Bind BLOB column as stream
$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

// Convert BLOB resource into binary data
if (is_resource($blob)) {
    $binary = stream_get_contents($blob);
} else {
    $binary = $blob;
}

// Detect MIME type dynamically
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $binary);
finfo_close($finfo);

if (!$mimeType) $mimeType = "application/octet-stream";

// Correct headers
header("Content-Type: $mimeType");
header("Content-Length: " . strlen($binary));
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Output file
echo $binary;
exit;
?>
