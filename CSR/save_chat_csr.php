<?php
session_start();
include "../db_connect.php";
include "b2_upload.php";

header("Content-Type: application/json");

$sender_type  = "csr";
$message      = trim($_POST["message"] ?? "");
$client_id    = intval($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Missing client ID"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES['files']['name'][0])) {
    $tmp  = $_FILES['files']['tmp_name'][0];
    $name = $_FILES['files']['name'][0];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) $media_type = "image";
    if (in_array($ext, ["mp4","mov","avi","mkv","webm"]))  $media_type = "video";

    if ($media_type) {
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $uploadedURL = b2_upload($tmp, $newName);

        if ($uploadedURL) {
            $media_path = $uploadedURL;
        }
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, :csr, NOW())
");
$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":mp"    => $media_path,
    ":mt"    => $media_type,
    ":csr"   => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
exit;
?>
