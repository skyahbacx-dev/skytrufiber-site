<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message = trim($_POST["message"] ?? '');
$client_id = $_POST["client_id"] ?? 0;
$csr_fullname = $_SESSION["csr_fullname"] ?? "CSR";

if (!$client_id) { echo json_encode(["status" => "error"]); exit; }

$media_path = null;
$media_type = null;

/* MULTIPLE FILES */
if (!empty($_FILES["files"]["name"][0])) {
    foreach ($_FILES["files"]["name"] as $index => $fileName) {

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
            $media_type = "image";
            $folder = "../upload/chat/";
        } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
            $media_type = "video";
            $folder = "../upload/chat/";
        }

        if ($media_type) {
            $newName = time()."_".rand(1000,9999).".".$ext;
            $path = $folder.$newName;
            move_uploaded_file($_FILES["files"]["tmp_name"][$index], $path);
            $media_path = "upload/chat/".$newName;

            // Insert file bubble
            $stmt = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
                VALUES (:cid,'csr','',:mp,:mt,:csr,NOW())
            ");
            $stmt->execute([
                ":cid"=>$client_id,
                ":mp"=>$media_path,
                ":mt"=>$media_type,
                ":csr"=>$csr_fullname
            ]);
        }
    }
}

/* Insert text message */
if ($message !== "") {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
        VALUES (:cid,'csr',:msg,:csr,NOW())
    ");
    $stmt->execute([
        ":cid"=>$client_id,
        ":msg"=>$message,
        ":csr"=>$csr_fullname
    ]);
}

echo json_encode(["status" => "ok"]);
?>
