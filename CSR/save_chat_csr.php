<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message      = trim($_POST["message"] ?? "");
$client_id    = $_POST["client_id"] ?? 0;
$csrUser      = $_SESSION["csr_user"] ?? "";
$csrFullName  = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client ID"]);
    exit;
}

if ($message === "" && empty($_FILES["files"]["name"][0])) {
    echo json_encode(["status" => "empty"]);
    exit;
}

/* INSERT TEXT MESSAGE IF EXISTS */
if ($message !== "") {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
        VALUES (:cid, 'csr', :msg, :csr, NOW())
    ");
    $stmt->execute([
        ":cid" => $client_id,
        ":msg" => $message,
        ":csr" => $csrFullName
    ]);
}

/* PROCESS MULTIPLE FILE UPLOADS */
if (!empty($_FILES["files"]["name"][0])) {

    foreach ($_FILES["files"]["name"] as $i => $name) {

        $tmpPath = $_FILES["files"]["tmp_name"][$i];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $media_type = null;
        $folder = null;

        if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) {
            $media_type = "image";
            $folder = "../uploads/chat_images/";
        } elseif (in_array($ext, ["mp4", "mov", "avi", "mkv", "webm"])) {
            $media_type = "video";
            $folder = "../uploads/chat_videos/";
        }

        if ($media_type) {
            $newName = time() . "_" . rand(1000, 9999) . "." . $ext;
            $savePath = $folder . $newName;

            if (move_uploaded_file($tmpPath, $savePath)) {
                $dbPath = substr($savePath, 3); // remove ../

                $stmt2 = $conn->prepare("
                    INSERT INTO chat (client_id, sender_type, media_path, media_type, csr_fullname, created_at)
                    VALUES (:cid, 'csr', :mp, :mt, :csr, NOW())
                ");
                $stmt2->execute([
                    ":cid" => $client_id,
                    ":mp"  => $dbPath,
                    ":mt"  => $media_type,
                    ":csr" => $csrFullName
                ]);
            }
        }
    }
}

echo json_encode(["status" => "ok"]);
exit;
?>
