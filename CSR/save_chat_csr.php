<?php
session_start();
include "../db_connect.php";
include "../b2_upload.php";
header("Content-Type: application/json");

$csr_user = $_SESSION["csr_user"] ?? null;
$message = trim($_POST["message"] ?? "");
$user_id = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$user_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO chat (user_id, sender_type, message, created_at, delivered, seen)
    VALUES (:uid, 'csr', :msg, NOW(), true, false)
");
$stmt->execute([":uid" => $user_id, ":msg" => $message]);

$chat_id = $conn->lastInsertId();

/* MEDIA UPLOADS */
if (!empty($_FILES["media"]["name"][0])) {
    foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {
        if (!$tmp) continue;

        $ext = strtolower(pathinfo($_FILES["media"]["name"][$i], PATHINFO_EXTENSION));
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;

        $url = b2_upload($tmp, $newName);

        if ($url) {
            $media_type = in_array($ext, ["jpg","jpeg","png","gif","webp"])
                ? "image"
                : "video";

            $conn->prepare("
                INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
                VALUES (:cid, :path, :type, NOW())
            ")->execute([
                ":cid" => $chat_id,
                ":path" => $url,
                ":type" => $media_type
            ]);
        }
    }
}

echo json_encode(["status" => "ok"]);
exit;
?>
