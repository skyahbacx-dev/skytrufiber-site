<?php
$file = $_GET["file"] ?? null;
$path = "/tmp/chat_media/" . basename($file);

if (!file_exists($path)) {
    http_response_code(404);
    die("File not found");
}

$mime = mime_content_type($path);
header("Content-Type: $mime");
readfile($path);
exit;
?>
