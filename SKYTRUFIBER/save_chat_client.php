<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php"; // located outside both folders
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$username   = $_POST["username"] ?? "";
$message    = trim($_POST["message"] ?? "");
$firstSend  = $_POST["first_message"] ?? "no";  // register/concern submission caller

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "No username provided"]);
    exit;
}

/* FIND CLIENT ID */
$stmt = $conn->prepare("SELECT id FROM users WHERE full_name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$user_id = $stmt->fetchColumn();

if (!$user_id) {
    echo json_encode(["status" => "error", "msg" => "User not found"]);
    exit;
}

/* INSERT PRIMARY CHAT MESSAGE */
$stmt = $conn->prepare("
    INSERT INTO chat (user_id, sender_type, message, created_at)
    VALUES (:uid, 'client', :msg, NOW())
");
$stmt->execute([
    ":uid" => $user_id,
    ":msg" => $message
]);

$chat_id = $conn->lastInsertId();

/* FILE UPLOAD HANDLING */
if (!empty($_FILES['media']['name'][0])) {
    foreach ($_FILES['media']['tmp_name'] as $i => $tmpFile) {

        $originalName = $_FILES["media"]["name"][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;

        /* Upload to B2 cloud */
        $b2url = b2_upload($tmpFile, $newName);

        /* Upload locally */
        $localPath = "../upload/" . $newName;
        move_uploaded_file($tmpFile, $localPath);

        $media_type = in_array($ext, ["jpg","jpeg","png","gif","webp"])
                      ? "image"
                      : (in_array($ext, ["mp4","mov","avi","mkv","webm"]) ? "video" : null);

        if ($b2url) {
            $stmt2 = $conn->prepare("
                INSERT INTO chat_media (chat_id, media_path, media_path_local, media_type, created_at)
                VALUES (:chat, :path, :local, :type, NOW())
            ");
            $stmt2->execute([
                ":chat"  => $chat_id,
                ":path"  => $b2url,
                ":local" => $localPath,
                ":type"  => $media_type
            ]);
        }
    }
}

/* Mark delivered */
$conn->prepare("UPDATE chat SET delivered = true WHERE id = :id")
     ->execute([":id" => $chat_id]);

echo json_encode(["status" => "ok", "msg" => "Message sent"]);
exit;
