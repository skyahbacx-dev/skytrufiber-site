<?php
session_start();
include '../db_connect.php';
include '../b2_upload.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$username = $_POST["username"] ?? '';
$message  = trim($_POST["message"] ?? '');
$sender_type = "client";

if (!$username) {
    echo json_encode(["status"=>"error","msg"=>"missing username"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :n LIMIT 1");
$stmt->execute([":n"=>$username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status"=>"error","msg"=>"client not found"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES["file"]["tmp_name"])) {
    $tmp  = $_FILES["file"]["tmp_name"];
    $name = basename($_FILES["file"]["name"]);
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $media_type = in_array($ext, ["jpg","jpeg","png","gif","webp"]) ? "image" : "video";
    $newName    = time() . "_" . rand(1000,9999) . "." . $ext;

    // Upload to B2
    $url = b2_upload($tmp, $newName);
    if ($url) $media_path = $url;
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, NOW())
");

$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":mp"    => $media_path,
    ":mt"    => $media_type
]);

echo json_encode(["status"=>"ok"]);
?>
