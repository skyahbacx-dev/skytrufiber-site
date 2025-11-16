<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

if (!isset($_POST["client_id"])) {
    echo json_encode(["status" => "error", "msg" => "No client ID"]);
    exit;
}

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? "");
$client_id    = (int)$_POST["client_id"];
$csr_user     = $_POST["csr_user"] ?? "";
$csr_fullname = $_POST["csr_fullname"] ?? "";

$uploaded_paths = [];

// MULTIPLE FILE UPLOAD SUPPORT
if (!empty($_FILES["files"]["name"][0])) {
    $count = count($_FILES["files"]["name"]);

    for ($i = 0; $i < $count; $i++) {
        $ext = strtolower(pathinfo($_FILES["files"]["name"][$i], PATHINFO_EXTENSION));

        if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
            $media_type = "image";
            $folder = "upload/chat/";
        } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
            $media_type = "video";
            $folder = "upload/chat/";
        } else { continue; }

        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $relativePath = $folder . $newName;
        $absolutePath = "../" . $relativePath;

        move_uploaded_file($_FILES["files"]["tmp_name"][$i], $absolutePath);

        // Insert each media item as a separate message row
        $stmt = $conn->prepare("
            INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
            VALUES (:cid, 'csr', NULL, :path, :type, :csr, NOW())
        ");
        $stmt->execute([
            ":cid" => $client_id,
            ":path" => $relativePath,
            ":type" => $media_type,
            ":csr"  => $csr_fullname
        ]);
    }
}

// Insert text message if exists
if ($message !== "") {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
        VALUES (:cid, 'csr', :msg, :csr, NOW())
    ");
    $stmt->execute([
        ":cid" => $client_id,
        ":msg" => $message,
        ":csr" => $csr_fullname
    ]);
}

echo json_encode(["status" => "ok"]);
?>
