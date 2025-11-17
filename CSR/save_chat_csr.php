<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message  = trim($_POST["message"] ?? "");
$client_id = $_POST["client_id"] ?? 0;
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client"]);
    exit;
}

// Insert text message first (if exists)
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

// Handle multiple uploaded files
if (!empty($_FILES["files"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["files"]["name"]); $i++) {

        $fileName = $_FILES["files"]["name"][$i];
        $tmpName  = $_FILES["files"]["tmp_name"][$i];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $folder = "";
        $media_type = "";

        if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
            $folder = "../CSR/upload/chat/";
            $media_type = "image";
        } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
            $folder = "../CSR/upload/chat/";
            $media_type = "video";
        }

        if ($media_type) {
            $newName = time() . "_" . rand(1000,9999) . "." . $ext;
            $finalPath = $folder . $newName;
            move_uploaded_file($tmpName, $finalPath);

            $dbPath = "CSR/upload/chat/" . $newName;

            $ins = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, media_path, media_type, csr_fullname, created_at)
                VALUES (:cid, 'csr', :mp, :mt, :csr, NOW())
            ");
            $ins->execute([
                ":cid" => $client_id,
                ":mp"  => $dbPath,
                ":mt"  => $media_type,
                ":csr" => $csr_fullname
            ]);
        }
    }
}

echo json_encode(["status" => "ok"]);
exit;
?>
