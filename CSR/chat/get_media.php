<?php
if (!isset($_SESSION)) session_start();

// Validate request
$file = $_GET["file"] ?? null;
if (!$file) {
    http_response_code(400);
    die("Missing file reference");
}

// Always load from Railway `/tmp/chat_media/`
$storageDirectory = "/tmp/chat_media/";

// Clean filename
$cleanName = basename($file);        // Remove directory traversal
$fullPath  = $storageDirectory . $cleanName;

if (!file_exists($fullPath)) {
    http_response_code(404);
    die("File not found");
}

// Detect file MIME
$mime = mime_content_type($fullPath);
header("Content-Type: $mime");
header("Content-Length: " . filesize($fullPath));

// Support range streaming for video
if (strpos($mime, "video") !== false) {
    header("Accept-Ranges: bytes");
}

// For non-video/doc files, force download
if (strpos($mime, "application") !== false && !strpos($mime, "pdf")) {
    header("Content-Disposition: attachment; filename=\"" . $cleanName . "\"");
}

readfile($fullPath);
exit;
?>
