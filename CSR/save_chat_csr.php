<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$client_id = $_POST["client_id"] ?? 0;
$message   = trim($_POST["message"] ?? "");
$csr_user  = $_POST["csr_user"] ?? "";
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "No client ID"]);
    exit;
}

if ($message === "" && empty($_FILES['file']['name'])) {
    echo json_encode(["status" => "error", "msg" => "Empty message"]);
    exit;
}

/* ---- MEDIA UPLOAD HANDLING ---- */
$media_path = null;
$media_type = null;

if (!empty($_FILES["file"]["name"])) {
    $ext = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
        $folder = "../uploads/chat_images/";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
        $folder = "../uploads/chat_videos/";
    }

    if ($media_type) {
        if (!is_dir($folder)) mkdir($folder, 0777, true);
        
        $newName = time() . "_" . rand(1000, 9999) . "." . $ext;
        $fullPath = $folder . $newName;
        move_uploaded_file($_FILES["file"]["tmp_name"], $fullPath);

        // path for frontend
        $media_path = str_replace("../", "", $fullPath);
    }
}

/* ---- INSERT CHAT RECORD ---- */
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, assigned_csr, csr_fullname, is_seen, created_at)
    VALUES (:cid, 'csr', :msg, :mp, :mt, :csrUser, :csrFull, 0, NOW())
");
$stmt->execute([
    ":cid"      => $client_id,
    ":msg"      => $message,
    ":mp"       => $media_path,
    ":mt"       => $media_type,
    ":csrUser"  => $csr_user,
    ":csrFull"  => $csr_fullname
]);

/* ---- UPDATE CLIENT LAST ACTIVE & ASSIGN IF NONE ---- */
$conn->prepare("UPDATE clients SET last_active = NOW(), assigned_csr = :csr WHERE id = :cid AND (assigned_csr IS NULL OR assigned_csr = '')")
     ->execute([":csr" => $csr_user, ":cid" => $client_id]);

echo json_encode(["status" => "ok"]);
?>
