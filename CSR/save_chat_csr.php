<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message     = trim($_POST["message"] ?? '');
$client_id   = $_POST["client_id"] ?? 0;
$csr_fullname = $_POST["csr_fullname"] ?? "CSR";

if (!$client_id) { echo json_encode(["status"=>"error","msg"=>"No client"]); exit; }

$media_paths = [];
$media_types = [];

if (!empty($_FILES["files"]["name"][0])) {
    for ($i = 0; $i < count($_FILES["files"]["name"]); $i++) {

        $ext = strtolower(pathinfo($_FILES["files"]["name"][$i], PATHINFO_EXTENSION));

        if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
            $media_types[] = "image";
            $folder = "upload/chat/";
        } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
            $media_types[] = "video";
            $folder = "upload/chat/";
        } else continue;

        $newName = time().'_'.rand(1000,9999).".".$ext;
        $path = "../CSR/" . $folder . $newName;

        if (move_uploaded_file($_FILES["files"]["tmp_name"][$i], $path)) {
            $media_paths[] = "CSR/".$folder.$newName;
        }
    }
}

/* Insert combined message entry */
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, 'csr', :msg, :mp, :mt, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":mp"  => isset($media_paths[0]) ? $media_paths[0] : null,
    ":mt"  => isset($media_types[0]) ? $media_types[0] : null,
    ":csr" => $csr_fullname
]);

echo json_encode(["status"=>"ok"]);
?>
