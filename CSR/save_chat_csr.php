<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message     = trim($_POST["message"] ?? '');
$client_id   = $_POST["client_id"] ?? 0;
$csr_fullname = $_POST["csr_fullname"] ?? "CSR";

if (!$client_id) { echo json_encode(["status"=>"error"]); exit; }

$media_path = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["files"]["name"]); $i++) {
        $ext = strtolower(pathinfo($_FILES["files"]["name"][$i], PATHINFO_EXTENSION));

        if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
            $media_type = "image";
            $folder = "../upload/chat_images/";
        }
        elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
            $media_type = "video";
            $folder = "../upload/chat_videos/";
        }

        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $path = $folder . $newName;

        move_uploaded_file($_FILES["files"]["tmp_name"][$i], $path);

        // Correct public path
        $media_path = "/CSR/upload/" . ($media_type === "image" ? "chat_images/" : "chat_videos/") . $newName;

        $stmt = $conn->prepare("
            INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
            VALUES (:cid, 'csr', :msg, :mp, :mt, :csr, NOW())
        ");
        $stmt->execute([
            ":cid" => $client_id,
            ":msg" => $message,
            ":mp"  => $media_path,
            ":mt"  => $media_type,
            ":csr" => $csr_fullname
        ]);
    }

} else {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, created_at)
        VALUES (:cid, 'csr', :msg, NOW())
    ");
    $stmt->execute([
        ":cid" => $client_id,
        ":msg" => $message
    ]);
}

echo json_encode(["status"=>"ok"]);
?>
