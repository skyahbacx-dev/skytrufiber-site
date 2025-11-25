<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$user_id = $_SESSION["user"] ?? null;
$message = trim($_POST["message"] ?? "");

if (!$user_id) {
    echo json_encode(["status" => "error", "msg" => "Not logged in"]);
    exit;
}

/* GET CLIENT ID */
$stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "msg" => "User not found"]);
    exit;
}

$client_id = $user["id"];
$media_path = null;
$media_type = null;

/* FILE UPLOAD HANDLER */
if (!empty($_FILES["file"]["tmp_name"])) {
    $tmp  = $_FILES["file"]["tmp_name"];
    $name = time() . "_" . preg_replace("/[^A-Za-z0-9.]/", "_", $_FILES["file"]["name"]);

    $url = b2_upload($tmp, $name);

    if ($url) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $media_type = in_array($ext, ["jpg","jpeg","png","gif","webp"]) ? "image" : "video";
        $media_path = $url;
    }
}

/* INSERT MESSAGE */
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at)
    VALUES (:cid, 'client', :msg, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message
]);

$chat_id = $conn->lastInsertId();

/* INSERT MEDIA IF EXISTS */
if ($media_path) {
    $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
        VALUES (:chat, :path, :type, NOW())
    ")->execute([
        ":chat" => $chat_id,
        ":path" => $media_path,
        ":type" => $media_type
    ]);
}

echo json_encode(["status" => "ok"]);
exit;
?>
