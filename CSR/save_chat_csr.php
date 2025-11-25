<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$csr_user  = $_SESSION["csr_user"] ?? null;
$message   = trim($_POST["message"] ?? "");
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid CSR Session"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO chat (user_id, sender_type, message, created_at, delivered)
    VALUES (:cid, 'csr', :msg, NOW(), true)
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message
]);

$chat_id = $conn->lastInsertId();

if (!empty($_FILES["media"]["name"][0])) {
    foreach ($_FILES["media"]["tmp_name"] as $i => $tmpFile) {
        if (!$tmpFile) continue;

        $original = $_FILES["media"]["name"][$i];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;

        $url = b2_upload($tmpFile, $newName);

        if ($url) {
            $media_type = in_array($ext, ["jpg","jpeg","png","gif","webp"])
                ? "image"
                : (in_array($ext, ["mp4","mov","avi","mkv","webm"]) ? "video" : null);

            $conn->prepare("
                INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
                VALUES (:chat, :path, :type, NOW())
            ")->execute([
                ":chat" => $chat_id,
                ":path" => $url,
                ":type" => $media_type
            ]);
        }
    }
}

echo json_encode(["status" => "ok"]);
exit;
