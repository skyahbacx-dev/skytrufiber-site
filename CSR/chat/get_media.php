<?php
if (!isset($_SESSION)) session_start();

// Validate request
$file = $_GET["file"] ?? null;
if (!$file) {
    http_response_code(400);
    die("Missing file reference");
}

$cleanPath = ltrim($file, "/"); // Remove leading slash if present
$storageDirectory = $_SERVER["DOCUMENT_ROOT"] . "/tmp/chat_media/";
$fullPath = $storageDirectory . basename($cleanPath);

if (!file_exists($fullPath)) {
    http_response_code(404);
    die("File not found");
}

// Detect file MIME
$mime = mime_content_type($fullPath);
header("Content-Type: $mime");
header("Content-Length: " . filesize($fullPath));

// Support video playback streaming
if (strpos($mime, "video") !== false) {
    header("Accept-Ranges: bytes");
}

// Display or download
if (strpos($mime, "application") !== false && !strpos($mime, "pdf")) {
    header("Content-Disposition: attachment; filename=\"" . basename($fullPath) . "\"");
}

readfile($fullPath);
exit;
?>
