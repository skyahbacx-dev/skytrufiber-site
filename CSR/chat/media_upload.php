<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;

if (!$csrUser || !$clientID || empty($_FILES["media"])) {
    echo json_encode(["status" => "error", "msg" => "missing data"]);
    exit;
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/upload/chat_media/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES["media"];
$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$allowed = ["jpg","jpeg","png","gif","pdf","docx","xlsx","mp4"];

if (!in_array($ext, $allowed)) {
    echo json_encode(["status"=>"error","msg"=>"invalid file"]);
    exit;
}

$filename = time() . "_" . rand(1000,9999) . "." . $ext;
$path = $uploadDir . $filename;

if (!move_uploaded_file($file["tmp_name"], $path)) {
    echo json_encode(["status"=>"error","msg"=>"upload failed"]);
    exit;
}

$urlPath = "upload/chat_media/" . $filename; // stored in DB

try {
    $stmt = $conn->prepare("
        INSERT INTO chat_media (client_id, media_path, media_type, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$clientID, $urlPath, (str_contains($ext,'jpg')||str_contains($ext,'png')) ? 'image':'file']);

    echo json_encode(["status"=>"ok"]);
} catch (PDOException $e) {
    echo json_encode(["status"=>"error","msg"=>$e->getMessage()]);
}
?>
