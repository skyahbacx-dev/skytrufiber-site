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

// Clean filename and enforce local file read only
$cleanName = basename($file);
$fullPath  = $storageDirectory . $cleanName;

if (!file_exists($fullPath)) {
    http_response_code(404);
    die("File not found");
}

// Detect file MIME type
$mime = mime_content_type($fullPath);
$size = filesize($fullPath);

header("Content-Type: $mime");

// ===============================
// STREAM VIDEO WITH RANGE SUPPORT
// ===============================
if (strpos($mime, "video") !== false) {

    header("Accept-Ranges: bytes");
    $range = $_SERVER['HTTP_RANGE'] ?? null;

    if ($range) {
        list(, $range) = explode("=", $range);
        list($start, $end) = explode("-", $range);

        $start = intval($start);
        $end   = ($end === "") ? ($size - 1) : intval($end);
        $length = ($end - $start) + 1;

        header("HTTP/1.1 206 Partial Content");
        header("Content-Length: $length");
        header("Content-Range: bytes $start-$end/$size");

        $handle = fopen($fullPath, "rb");
        fseek($handle, $start);
        echo fread($handle, $length);
        fclose($handle);
        exit;
    }

    // If no byte range requested, serve entire file
    header("Content-Length: $size");
    readfile($fullPath);
    exit;
}

// ===============================
// PDFs inline and readable online
// ===============================
if ($mime === "application/pdf") {
    header("Content-Length: $size");
    readfile($fullPath);
    exit;
}

// ===============================
// Other application files -> download
// ===============================
if (strpos($mime, "application") !== false) {
    header("Content-Disposition: attachment; filename=\"$cleanName\"");
    header("Content-Length: $size");
    readfile($fullPath);
    exit;
}

// ===============================
// IMAGES or Others => inline render
// ===============================
header("Content-Length: $size");
readfile($fullPath);
exit;
?>
