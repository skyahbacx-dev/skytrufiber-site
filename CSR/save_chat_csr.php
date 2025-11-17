<?php
session_start();
include '../db_connect.php';

$csr = $_POST["csr_fullname"] ?? "";
$client_id = $_POST["client_id"] ?? 0;
$message = trim($_POST["message"] ?? "");
$sender_type = "csr";

$media_path = null;
$media_type = null;

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $i => $filename) {

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $media_type = "image";
            $folder = "../upload/chat_images/";
        } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
            $media_type = "video";
            $folder = "../upload/chat_videos/";
        }

        if ($media_type) {
            $newName = time() . "_" . rand(1000,9999) . "." . $ext;
            $fullPath = $folder . $newName;
            move_uploaded_file($_FILES['files']['tmp_name'][$i], $fullPath);

            // Path accessible by browser
            $media_path = "CSR/upload/" . ($media_type === "image" ? "chat_images/" : "chat_videos/") . $newName;

            $stmt = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, message, media_path, media_type, created_at)
                VALUES (:cid, :s, '', :mp, :mt, NOW())
            ");
            $stmt->execute([
                ":cid" => $client_id,
                ":s"   => $sender_type,
                ":mp"  => $media_path,
                ":mt"  => $media_type
            ]);
        }
    }
}

// normal message
if ($message !== "") {
    $stmt2 = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, created_at)
        VALUES (:cid, :s, :msg, NOW())
    ");
    $stmt2->execute([
        ":cid" => $client_id,
        ":s"   => $sender_type,
        ":msg" => $message
    ]);
}

echo json_encode(["status" => "ok"]);
?>
