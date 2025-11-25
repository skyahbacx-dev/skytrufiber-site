<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";
header("Content-Type: application/json");

$username = $_SESSION["name"] ?? "";
$message  = trim($_POST["message"] ?? "");

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "No username"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Client not found"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES['media']['tmp_name'])) {
    $tmp  = $_FILES['media']['tmp_name'];
    $name = time() . "_" . $_FILES['media']['name'];

    $url = b2_upload($tmp, $name);

    if ($url) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $media_type = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'image' : 'video';
        $media_path = $url;
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at)
    VALUES (:cid, 'client', :msg, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message
]);

$chat_id = $conn->lastInsertId();

if ($media_path) {
    $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
        VALUES (:cid, :mp, :mt, NOW())
    ")->execute([
        ":cid" => $chat_id,
        ":mp"  => $media_path,
        ":mt"  => $media_type
    ]);
}

$conn->prepare("UPDATE chat SET delivered = true WHERE id = :id")
    ->execute([":id" => $chat_id]);

echo json_encode(["status" => "ok"]);
exit;
