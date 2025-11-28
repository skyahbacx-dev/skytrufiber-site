<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$uploadDirectory = $_SERVER['DOCUMENT_ROOT'] . "/upload/chat_media/";
$client_id = $_POST["client_id"] ?? null;
$sender = $_SESSION["csr_user"] ?? null;

$response = [
    "debug_path" => $uploadDirectory,
    "document_root" => $_SERVER['DOCUMENT_ROOT'],
    "post" => $_POST,
    "files" => $_FILES,
];

if (!$client_id || !$sender) {
    echo json_encode(["status" => "error", "msg" => "Missing data", "debug" => $response]);
    exit;
}

if (!isset($_FILES["media"])) {
    echo json_encode(["status" => "error", "msg" => "No file", "debug" => $response]);
    exit;
}

$file = $_FILES["media"];

if (!file_exists($uploadDirectory)) {
    $mk = mkdir($uploadDirectory, 0777, true);
    $response["mkdir"] = $mk ? "created" : "failed";
}

$fileName = time() . "_" . basename($file["name"]);
$targetPath = $uploadDirectory . $fileName;
$response["target_path"] = $targetPath;

// Try the upload
$move = move_uploaded_file($file["tmp_name"], $targetPath);
$response["move_result"] = $move ? "success" : "failed";
$response["error_code"] = $file["error"];

if (!$move) {
    echo json_encode(["status" => "error", "msg" => "Move failed", "debug" => $response]);
    exit;
}

echo json_encode(["status" => "ok", "debug" => $response]);
exit;
